<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'client_name',
        'client_id',
        'appointment_date',
        'appointment_time',
        'location',
        'notes',
        'status',
        'color',
    ];

    protected $casts = [
        'appointment_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
