<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportJobTracking extends Model
{
    protected $table = 'import_jobs_tracking';

    protected $fillable = [
        'uuid', 'user_id', 'status', 'destination', 'file_path',
        'sheet_index', 'next_source_row', 'total_rows', 'processed_count', 'imported_count', 'skipped_count', 'failed_count',
        'error_message', 'skipped_details', 'source_headers'
    ];

    protected $casts = [
        'skipped_details' => 'array',
        'source_headers' => 'array',
    ];

    public function failures(): HasMany
    {
        return $this->hasMany(ImportJobFailure::class, 'import_job_tracking_id');
    }
}
