<?php

namespace App\Notifications;

use App\Models\ImportJobTracking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ClientImportFinished extends Notification
{
    use Queueable;

    public function __construct(private ImportJobTracking $tracking) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $isLeadImport = $this->tracking->destination === 'leads';
        $recordType = $isLeadImport ? 'Lead' : 'Client';
        $failedCount = (int) $this->tracking->failed_count;
        $message = sprintf(
            $recordType . ' import finished: %d imported, %d skipped, %d failed.',
            $this->tracking->imported_count,
            $this->tracking->skipped_count,
            $failedCount
        );

        if ($this->tracking->error_message) {
            $message .= ' ' . $this->tracking->error_message;
        }

        return [
            'title' => $this->tracking->status === 'failed' ? $recordType . ' import failed' : $recordType . ' import completed',
            'message' => $message,
            'icon' => $this->tracking->status === 'failed' ? 'alert-triangle' : 'upload',
            'url' => $isLeadImport ? route('crm.pipeline') : route('clients.login', ['import_id' => $this->tracking->uuid]),
            'error_csv_url' => $failedCount > 0
                ? route('import.errors.download', $this->tracking->uuid)
                : null,
            'import_id' => $this->tracking->uuid,
        ];
    }
}