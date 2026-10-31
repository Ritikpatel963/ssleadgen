<?php

namespace App\Jobs;

use App\Imports\RowRangeImport;
use App\Imports\SelectedSheetImport;
use App\Models\Client;
use App\Models\ImportJobTracking;
use App\Models\User;
use App\Notifications\ClientImportFinished;
use App\Services\ImportDestinations;
use App\Services\UniversalImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessImportJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 45;
    public $tries = 3;
    public $backoff = 30;
    private const ROWS_PER_JOB = 100;

    public function __construct(
        public string $trackingUuid,
        public string $filePath,
        public string $destination,
        public array  $mapping,
        public bool   $skipErrors = true,
        public bool   $overwrite  = true,
        public int    $sheetIndex = 0,
        public int    $startRow   = 2,
        public ?string $defaultLeadSource = null,
    ) {}

    public function handle(): void
    {
        $tracking = ImportJobTracking::where('uuid', $this->trackingUuid)->first();
        if (!$tracking) {
            return;
        }

        if (in_array($tracking->status, ['done', 'done_with_errors', 'failed'], true)) {
            $this->deleteUploadedFile();
            return;
        }

        if (!Storage::exists($this->filePath)) {
            $this->failImport($tracking, 'Uploaded file not found.');
            return;
        }

        $destinationConfig = ImportDestinations::getConfig($this->destination);
        if (!$destinationConfig) {
            $this->failImport($tracking, 'Invalid import destination.');
            return;
        }

        $importService = new UniversalImportService();
        $rowNumbers = $importService->getNonEmptyRowNumbers($this->filePath, $this->sheetIndex);
        $totalRows = count($rowNumbers);
        $remainingRows = array_values(array_filter($rowNumbers, fn (int $row) => $row >= $this->startRow));

        if (!$remainingRows) {
            $this->finishImport($tracking, $totalRows);
            $this->deleteUploadedFile();
            return;
        }

        $firstSourceRow = $remainingRows[0];
        $lastSourceRow = (int) end($rowNumbers);
        $rangeEnd = min($firstSourceRow + self::ROWS_PER_JOB - 1, $lastSourceRow);
        $rowRange = new RowRangeImport($firstSourceRow, $rangeEnd - $firstSourceRow + 1);

        Excel::import(
            SelectedSheetImport::forFile($this->filePath, $rowRange, $this->sheetIndex),
            Storage::path($this->filePath)
        );

        $sourceRows = $rowRange->data;
        if ($this->destination === 'login_client') {
            $importService->preloadExistingClients($sourceRows, $this->mapping);
        } elseif ($this->destination === 'leads') {
            $leadPhoneCacheKey = 'universal-import:lead-phones:' . $this->trackingUuid;
            $importService->preloadExistingLeads(
                $sourceRows,
                $this->mapping,
                Cache::get($leadPhoneCacheKey, [])
            );
        }
        $preparedRows = [];
        $preparedPhoneRows = [];
        $failureRows = [];
        $abortMessage = null;
        $attemptedRows = 0;

        foreach ($sourceRows as $index => $sourceRow) {
            $rowNumber = $firstSourceRow + $index;
            if ($importService->isEmptyRow($sourceRow)) {
                continue;
            }

            $attemptedRows++;
            $processed = $importService->processRow(
                $sourceRow,
                $destinationConfig,
                $this->mapping,
                $this->overwrite,
                $this->defaultLeadSource
            );

            if (!$processed['success']) {
                $failureRows[] = $this->failureRow(
                    $tracking->id,
                    $rowNumber,
                    $sourceRow,
                    implode(', ', $processed['errors'])
                );
                if (!$this->skipErrors) {
                    $abortMessage = 'Validation error: ' . implode(', ', $processed['errors']);
                    break;
                }
                continue;
            }

            $data = $processed['data'];
            if ($destinationConfig['name'] === 'Login Client') {
                $duplicatePhoneInFile = $data['__duplicate_phone_in_file'] ?? false;
                unset($data['__duplicate_phone_in_file']);
                $phone = $data['phone'] ?? null;

                if ($duplicatePhoneInFile && !$data['existing_client_id'] && $phone !== null && isset($preparedPhoneRows[$phone])) {
                    $preparedRows[$preparedPhoneRows[$phone]] = $data;
                    continue;
                }

                if ($phone !== null && !isset($preparedPhoneRows[$phone])) {
                    $preparedPhoneRows[$phone] = count($preparedRows);
                }
            }
            $preparedRows[] = $data;
        }

        if ($this->destination === 'leads') {
            Cache::put(
                'universal-import:lead-phones:' . $this->trackingUuid,
                $importService->getSeenLeadPhones(),
                now()->addDay()
            );
        }

        $isFinal = DB::transaction(function () use (
            $tracking,
            $totalRows,
            $lastSourceRow,
            $rangeEnd,
            $attemptedRows,
            $preparedRows,
            $failureRows,
            $abortMessage,
            $destinationConfig,
            $importService
        ): bool {
            $lockedTracking = ImportJobTracking::query()
                ->whereKey($tracking->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedTracking || in_array($lockedTracking->status, ['done', 'done_with_errors', 'failed'], true)) {
                return true;
            }

            if ((int) $lockedTracking->next_source_row > $this->startRow) {
                return false;
            }

            $baseProcessed = (int) $lockedTracking->processed_count;
            $baseImported = (int) $lockedTracking->imported_count;
            $baseSkipped = (int) $lockedTracking->skipped_count;

            if ($abortMessage !== null) {
                if ($failureRows) {
                    DB::table('import_job_failures')->insert([$failureRows[0]]);
                }
                $lockedTracking->update([
                    'status' => 'failed',
                    'total_rows' => $totalRows,
                    'processed_count' => $baseProcessed + $attemptedRows,
                    'skipped_count' => $baseSkipped + 1,
                    'failed_count' => (int) $lockedTracking->failed_count + 1,
                    'error_message' => $abortMessage,
                ]);
                $this->notifyUser($lockedTracking->fresh());
                return true;
            }

            $importedThisChunk = 0;
            foreach ($preparedRows as $data) {
                if ($destinationConfig['name'] === 'Login Client') {
                    $existingId = $data['existing_client_id'] ?? null;
                    unset($data['existing_client_id']);
                    if ($existingId) {
                        Client::whereKey($existingId)->update($data);
                        $importService->forgetClient((int) $existingId);
                        $importService->rememberClient($data + ['id' => $existingId]);
                    } else {
                        $client = Client::create($data);
                        $importService->rememberClient($client->toArray());
                    }
                } else {
                    $modelClass = $destinationConfig['model'];
                    $modelClass::create($data);
                }
                $importedThisChunk++;
            }

            if ($failureRows) {
                DB::table('import_job_failures')->insert($failureRows);
            }

            $nextSourceRow = $rangeEnd + 1;
            $isFinalChunk = $nextSourceRow > $lastSourceRow;
            $newImportedCount = $baseImported + $importedThisChunk;
            $newSkippedCount = $baseSkipped + count($failureRows);
            $lockedTracking->update([
                'status' => $isFinalChunk ? ($newSkippedCount > 0 ? 'done_with_errors' : 'done') : 'processing',
                'total_rows' => $totalRows,
                'next_source_row' => $nextSourceRow,
                'processed_count' => $baseProcessed + $attemptedRows,
                'imported_count' => $newImportedCount,
                'skipped_count' => $newSkippedCount,
                'failed_count' => (int) $lockedTracking->failed_count + count($failureRows),
            ]);

            if ($isFinalChunk) {
                $this->notifyUser($lockedTracking->fresh());
                return true;
            }

            ProcessImportJob::dispatch(
                $this->trackingUuid,
                $this->filePath,
                $this->destination,
                $this->mapping,
                $this->skipErrors,
                $this->overwrite,
                $this->sheetIndex,
                $nextSourceRow,
                $this->defaultLeadSource,
            )->onQueue($this->queue ?? 'default');

            return false;
        }, 3);

        if ($isFinal) {
            $this->deleteUploadedFile();
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ProcessImportJob failed: ' . $e->getMessage());
        $tracking = ImportJobTracking::where('uuid', $this->trackingUuid)->first();
        if (!$tracking || in_array($tracking->status, ['done', 'done_with_errors', 'failed'], true)) {
            return;
        }

        $this->deleteUploadedFile();
        $tracking->update([
            'status' => 'failed',
            'error_message' => 'Import worker failed: ' . $e->getMessage(),
        ]);
        $this->notifyUser($tracking->fresh());
    }

    private function failureRow(int $trackingId, int $rowNumber, array $row, string $reason): array
    {
        $now = now();

        return [
            'import_job_tracking_id' => $trackingId,
            'row_number' => $rowNumber,
            'row_data' => json_encode($row, JSON_THROW_ON_ERROR),
            'reason' => $reason,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function finishImport(ImportJobTracking $tracking, int $totalRows): void
    {
        DB::transaction(function () use ($tracking, $totalRows) {
            $lockedTracking = ImportJobTracking::query()->whereKey($tracking->id)->lockForUpdate()->first();
            if (!$lockedTracking || in_array($lockedTracking->status, ['done', 'done_with_errors', 'failed'], true)) {
                return;
            }

            $lockedTracking->update([
                'status' => $lockedTracking->skipped_count > 0 ? 'done_with_errors' : 'done',
                'total_rows' => $totalRows,
                'next_source_row' => $this->startRow,
            ]);
            $this->notifyUser($lockedTracking->fresh());
        });
    }

    private function failImport(
        ImportJobTracking $tracking,
        string $message,
        ?int $processed = null,
        ?int $imported = null,
        ?int $skipped = null
    ): void {
        $this->deleteUploadedFile();
        $processed ??= (int) $tracking->processed_count;
        $imported ??= (int) $tracking->imported_count;
        $skipped ??= (int) $tracking->skipped_count;
        $tracking->update([
            'status' => 'failed',
            'total_rows' => $tracking->total_rows,
            'processed_count' => $processed,
            'imported_count' => $imported,
            'skipped_count' => $skipped,
            'failed_count' => max((int) $tracking->failed_count, $skipped),
            'error_message' => $message,
        ]);
        $this->notifyUser($tracking->fresh());
    }

    private function deleteUploadedFile(): void
    {
        Cache::forget('universal-import:lead-phones:' . $this->trackingUuid);
        try {
            Storage::delete($this->filePath);
        } catch (\Throwable $e) {
            Log::warning('Unable to remove client import source file: ' . $e->getMessage(), [
                'import_id' => $this->trackingUuid,
            ]);
        }
    }

    private function notifyUser(?ImportJobTracking $tracking): void
    {
        if (!$tracking || !$tracking->user_id) {
            return;
        }

        try {
            User::find($tracking->user_id)?->notify(new ClientImportFinished($tracking));
        } catch (\Throwable $e) {
            Log::error('Unable to send client import notification: ' . $e->getMessage(), [
                'import_id' => $tracking->uuid,
            ]);
        }
    }
}
