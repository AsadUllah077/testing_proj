<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GenerateStatesArray extends Command
{
    protected $signature = 'generate:states-array';
    protected $description = 'Generate PHP $states array file for all countries and states worldwide';

    public function handle()
    {
        $url = 'https://raw.githubusercontent.com/dr5hn/countries-states-cities-database/master/json/states.json';
        $response = Http::get($url);

        if ($response->failed()) {
            $this->error('❌ Failed to fetch states data from GitHub.');
            return;
        }

        $states = $response->json();
        $phpArray = "<?php\n\n\$states = [\n";

        foreach ($states as $state) {
            $country = addslashes($state['country_name'] ?? '');
            $name = addslashes($state['name'] ?? '');
            $phpArray .= "    ['country' => '{$country}', 'name' => '{$name}', 'status' => 1],\n";
        }

        $phpArray .= "];\n";

        file_put_contents(database_path('seeders/full_states_array.php'), $phpArray);

        $this->info('✅ full_states_array.php generated successfully at database/seeders/full_states_array.php');
    }
}
