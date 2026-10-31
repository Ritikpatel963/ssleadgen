<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = ['title', 'client_name', 'due_date', 'due_time', 'priority', 'status'];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function notes()
    {
        return $this->hasMany(TaskNote::class);
    }
}
