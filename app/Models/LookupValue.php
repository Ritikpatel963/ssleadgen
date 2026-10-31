<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LookupValue extends Model
{
    use HasFactory;

    protected $fillable = ['group', 'name', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getLabelAttribute(): string
    {
        return (string) $this->name;
    }

    public function getValueAttribute(): string
    {
        return (string) $this->name;
    }
    
    public function getStageKeyAttribute(): string
    {
        $normalized = strtolower(trim((string) $this->name));
        if ($normalized === 'new' || $normalized === 'new leads') {
            return 'new';
        }
        if ($normalized === 'contacted') {
            return 'contacted';
        }
        if ($normalized === 'proposal' || $normalized === 'underwriting') {
            return 'proposal';
        }
        if (str_contains($normalized, 'won') || $normalized === 'policy issued') {
            return 'won';
        }

        return \Illuminate\Support\Str::slug($normalized, '_');
    }
}
