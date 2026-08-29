<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$file = __DIR__ . '/test_employee.csv';

if (!file_exists($file)) {
    die("CSV file not found: " . $file);
}

$handle = fopen($file, 'r');

$headers = fgetcsv($handle, 0, ",");

if (!$headers) {
    die("CSV header row not found.");
}

$headers = array_map(function ($header) {
    return trim(str_replace("\xEF\xBB\xBF", '', $header));
}, $headers);

$dateColumns = [
    'date_of_joining',
    'date_of_confirmation',
    'deleted_at',
    'created_at',
    'updated_at',
    'date_of_birth',
    'resign_date',
];

DB::beginTransaction();

try {
    $count = 0;

    while (($row = fgetcsv($handle, 0, ",")) !== false) {

        if (count($headers) !== count($row)) {
            echo "Skipped row. Header count: " . count($headers) . " Row count: " . count($row) . PHP_EOL;
            continue;
        }

        $data = array_combine($headers, $row);

        foreach ($data as $key => $value) {
            $data[$key] = trim($value) === '' ? null : trim($value);
        }

        unset($data['id'], $data['company_id'], $data['created_at'], $data['updated_at']);

        foreach ($dateColumns as $column) {
            if (!empty($data[$column])) {
                $data[$column] = Carbon::parse($data[$column])->format('Y-m-d');
            }
        }

        $data['company_id'] = 93;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::table('employees')->insert($data);

        $count++;
    }

    DB::commit();
    fclose($handle);

    echo "Employees imported successfully. Total: {$count}\n";
} catch (Throwable $e) {
    DB::rollBack();
    fclose($handle);

    echo "Import failed:\n";
    echo $e->getMessage() . "\n";
}
