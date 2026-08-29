<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $companyIds = Company::pluck('id')->toArray();

        if (empty($companyIds)) {
            $this->command->warn('⚠️ No companies found. Seed companies first.');

            return;
        }

        $users = [];
        $password = Hash::make('password');
        $now = now();

        for ($i = 0; $i < 100; $i++) {
            $firstName = $faker->firstName;
            $lastName = $faker->lastName;
            $username = strtolower($firstName.'.'.$lastName.$faker->numberBetween(1, 99));

            $users[] = [
                'company_id' => $faker->randomElement($companyIds),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $faker->unique()->safeEmail,
                'phone_number' => $faker->unique()->e164PhoneNumber,
                'username' => $username,
                'profile_pic' => 'default.png',
                'designation' => $faker->jobTitle,
                'password' => $password,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Bulk Insert (1 query instead of 100)
        User::insert($users);

        $this->command->info('🚀 100 users inserted successfully in bulk!');
    }
}
