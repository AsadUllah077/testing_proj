<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Country;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create();
        $countryIds = Country::pluck('id')->toArray();

        if (empty($countryIds)) {
            $this->command->warn('⚠️ No countries found. Please seed the countries table first.');

            return;
        }

        $companies = [];

        for ($i = 0; $i < 100; $i++) {
            $companies[] = [
                'country_id' => $faker->randomElement($countryIds),
                'name' => $faker->company,
                'phone' => $faker->phoneNumber,
                'mobile' => $faker->e164PhoneNumber,
                'email' => $faker->unique()->safeEmail,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Company::insert($companies);

        $this->command->info('🚀 100 companies seeded successfully (Bulk Insert)!');
    }
}
