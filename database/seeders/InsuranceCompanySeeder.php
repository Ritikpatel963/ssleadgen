<?php

namespace Database\Seeders;

use App\Models\InsuranceCompany;
use Illuminate\Database\Seeder;

class InsuranceCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            'AIA Health',
            'AIA Living',
            'Fidelity Life',
            'Chubb Life',
            'Partners Life',
            'Asteron Life',
            'NIB',
        ];

        foreach ($companies as $company) {
            InsuranceCompany::firstOrCreate(['name' => $company]);
        }
    }
}
