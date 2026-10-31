<?php

namespace Tests\Feature;

use App\Models\ImportJobFailure;
use App\Models\ImportJobTracking;
use App\Models\Role;
use App\Models\User;
use App\Jobs\ProcessImportJob;
use App\Models\Client;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportResultAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('import_job_failures');
        Schema::dropIfExists('import_jobs_tracking');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('insurers');
        Schema::dropIfExists('notifications');

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->json('permissions')->nullable();
            $table->boolean('is_super_admin')->default(false);
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
        Schema::create('import_jobs_tracking', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status', 32)->default('pending');
            $table->string('destination', 100)->nullable();
            $table->string('file_path', 500)->nullable();
            $table->unsignedInteger('sheet_index')->default(0);
            $table->unsignedInteger('next_source_row')->default(2);
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_count')->default(0);
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->text('error_message')->nullable();
            $table->json('skipped_details')->nullable();
            $table->json('source_headers')->nullable();
            $table->timestamps();
        });
        Schema::create('import_job_failures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('import_job_tracking_id');
            $table->unsignedInteger('row_number');
            $table->json('row_data');
            $table->text('reason');
            $table->timestamps();
        });
        Schema::create('insurers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('policy_no')->nullable();
            $table->string('company')->nullable();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->date('dob')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('suburb')->nullable();
            $table->string('city')->nullable();
            $table->string('post_code')->nullable();
            $table->date('login_date')->nullable();
            $table->decimal('anp', 10, 2)->default(0);
            $table->string('outcome')->nullable();
            $table->string('adviser')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('not_counting')->default(false);
            $table->string('compliance_by')->nullable();
            $table->date('roa_due_date')->nullable();
            $table->string('status')->default('Inforce');
            $table->string('sent_to_client')->default('Pending');
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_import_status_is_private_to_the_importing_user(): void
    {
        $owner = $this->createImportUser();
        $otherUser = $this->createImportUser();
        $tracking = ImportJobTracking::create([
            'uuid' => '8ee48b6b-6689-4ef3-9f75-a9c4d4b8386c',
            'user_id' => $owner->id,
            'status' => 'pending',
        ]);

        $this->actingAs($otherUser)
            ->getJson(route('import.status', $tracking->uuid))
            ->assertNotFound();
    }

    public function test_owned_client_import_is_accepted_and_dispatched_to_the_queue(): void
    {
        $owner = $this->createImportUser();
        Queue::fake();
        Storage::fake('local');
        $filePath = 'temp/imports/' . $owner->id . '/f3b2d36c-0877-48f3-b11f-8dbd60845598.csv';
        Storage::disk('local')->put($filePath, "first_name\nTaylor\n");

        $response = $this->actingAs($owner)->postJson(route('import.process'), [
            'file_path' => $filePath,
            'destination' => 'login_client',
            'sheet_index' => 0,
            'mapping' => [
                ['original_header' => 'first_name', 'db_field' => 'first_name'],
            ],
        ])->assertOk()->assertJson(['success' => true, 'queued' => true]);

        $trackingId = $response->json('tracking_id');
        $this->assertDatabaseHas('import_jobs_tracking', [
            'uuid' => $trackingId,
            'user_id' => $owner->id,
            'status' => 'pending',
        ]);
        Queue::assertPushed(ProcessImportJob::class, fn (ProcessImportJob $job) => $job->trackingUuid === $trackingId);

        $this->postJson(route('import.process'), [
            'file_path' => $filePath,
            'destination' => 'login_client',
            'sheet_index' => 0,
            'mapping' => [
                ['original_header' => 'first_name', 'db_field' => 'first_name'],
            ],
        ])->assertOk()->assertJsonPath('tracking_id', $trackingId);
        Queue::assertPushed(ProcessImportJob::class, 1);
    }

    public function test_queued_job_imports_valid_rows_and_notifies_with_failed_row_counts(): void
    {
        $owner = $this->createImportUser();
        DB::table('insurers')->insert([
            'name' => 'Acme Insurance',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Storage::fake('local');

        $filePath = 'temp/imports/' . $owner->id . '/f3b2d36c-0877-48f3-b11f-8dbd60845598.csv';
        Storage::disk('local')->put($filePath, implode("\n", [
            'first_name,last_name,phone,email,company',
            'Ada,Lovelace,5550101,ada@example.test,Acme Insurance',
            '',
            ' , , , , ',
            ',Invalid,5550102,invalid@example.test,Acme Insurance',
            'Grace,Hopper,5550103,grace@example.test,Acme Insurance',
        ]));
        $tracking = ImportJobTracking::create([
            'uuid' => 'f3b2d36c-0877-48f3-b11f-8dbd60845598',
            'user_id' => $owner->id,
            'status' => 'pending',
            'destination' => 'login_client',
            'file_path' => $filePath,
            'source_headers' => ['first_name', 'last_name', 'phone', 'email', 'company'],
        ]);
        $mapping = [];
        foreach ($tracking->source_headers as $header) {
            $mapping[] = ['original_header' => $header, 'db_field' => $header];
        }

        (new ProcessImportJob($tracking->uuid, $filePath, 'login_client', $mapping))->handle();

        $tracking->refresh();
        $this->assertSame('done_with_errors', $tracking->status);
        $this->assertSame(3, $tracking->total_rows);
        $this->assertSame(2, $tracking->imported_count);
        $this->assertSame(1, $tracking->failed_count);
        $this->assertSame(2, Client::count());
        $this->assertSame(5, $tracking->failures()->value('row_number'));
        $this->assertSame(1, $owner->notifications()->count());
        $this->assertStringContainsString('2 imported, 1 skipped, 1 failed', $owner->notifications()->first()->data['message']);
    }

    public function test_import_resumes_from_committed_chunk_without_reimporting_it(): void
    {
        $owner = $this->createImportUser();
        Queue::fake();
        Storage::fake('local');
        $filePath = 'temp/imports/' . $owner->id . '/resumable-import.csv';
        $csvRows = ['first_name,last_name,phone'];
        for ($row = 1; $row <= 1005; $row++) {
            $csvRows[] = "Person{$row},Last{$row},555" . str_pad((string) $row, 7, '0', STR_PAD_LEFT);
        }
        Storage::disk('local')->put($filePath, implode("\n", $csvRows));

        $tracking = ImportJobTracking::create([
            'uuid' => '5bcc7dd7-d88d-43b6-bd78-6274207aeec8',
            'user_id' => $owner->id,
            'status' => 'pending',
            'destination' => 'login_client',
            'file_path' => $filePath,
            'source_headers' => ['first_name', 'last_name', 'phone'],
        ]);
        $mapping = array_map(
            fn (string $header): array => ['original_header' => $header, 'db_field' => $header],
            $tracking->source_headers
        );

        (new ProcessImportJob($tracking->uuid, $filePath, 'login_client', $mapping))->handle();
        $tracking->refresh();
        $this->assertSame('processing', $tracking->status);
        $this->assertSame(100, $tracking->processed_count);
        $this->assertSame(100, $tracking->imported_count);
        $this->assertSame(102, $tracking->next_source_row);
        Queue::assertPushed(ProcessImportJob::class, fn (ProcessImportJob $job) => $job->startRow === 102);

        (new ProcessImportJob($tracking->uuid, $filePath, 'login_client', $mapping))->handle();
        $this->assertSame(100, Client::count());

        while ($tracking->status === 'processing') {
            (new ProcessImportJob(
                $tracking->uuid,
                $filePath,
                'login_client',
                $mapping,
                true,
                true,
                0,
                (int) $tracking->next_source_row
            ))->handle();
            $tracking->refresh();
        }

        $tracking->refresh();
        $this->assertSame('done', $tracking->status);
        $this->assertSame(1005, $tracking->total_rows);
        $this->assertSame(1005, $tracking->imported_count);
        $this->assertSame(1005, Client::count());
        $this->assertSame(1, $owner->notifications()->count());
    }

    public function test_terminal_worker_failure_preserves_committed_import_counts(): void
    {
        $owner = $this->createImportUser();
        $tracking = ImportJobTracking::create([
            'uuid' => '627eb5aa-5629-4c32-a9d9-e606863236ab',
            'user_id' => $owner->id,
            'status' => 'processing',
            'destination' => 'login_client',
            'file_path' => 'temp/imports/' . $owner->id . '/failed-import.csv',
            'next_source_row' => 102,
            'total_rows' => 500,
            'processed_count' => 100,
            'imported_count' => 98,
            'skipped_count' => 2,
            'failed_count' => 2,
        ]);

        (new ProcessImportJob(
            $tracking->uuid,
            $tracking->file_path,
            'login_client',
            [],
            true,
            true,
            0,
            102
        ))->failed(new \RuntimeException('Worker process was terminated'));

        $tracking->refresh();
        $this->assertSame('failed', $tracking->status);
        $this->assertSame(100, $tracking->processed_count);
        $this->assertSame(98, $tracking->imported_count);
        $this->assertSame(2, $tracking->failed_count);
        $this->assertSame(1, $owner->notifications()->count());
    }

    public function test_selected_excel_worksheet_supplies_headers_and_import_rows(): void
    {
        $owner = $this->createImportUser();
        Storage::fake('local');
        $filePath = 'temp/imports/' . $owner->id . '/c5f035ae-19fa-4667-a18c-d533fe3ae8c2.xlsx';

        $workbook = new Spreadsheet();
        $workbook->getActiveSheet()->setTitle('Instructions');
        $workbook->getActiveSheet()->fromArray([
            ['Notes', 'Value'],
            ['Ignore this tab', 'not a client'],
        ]);
        $clientSheet = $workbook->createSheet();
        $clientSheet->setTitle('Client Data');
        $clientRows = [['First Name', 'Last Name', 'Phone']];
        for ($row = 1; $row <= 60; $row++) {
            $clientRows[] = ["Person{$row}", "Last{$row}", '555' . str_pad((string) $row, 4, '0', STR_PAD_LEFT)];
        }
        $clientSheet->fromArray($clientRows);
        $clientSheet->getStyle('A1048481')->getNumberFormat()->setFormatCode('0');

        $temporaryFile = tempnam(sys_get_temp_dir(), 'client-import-');
        (new Xlsx($workbook))->save($temporaryFile);
        Storage::disk('local')->put($filePath, file_get_contents($temporaryFile));
        unlink($temporaryFile);
        $workbook->disconnectWorksheets();

        $this->actingAs($owner)->postJson(route('import.headers'), [
            'file_path' => $filePath,
            'destination' => 'login_client',
            'sheet_index' => 1,
        ])->assertOk()->assertJsonPath('sheet_name', 'Client Data')->assertJsonPath('headers.0', 'first_name');

        $importService = app(\App\Services\UniversalImportService::class);
        $sheets = $importService->getSheets($filePath);
        $this->assertSame('Client Data', $sheets[1]['name']);
        $headers = $importService->getHeaders($filePath, 1);
        $this->assertSame(['first_name', 'last_name', 'phone'], $headers);

        $mapping = array_map(
            fn (string $header): array => ['original_header' => $header, 'db_field' => $header],
            $headers
        );
        $preview = $this->postJson(route('import.preview'), [
            'file_path' => $filePath,
            'destination' => 'login_client',
            'sheet_index' => 1,
            'preview_offset' => 25,
            'preview_limit' => 25,
            'mapping' => $mapping,
        ])->assertOk();
        $preview->assertJsonPath('total_rows', 60)
            ->assertJsonPath('preview_data.0.raw.first_name', 'Person26')
            ->assertJsonPath('has_more', true);
        $this->assertCount(25, $preview->json('preview_data'));

        $tracking = ImportJobTracking::create([
            'uuid' => 'c5f035ae-19fa-4667-a18c-d533fe3ae8c2',
            'user_id' => $owner->id,
            'status' => 'pending',
            'destination' => 'login_client',
            'file_path' => $filePath,
            'sheet_index' => 1,
            'source_headers' => $headers,
        ]);

        (new ProcessImportJob($tracking->uuid, $filePath, 'login_client', $mapping, true, true, 1))->handle();

        $this->assertSame('done', $tracking->fresh()->status);
        $this->assertSame(60, $tracking->fresh()->imported_count);
        $this->assertDatabaseHas('clients', ['first_name' => 'Person1', 'last_name' => 'Last1']);
        $this->assertDatabaseMissing('clients', ['first_name' => 'Ignore this tab']);
    }

    public function test_csv_header_detection_remains_a_single_sheet_flow(): void
    {
        Storage::fake('local');
        $filePath = 'temp/imports/1/4bc3894c-2b1f-47c2-b41f-d459d9139a93.csv';
        Storage::disk('local')->put($filePath, "First Name,Last Name\nAlex,Smith\n");

        $importService = app(\App\Services\UniversalImportService::class);

        $this->assertSame([['index' => 0, 'name' => 'Worksheet']], $importService->getSheets($filePath));
        $this->assertSame(['first_name', 'last_name'], $importService->getHeaders($filePath, 0));
    }

    public function test_preview_pages_cover_all_rows_with_bounded_requests(): void
    {
        $owner = $this->createImportUser();
        Storage::fake('local');
        $filePath = 'temp/imports/' . $owner->id . '/a6743998-714b-47ea-9089-0429e221af55.csv';
        $csvRows = ['First Name,Last Name'];
        for ($row = 1; $row <= 60; $row++) {
            $csvRows[] = "Person{$row},Last{$row}";
        }
        Storage::disk('local')->put($filePath, implode("\n", $csvRows));

        $firstPage = $this->actingAs($owner)->postJson(route('import.preview'), [
            'file_path' => $filePath,
            'destination' => 'login_client',
            'sheet_index' => 0,
            'preview_offset' => 25,
            'preview_limit' => 25,
            'mapping' => [
                ['original_header' => 'first_name', 'db_field' => 'first_name'],
            ],
        ])->assertOk();

        $firstPage->assertJsonPath('preview_offset', 25)
            ->assertJsonPath('total_rows', 60)
            ->assertJsonPath('preview_data.0.raw.first_name', 'Person26');
        $firstPageRows = $firstPage->json('preview_data');
        $this->assertCount(25, $firstPageRows, json_encode([
            'first' => $firstPageRows[0]['raw']['first_name'] ?? null,
            'last' => $firstPageRows[array_key_last($firstPageRows)]['raw']['first_name'] ?? null,
            'total' => $firstPage->json('total_rows'),
        ]));
        $this->assertTrue($firstPage->json('has_more'));

        $lastPage = $this->postJson(route('import.preview'), [
            'file_path' => $filePath,
            'destination' => 'login_client',
            'sheet_index' => 0,
            'preview_offset' => 50,
            'preview_limit' => 25,
            'mapping' => [
                ['original_header' => 'first_name', 'db_field' => 'first_name'],
            ],
        ])->assertOk();

        $lastPage->assertJsonPath('preview_offset', 50)
            ->assertJsonPath('preview_limit', 25)
            ->assertJsonPath('total_rows', 60)
            ->assertJsonPath('has_more', false)
            ->assertJsonPath('preview_data.0.raw.first_name', 'Person51');
        $this->assertCount(10, $lastPage->json('preview_data'));
    }

    public function test_preview_omits_completely_empty_rows_and_keeps_source_row_numbers(): void
    {
        $owner = $this->createImportUser();
        Storage::fake('local');
        $filePath = 'temp/imports/' . $owner->id . '/4f6ce979-8d7a-49ee-a90e-fb38ffae817c.csv';
        Storage::disk('local')->put($filePath, implode("\n", [
            'First Name,Last Name',
            'Ada,Lovelace',
            '',
            ' , ',
            'Grace,Hopper',
        ]));

        $response = $this->actingAs($owner)->postJson(route('import.preview'), [
            'file_path' => $filePath,
            'destination' => 'login_client',
            'sheet_index' => 0,
            'preview_offset' => 0,
            'preview_limit' => 25,
            'mapping' => [
                ['original_header' => 'first_name', 'db_field' => 'first_name'],
            ],
        ])->assertOk();

        $this->assertCount(2, $response->json('preview_data'));
        $this->assertSame([2, 5], array_column($response->json('preview_data'), 'row_number'));
        $this->assertSame(['Ada', 'Grace'], array_column(array_column($response->json('preview_data'), 'raw'), 'first_name'));
    }

    public function test_cleanup_removes_expired_results_but_preserves_active_imports(): void
    {
        $owner = $this->createImportUser();
        Storage::fake('local');
        $expiredPath = 'temp/imports/' . $owner->id . '/expired-file.csv';
        $activePath = 'temp/imports/' . $owner->id . '/active-file.csv';
        Storage::disk('local')->put($expiredPath, 'expired');
        Storage::disk('local')->put($activePath, 'active');

        $expired = ImportJobTracking::create([
            'uuid' => 'b54195c6-81c2-4ea8-ae90-2eb449181ad9',
            'user_id' => $owner->id,
            'status' => 'done',
            'file_path' => $expiredPath,
        ]);
        $expired->forceFill([
            'created_at' => now()->subDays(31),
            'updated_at' => now()->subDays(31),
        ])->saveQuietly();

        $active = ImportJobTracking::create([
            'uuid' => 'dc00cfc4-ef64-47ac-ad91-6ea72cc1004a',
            'user_id' => $owner->id,
            'status' => 'processing',
            'file_path' => $activePath,
        ]);
        $active->forceFill([
            'created_at' => now()->subDays(31),
            'updated_at' => now()->subDays(31),
        ])->saveQuietly();

        Artisan::call('imports:cleanup');

        $this->assertDatabaseMissing('import_jobs_tracking', ['id' => $expired->id]);
        $this->assertDatabaseHas('import_jobs_tracking', ['id' => $active->id]);
        $this->assertFalse(Storage::disk('local')->exists($expiredPath));
        $this->assertTrue(Storage::disk('local')->exists($activePath));
    }

    public function test_error_csv_contains_source_data_row_number_and_safe_values(): void
    {
        $owner = $this->createImportUser();
        $tracking = ImportJobTracking::create([
            'uuid' => 'd0155ad1-a9cd-40b7-a35e-3422759980ef',
            'user_id' => $owner->id,
            'status' => 'done_with_errors',
            'failed_count' => 1,
            'skipped_count' => 1,
            'source_headers' => ['first_name', 'phone'],
        ]);
        ImportJobFailure::create([
            'import_job_tracking_id' => $tracking->id,
            'row_number' => 4,
            'row_data' => ['first_name' => '=HYPERLINK("https://example.invalid")', 'phone' => '555-0100'],
            'reason' => 'Duplicate email address already exists',
        ]);

        $response = $this->actingAs($owner)
            ->get(route('import.errors.download', $tracking->uuid))
            ->assertOk()
            ->assertDownload('client-import-errors-' . $tracking->uuid . '.csv');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('_source_row,first_name,phone,_error', $csv);
        $this->assertStringContainsString('4,', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString('Duplicate email address already exists', $csv);
    }

    private function createImportUser(): User
    {
        $role = Role::create([
            'name' => 'Import Admin ' . fake()->unique()->numerify('####'),
            'slug' => 'import-admin-' . fake()->unique()->numerify('####'),
            'permissions' => [],
            'is_super_admin' => true,
        ]);

        return User::factory()->create(['role_id' => $role->id, 'status' => 'active']);
    }
}