<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportJobFailure extends Model
{
    protected $fillable = [
        'import_job_tracking_id',
        'row_number',
        'row_data',
        'reason',
    ];

    protected $casts = [
        'row_data' => 'array',
    ];
}