<?php

namespace App\Console\Commands;

use App\Jobs\PayrollAutoPostJob;
use Illuminate\Console\Command;

class PayrollAutoPost extends Command
{
    protected $signature = 'payroll:auto-post';

    protected $description = 'Dispatch a job to automatically generate and post payroll on the first of each month';

    public function handle(): void
    {
        PayrollAutoPostJob::dispatch();

        $this->info('PayrollAutoPostJob dispatched.');
    }
}
