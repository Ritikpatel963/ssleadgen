<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TaskNote extends Model
{
    protected $fillable = ['task_id', 'note'];

    protected static function booted(): void
    {
        static::addGlobalScope('latest', fn (Builder $query): Builder => $query->latest());
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
