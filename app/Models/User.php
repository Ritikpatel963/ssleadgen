<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role_id', 'status', 'fspr_number', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function auditLogsAsActor(): HasMany
    {
        return $this->hasMany(UserAuditLog::class, 'actor_id');
    }

    public function auditLogsAsTarget(): HasMany
    {
        return $this->hasMany(UserAuditLog::class, 'target_user_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->role?->is_super_admin === true
            || in_array(strtolower((string)$this->role?->name), ['admin', 'super admin', 'super administrator'], true)
            || in_array(strtolower((string)$this->role?->slug), ['admin', 'super-admin'], true);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasPermission(string $module, string $level = 'read'): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($module === 'clients_profile') {
            foreach (['clients', 'clients_login', 'clients_inforce', 'clients_inactive', 'clients_claims', 'clients_cancellation', 'clients_npw_deferred'] as $clientModule) {
                if ($this->role?->can($clientModule, $level)) {
                    return true;
                }
            }

            return false;
        }

        if (str_starts_with($module, 'clients_')) {
            if ($this->role?->can($module, $level)) {
                return true;
            }
            if (!isset($this->role?->permissions[$module]) || $this->role?->permissions[$module] === 'none') {
                return $this->role?->can('clients', $level) ?? false;
            }
        }

        return $this->role?->can($module, $level) ?? false;
    }

    public function canWrite(string $module): bool
    {
        return $this->hasPermission($module, 'write');
    }

    public function canRead(string $module): bool
    {
        return $this->hasPermission($module, 'read');
    }

    public function defaultRouteName(): string
    {
        foreach ([
            'dashboard' => 'dashboard',
            'clients_login' => 'clients.login',
            'clients' => 'clients.index',
            'clients_inforce' => 'clients.inforce',
            'clients_inactive' => 'clients.inactive',
            'clients_claims' => 'clients.claims',
            'clients_cancellation' => 'clients.cancellation',
            'clients_npw_deferred' => 'clients.npw-deferred',
            'leads' => 'crm.pipeline',
            'tasks' => 'tasks.index',
            'calendar' => 'calendar.index',
            'policies' => 'policies.index',
            'reports' => 'crm.reports',
            'documents' => 'documents.index',
            'access' => 'settings.access',
        ] as $module => $route) {
            if ($this->canRead($module)) {
                return $route;
            }
        }

        return 'dashboard';
    }
}
