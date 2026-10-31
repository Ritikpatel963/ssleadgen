<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class ReportFakeDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        // 1. Create Lead Sources if they don't exist
        $sources = [
            'Web Form',
            'Referral',
            'LinkedIn Ads',
            'Cold Call',
            'Event',
        ];

        $sourceIds = [];
        foreach ($sources as $source) {
            $ls = LeadSource::firstOrCreate(['name' => $source]);
            $sourceIds[] = $ls->id;
        }

        // 2. Fetch all existing users to act as advisors
        $users = User::all();
        if ($users->isEmpty()) {
            $this->command->info('No users found. Creating fake users...');
            $users = collect();
            for ($i = 0; $i < 4; $i++) {
                $users->push(User::create([
                    'name' => $faker->name,
                    'email' => $faker->unique()->safeEmail,
                    'password' => bcrypt('password'),
                ]));
            }
        }

        $userIds = $users->pluck('id')->toArray();

        // Generate data for the last 6 months
        $this->command->info('Generating fake Leads and Clients for the last 6 months...');

        // 3. Generate fake Leads (unconverted)
        for ($i = 0; $i < 150; $i++) {
            $createdAt = Carbon::now()->subDays(rand(0, 180));
            Lead::create([
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'email' => $faker->unique()->safeEmail,
                'phone' => $faker->phoneNumber,
                'lead_source_id' => $faker->randomElement($sourceIds),
                'user_id' => $faker->randomElement($userIds),
                'status' => $faker->randomElement(['New Lead', 'Contacted', 'Proposal Sent', 'Following Up']),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        // 4. Generate fake Clients (converted)
        $clientStatuses = ['Inforce', 'Claims', 'Inactive', 'Cancellation', 'NPW Deferred'];
        for ($i = 0; $i < 120; $i++) {
            $createdAt = Carbon::now()->subDays(rand(0, 180));
            Client::create([
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'email' => $faker->unique()->safeEmail,
                'phone' => $faker->phoneNumber,
                'lead_source_id' => $faker->randomElement($sourceIds),
                'user_id' => $faker->randomElement($userIds),
                'status' => $faker->randomElement($clientStatuses),
                'anp' => $faker->randomFloat(2, 500, 15000), // Annual Premium between 500 and 15000
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        $this->command->info('Fake data generated successfully!');
    }
}
