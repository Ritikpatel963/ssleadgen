<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientServiceRequest extends Model
{
    protected $fillable = [
        'client_id',
        'request_date',
        'client_name',
        'company',
        'request_type',
        'process_status',
        'process_by',
        'outcome',
        'comments',
        'finished_date',
        'status',
    ];

    protected $casts = [
        'request_date' => 'date',
        'finished_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}