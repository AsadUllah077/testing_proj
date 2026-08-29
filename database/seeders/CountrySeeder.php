<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌍 Fetching global countries list...');

        $url = 'https://raw.githubusercontent.com/dr5hn/countries-states-cities-database/master/json/countries.json';
        $response = Http::timeout(30)->get($url);

        if ($response->failed()) {
            $this->command->error('❌ Failed to fetch countries data from GitHub.');

            return;
        }

        $countries = $response->json();

        // Fetch existing ISO2 codes only once
        $existingCodes = Country::pluck('code')->toArray();
        $now = now();

        $toInsert = [];
        $toUpdate = [];

        foreach ($countries as $country) {
            $code = $country['iso2'];
            $data = [
                'name' => $country['name'],
                'status' => 1,
                'updated_at' => $now,
            ];

            if (in_array($code, $existingCodes)) {
                $toUpdate[$code] = $data;
            } else {
                $toInsert[] = [
                    'code' => $code,
                    'name' => $country['name'],
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Bulk Insert
        if (! empty($toInsert)) {
            Country::insert($toInsert);
        }

        // Bulk Update using Query Builder (better for large sets)
        foreach ($toUpdate as $code => $data) {
            Country::where('code', $code)->update($data);
        }

        $inserted = count($toInsert);
        $updated = count($toUpdate);

        $this->command->info("🚀 Done! Inserted: {$inserted}, Updated: {$updated}");
    }
}
