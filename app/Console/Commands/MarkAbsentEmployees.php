<?php

namespace App\Console\Commands;

use App\Jobs\MarkAbsentEmployeesJob;
use Illuminate\Console\Command;

class MarkAbsentEmployees extends Command
{
    protected $signature = 'attendance:mark-absent
                            {--date= : The date to process (Y-m-d), defaults to today}';

    protected $description = 'Dispatch a job to mark absent employees and create pending annual leave records';

    public function handle(): void
    {
        $date = $this->option('date') ?? null;

        MarkAbsentEmployeesJob::dispatch($date);

        $this->info('MarkAbsentEmployeesJob dispatched for ' . ($date ?? 'today') . '.');
    }
}
