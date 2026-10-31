<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->select(['id', 'slug', 'permissions'])->orderBy('id')->chunkById(100, function ($roles): void {
            foreach ($roles as $role) {
                $permissions = json_decode($role->permissions ?: '{}', true) ?: [];
                $hadClaimsPagePermission = array_key_exists('clients_claims', $permissions);
                $legacyClaims = $permissions['claims'] ?? $permissions['clients'] ?? 'none';
                if (! $hadClaimsPagePermission) {
                    $permissions['clients_claims'] = $legacyClaims;
                }
                if ($role->slug === 'compliance-specialist' && ! $hadClaimsPagePermission) {
                    $permissions['clients_claims'] = 'read';
                }

                DB::table('roles')->where('id', $role->id)->update([
                    'permissions' => json_encode($permissions),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('roles')->select(['id', 'permissions'])->orderBy('id')->chunkById(100, function ($roles): void {
            foreach ($roles as $role) {
                $permissions = json_decode($role->permissions ?: '{}', true) ?: [];
                unset($permissions['clients_claims']);

                DB::table('roles')->where('id', $role->id)->update([
                    'permissions' => json_encode($permissions),
                ]);
            }
        });
    }
};
