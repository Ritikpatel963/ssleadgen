<?php

namespace Tests\Unit;

use App\Models\ImportJobTracking;
use App\Notifications\ClientImportFinished;
use Tests\TestCase;

class ClientImportFinishedTest extends TestCase
{
    public function test_notification_includes_counts_and_error_download_when_rows_failed(): void
    {
        $tracking = new ImportJobTracking([
            'uuid' => 'd0155ad1-a9cd-40b7-a35e-3422759980ef',
            'status' => 'done_with_errors',
            'imported_count' => 12,
            'skipped_count' => 3,
            'failed_count' => 3,
        ]);

        $payload = (new ClientImportFinished($tracking))->toArray(new \stdClass());

        $this->assertSame('Client import completed', $payload['title']);
        $this->assertStringContainsString('12 imported, 3 skipped, 3 failed', $payload['message']);
        $this->assertSame(route('import.errors.download', $tracking->uuid), $payload['error_csv_url']);
        $this->assertSame(route('clients.login', ['import_id' => $tracking->uuid]), $payload['url']);
    }
}