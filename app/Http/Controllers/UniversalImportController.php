<?php

namespace App\Http\Controllers;

use App\Imports\SelectedSheetImport;
use App\Services\ImportDestinations;
use App\Services\UniversalImportService;
use App\Jobs\ProcessImportJob;
use App\Models\ImportJobTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Collection;

class PreviewImport implements ToCollection, WithHeadingRow, WithLimit, WithStartRow, WithChunkReading, WithCalculatedFormulas
{
    public array $data = [];
    private ?int $chunkOffset = null;

    public function __construct(
        private int $firstSourceRow = 2,
        private int $rowSpan = 25,
    ) {}

    public function collection(Collection $rows): void
    {
        $this->data = $rows->toArray();
    }

    public function limit(): int
    {
        return $this->chunkOffset === null
            ? $this->firstSourceRow + $this->rowSpan - 1
            : $this->rowSpan;
    }

    public function setChunkOffset(int $row): void
    {
        $this->chunkOffset = $row;
    }

    public function startRow(): int
    {
        return $this->firstSourceRow;
    }

    public function chunkSize(): int
    {
        return $this->rowSpan;
    }
}

class UniversalImportController extends Controller
{
    public function downloadTemplate(string $destination)
    {
        $configs = [
            'leads' => ['table' => 'leads', 'filename' => 'leads-template.csv'],
            'login_client' => ['table' => 'clients', 'filename' => 'login-clients-template.csv'],
        ];
        abort_unless(isset($configs[$destination]), 404);

        $config = ImportDestinations::getConfig($destination);
        abort_unless($config, 404);

        $columns = Schema::getColumnListing($configs[$destination]['table']);
        $systemColumns = ['id', 'created_at', 'updated_at', 'deleted_at', 'remember_token', 'remember_tokens', 'password'];
        $headers = [];

        foreach ($columns as $column) {
            if (in_array($column, $systemColumns, true) || $column === 'adviser') {
                continue;
            }

            $field = match ($column) {
                'lead_source_id' => 'lead_source',
                'user_id' => 'adviser',
                default => $column,
            };
            $headers[] = $config['fields'][$field]['label'] ?? Str::headline($column);
        }

        return response()->streamDownload(function () use ($headers) {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers, ',', '"', '');
            fclose($output);
        }, $configs[$destination]['filename'], ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function upload(Request $request)
    {
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', '300');
        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt,xls,xlsx|max:10240',
            'destination' => 'required|string',
        ]);

        $destinationConfig = ImportDestinations::getConfig($request->destination);
        if (!$destinationConfig) {
            return response()->json(['success' => false, 'message' => 'Invalid destination module.']);
        }
        $this->assertDestinationPermission($request, $destinationConfig);

        $file = $request->file('import_file');
        $fileName = Str::uuid() . '.' . strtolower($file->getClientOriginalExtension());
        $filePath = $file->storeAs('temp/imports/' . $request->user()->id, $fileName);

        $sheets = app(UniversalImportService::class)->getSheets($filePath);
        if (empty($sheets)) {
            Storage::delete($filePath);
            return response()->json(['success' => false, 'message' => 'Could not read workbook sheets or file is empty.']);
        }

        return response()->json([
            'success' => true,
            'file_path' => $filePath,
            'destination' => $request->destination,
            'sheets' => $sheets,
        ]);
    }

    public function headers(Request $request)
    {
        $request->validate([
            'file_path' => 'required|string',
            'destination' => 'required|string',
            'sheet_index' => 'required|integer|min:0',
        ]);

        $this->assertOwnedUpload($request->user()->id, $request->file_path);
        abort_unless(Storage::exists($request->file_path), 404, 'Temporary file expired or not found.');

        $destinationConfig = ImportDestinations::getConfig($request->destination);
        abort_unless($destinationConfig, 422, 'Invalid destination module.');
        $this->assertDestinationPermission($request, $destinationConfig);

        $sheets = app(UniversalImportService::class)->getSheets($request->file_path);
        $selectedSheet = collect($sheets)->firstWhere('index', (int) $request->sheet_index);
        abort_unless($selectedSheet, 422, 'Selected worksheet does not exist.');

        $importService = app(UniversalImportService::class);
        $headers = $importService->getHeaders($request->file_path, (int) $request->sheet_index);
        if (empty($headers)) {
            return response()->json(['success' => false, 'message' => 'The selected worksheet has no readable header row.'], 422);
        }

        return response()->json([
            'success' => true,
            'sheet_index' => (int) $request->sheet_index,
            'sheet_name' => $selectedSheet['name'],
            'headers' => $headers,
            'db_fields' => $destinationConfig['fields'],
            'mapping' => $importService->suggestMapping($headers, $destinationConfig),
        ]);
    }

    public function preview(Request $request)
    {
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', '300');
        $request->validate([
            'file_path' => 'required|string',
            'destination' => 'required|string',
            'sheet_index' => 'required|integer|min:0',
            'preview_offset' => 'nullable|integer|min:0',
            'preview_limit' => 'nullable|integer|min:1|max:100',
            'mapping' => 'required|array',
            'mapping.*.original_header' => 'required|string|max:255',
            'mapping.*.db_field' => 'required|string|max:100',
            'default_lead_source' => ['nullable', 'string', 'max:120', Rule::exists('lookup_values', 'name')->where(fn ($query) => $query->where('group', 'lead_source')->where('is_active', true))],
        ]);

        $this->assertOwnedUpload($request->user()->id, $request->file_path);
        if (!Storage::exists($request->file_path)) {
            return response()->json(['success' => false, 'message' => 'Temporary file expired or not found.']);
        }

        $destinationConfig = ImportDestinations::getConfig($request->destination);
        if (!$destinationConfig) {
            return response()->json(['success' => false, 'message' => 'Invalid destination module.'], 422);
        }
        $this->assertDestinationPermission($request, $destinationConfig);

        $this->assertValidSheet($request->file_path, (int) $request->sheet_index);

        $previewOffset = (int) $request->input('preview_offset', 0);
        $requestedPreviewLimit = (int) $request->input('preview_limit', 25);
        $importService = app(UniversalImportService::class);
        $nonEmptyRowNumbers = $importService->getNonEmptyRowNumbers(
            $request->file_path,
            (int) $request->sheet_index
        );
        $totalRows = count($nonEmptyRowNumbers);
        $pageSourceRows = array_slice($nonEmptyRowNumbers, $previewOffset, $requestedPreviewLimit);
        if ($previewOffset >= $totalRows) {
            return response()->json([
                'success' => true,
                'preview_data' => [],
                'preview_offset' => $previewOffset,
                'preview_limit' => $requestedPreviewLimit,
                'total_rows' => $totalRows,
                'has_more' => false,
            ]);
        }

        $previewLimit = count($pageSourceRows);
        $firstSourceRow = $pageSourceRows[0];
        $lastSourceRow = $pageSourceRows[array_key_last($pageSourceRows)];
        $previewImport = new PreviewImport($firstSourceRow, $lastSourceRow - $firstSourceRow + 1);
        Excel::import(
            SelectedSheetImport::forFile($request->file_path, $previewImport, (int) $request->sheet_index),
            Storage::path($request->file_path)
        );

        $importService = app(UniversalImportService::class);
        if ($request->destination === 'login_client') {
            $importService->preloadExistingClients($previewImport->data, $request->mapping);
        } elseif ($request->destination === 'leads') {
            $importService->preloadExistingLeads($previewImport->data, $request->mapping);
        }
        $previewData = [];
        foreach ($previewImport->data as $index => $row) {
            if ($importService->isEmptyRow($row)) {
                continue;
            }

            $overwriteExisting = $request->destination === 'login_client';
            $processedRow = $importService->processRow($row, $destinationConfig, $request->mapping, $overwriteExisting, $request->input('default_lead_source'));
            $processedRow['row_number'] = $previewOffset + $index + 2;
                        $processedRow['row_number'] = $firstSourceRow + $index;
            $previewData[] = $processedRow;
        }

        return response()->json([
            'success' => true,
            'preview_data' => $previewData,
            'preview_offset' => $previewOffset,
            'preview_limit' => $requestedPreviewLimit,
            'total_rows' => $totalRows,
            'has_more' => $previewOffset + $previewLimit < $totalRows,
                    'has_more' => $previewOffset + count($previewData) < $totalRows,
        ]);
    }

    public function process(Request $request)
    {
        $request->validate([
            'file_path'   => 'required|string',
            'destination' => 'required|string',
            'sheet_index' => 'required|integer|min:0',
            'mapping'     => 'required|array',
            'mapping.*.original_header' => 'required|string|max:255',
            'mapping.*.db_field' => 'required|string|max:100',
            'default_lead_source' => ['nullable', 'string', 'max:120', Rule::exists('lookup_values', 'name')->where(fn ($query) => $query->where('group', 'lead_source')->where('is_active', true))],
        ]);

        $destinationConfig = ImportDestinations::getConfig($request->destination);
        if (!$destinationConfig) {
            return response()->json(['success' => false, 'message' => 'Invalid destination module.'], 422);
        }
        $this->assertDestinationPermission($request, $destinationConfig);

        $this->assertOwnedUpload($request->user()->id, $request->file_path);
        if (!Storage::exists($request->file_path)) {
            return response()->json(['success' => false, 'message' => 'Temporary file expired or not found.']);
        }

        $this->assertValidSheet($request->file_path, (int) $request->sheet_index);

        foreach ($request->mapping as $mapInfo) {
            if ($mapInfo['db_field'] !== '--ignore--' && !array_key_exists($mapInfo['db_field'], $destinationConfig['fields'])) {
                return response()->json(['success' => false, 'message' => 'Invalid field mapping.'], 422);
            }
        }

        $activeImport = ImportJobTracking::query()
            ->where('user_id', $request->user()->id)
            ->where('file_path', $request->file_path)
            ->whereIn('status', ['pending', 'processing'])
            ->first();
        if ($activeImport) {
            if ((int) $activeImport->sheet_index !== (int) $request->sheet_index) {
                return response()->json([
                    'success' => false,
                    'message' => 'This workbook is already being imported from another worksheet. Wait for it to finish or upload the workbook again.',
                ], 409);
            }

            return response()->json([
                'success' => true,
                'queued' => true,
                'tracking_id' => $activeImport->uuid,
                'message' => 'This file is already queued for import.',
            ]);
        }

        $uuid = Str::uuid()->toString();
        $headers = array_values(array_unique(array_column($request->mapping, 'original_header')));

        DB::transaction(function () use ($uuid, $request, $headers) {
            ImportJobTracking::create([
                'uuid' => $uuid,
                'user_id' => $request->user()->id,
                'status' => 'pending',
                'destination' => $request->destination,
                'file_path' => $request->file_path,
                'sheet_index' => (int) $request->sheet_index,
                'source_headers' => $headers,
            ]);

            ProcessImportJob::dispatch(
                $uuid,
                $request->file_path,
                $request->destination,
                $request->mapping,
                $request->boolean('skip_errors', true),
                $request->boolean('overwrite', true),
                (int) $request->sheet_index,
                2,
                $request->input('default_lead_source'),
            )->afterCommit();
        });

        return response()->json([
            'success'      => true,
            'queued'       => true,
            'tracking_id'  => $uuid,
            'message'      => 'Import queued. Processing in background...',
        ]);
    }

    public function status(string $uuid)
    {
        $tracking = ImportJobTracking::where('uuid', $uuid)
            ->where('user_id', request()->user()->id)
            ->firstOrFail();
        $this->assertDestinationPermission(request(), ImportDestinations::getConfig($tracking->destination));

        return response()->json([
            'status'         => $tracking->status,
            'total_rows'     => $tracking->total_rows,
            'processed_count'=> $tracking->processed_count,
            'imported_count' => $tracking->imported_count,
            'skipped_count'  => $tracking->skipped_count,
            'failed_count'   => $tracking->failed_count,
            'error_message'  => $tracking->error_message,
            'error_csv_url'  => $tracking->failed_count > 0
                ? route('import.errors.download', $tracking->uuid)
                : null,
        ]);
    }

    public function downloadErrors(string $uuid)
    {
        $tracking = ImportJobTracking::where('uuid', $uuid)
            ->where('user_id', request()->user()->id)
            ->firstOrFail();
        $this->assertDestinationPermission(request(), ImportDestinations::getConfig($tracking->destination));

        abort_unless(in_array($tracking->status, ['done', 'done_with_errors', 'failed'], true), 404);
        abort_unless($tracking->failed_count > 0, 404);

        $headers = $tracking->source_headers ?? [];
        $filename = 'client-import-errors-' . $tracking->uuid . '.csv';

        return response()->streamDownload(function () use ($tracking, $headers) {
            $output = fopen('php://output', 'w');
            fputcsv($output, array_merge(['_source_row'], $headers, ['_error']), ',', '"', '', "\r\n");

            $tracking->failures()->orderBy('row_number')->cursor()->each(function ($failure) use ($output, $headers) {
                $row = $failure->row_data ?? [];
                $values = [$failure->row_number];
                foreach ($headers as $header) {
                    $values[] = $this->safeCsvCell($row[$header] ?? '');
                }
                $values[] = $this->safeCsvCell($failure->reason);
                fputcsv($output, $values, ',', '"', '', "\r\n");
            });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function safeCsvCell(mixed $value): string
    {
        $cell = is_scalar($value) || $value === null ? (string) $value : json_encode($value);
        if (preg_match('/^\s*[=+@-]/u', $cell)) {
            return "'" . $cell;
        }

        return $cell;
    }

    private function assertOwnedUpload(int $userId, string $filePath): void
    {
        $pattern = '/^temp\/imports\/' . preg_quote((string) $userId, '/') . '\/[0-9a-f-]{36}\.(csv|txt|xls|xlsx)$/i';
        abort_unless(preg_match($pattern, $filePath) === 1, 404);
    }

    private function assertDestinationPermission(Request $request, ?array $destinationConfig): void
    {
        abort_unless($destinationConfig, 422, 'Invalid import destination.');
        abort_unless(
            $request->user()->hasPermission($destinationConfig['permission'], 'write'),
            403,
            'You are not allowed to import records into this section.'
        );
    }

    private function assertValidSheet(string $filePath, int $sheetIndex): void
    {
        $sheets = app(UniversalImportService::class)->getSheets($filePath);
        abort_unless(collect($sheets)->contains('index', $sheetIndex), 422, 'Selected worksheet does not exist.');
    }
}
