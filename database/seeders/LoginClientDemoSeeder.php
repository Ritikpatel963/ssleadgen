<?php

namespace Database\Seeders;

use App\Models\Client;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class LoginClientDemoSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        for ($number = 1; $number <= 50; $number++) {
            $policyNumber = sprintf('DEMO-LC-%04d', $number);

            Client::firstOrCreate(
                ['policy_no' => $policyNumber],
                [
                    'first_name' => $faker->firstName(),
                    'last_name' => $faker->lastName(),
                    'email' => sprintf('login-demo-%02d@example.test', $number),
                    'phone' => sprintf('+64 21 %06d', $faker->numberBetween(100000, 999999)),
                    'dob' => $faker->dateTimeBetween('-75 years', '-20 years')->format('Y-m-d'),
                    'address' => $faker->streetAddress(),
                    'suburb' => $faker->city(),
                    'city' => $faker->city(),
                    'post_code' => $faker->postcode(),
                    'status' => 'Login Client',
                    'company' => $faker->company(),
                    'login_date' => $faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
                    'anp' => $faker->randomFloat(2, 600, 20000),
                    'adviser' => 'Demo Adviser',
                    'not_counting' => false,
                    'compliance_by' => 'Demo Compliance',
                    'roa_due_date' => $faker->dateTimeBetween('now', '+90 days')->format('Y-m-d'),
                    'status_compliance' => $faker->randomElement(['Pending', 'In Progress', 'Complete']),
                    'sent_to_client' => $faker->randomElement(['Pending', 'Yes', 'No']),
                    'outcome' => $faker->sentence(),
                    'notes' => 'DEMO DATA: generated for Login Client screen testing.',
                ]
            );
        }

        $this->command?->info('Ensured 50 demo Login Client records.');
    }
}
