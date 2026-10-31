<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'permissions', 'is_super_admin'];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_super_admin' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * The full list of permission modules used across the app.
     *
     * @return string[]
     */
    public static function modules(): array
    {
        return [
            'dashboard',
            'clients', 'clients_login', 'clients_inforce', 'clients_inactive', 'clients_claims', 'clients_cancellation', 'clients_npw_deferred',
            'leads',
            'tasks',
            'calendar',
            'policies',
            'reports',
            'documents',
            'access',
        ];
    }

    /**
     * Check whether this role has at least the given level for a module.
     * Levels: none < read < write
     */
    public function can(string $module, string $level = 'read'): bool
    {
        $map = ['none' => 0, 'read' => 1, 'write' => 2];
        $have = $map[$this->permissions[$module] ?? 'none'] ?? 0;
        $need = $map[$level] ?? 1;

        return $have >= $need;
    }
}
