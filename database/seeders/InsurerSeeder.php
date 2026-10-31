<?php

namespace Database\Seeders;

use App\Models\Insurer;
use Illuminate\Database\Seeder;

class InsurerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $insurers = [
            ['name' => 'AIA Health', 'is_active' => true],
            ['name' => 'AIA Living', 'is_active' => true],
            ['name' => 'Partners Life', 'is_active' => true],
            ['name' => 'Chubb Life', 'is_active' => true],
            ['name' => 'Asteron Life', 'is_active' => true],
            ['name' => 'Fidelity Life', 'is_active' => true],
            ['name' => 'NIB', 'is_active' => true],
        ];

        foreach ($insurers as $insurer) {
            Insurer::firstOrCreate(['name' => $insurer['name']], $insurer);
        }
    }
}
