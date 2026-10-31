<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $allWrite = array_fill_keys(Role::modules(), 'write');

        Role::firstOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Administrator',
                'description' => 'Full system read/write access to all resources',
                'permissions' => $allWrite,
                'is_super_admin' => true,
            ]
        );

        Role::firstOrCreate(
            ['slug' => 'insurance-advisor'],
            [
                'name' => 'Insurance Advisor',
                'description' => 'Can manage assigned clients, leads, and policies',
                'permissions' => [
                    'dashboard' => 'read',
                    'clients' => 'write',
                    'clients_login' => 'write',
                    'clients_inforce' => 'write',
                    'clients_inactive' => 'write',
                    'clients_claims' => 'write',
                    'clients_cancellation' => 'write',
                    'clients_npw_deferred' => 'write',
                    'leads' => 'write',
                    'tasks' => 'write',
                    'calendar' => 'write',
                    'policies' => 'write',
                    'reports' => 'read',
                    'documents' => 'write',
                    'access' => 'none',
                ],
                'is_super_admin' => false,
            ]
        );

        Role::firstOrCreate(
            ['slug' => 'compliance-specialist'],
            [
                'name' => 'Compliance Specialist',
                'description' => 'Reviews compliance, claims, and audit documentation',
                'permissions' => [
                    'dashboard' => 'read',
                    'clients' => 'read',
                    'clients_login' => 'read',
                    'clients_inforce' => 'read',
                    'clients_inactive' => 'read',
                    'clients_claims' => 'read',
                    'clients_cancellation' => 'read',
                    'clients_npw_deferred' => 'read',
                    'leads' => 'none',
                    'tasks' => 'write',
                    'calendar' => 'read',
                    'policies' => 'read',
                    'reports' => 'write',
                    'documents' => 'read',
                    'access' => 'none',
                ],
                'is_super_admin' => false,
            ]
        );
    }
}
