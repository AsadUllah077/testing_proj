<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use App\Jobs\MarkAbsentEmployeesJob;
use App\Jobs\PayrollAutoPostJob;
use Illuminate\Support\Facades\Schedule;

// ── HR / Payroll ─────────────────────────────────────────────────────────────
// Generate and post payroll on the 1st of each month at midnight
// Schedule::command('payroll:auto-post')->monthlyOn(1, '00:00');
Schedule::job(new PayrollAutoPostJob())->monthlyOn(1, '00:00');

// Mark absent employees daily at 15:00
// Mark absent employees 6 hours after the standard 09:00 shift start (runs daily at 15:00)
// Schedule::command('attendance:mark-absent')->dailyAt('15:00');
Schedule::job(new MarkAbsentEmployeesJob())->dailyAt('15:00');

// for the above two job can run the command php artisan schedule:work

// for the commented schedule run the command php artisan attendance:mark-absent, php artisan payroll:auto-post

// ── Accounting Automation ─────────────────────────────────────────────────────
// Send overdue invoice payment reminders daily at 09:00
// Triggers for invoices 7, 14, and 30 days overdue (Net-30 terms)
Schedule::command('invoices:send-overdue-reminders --days=7,14,30')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/overdue-reminders.log'));

// Auto-lock accounting periods 1 day after their end date (daily at 01:00)
Schedule::command('accounting:auto-lock-periods --grace-days=1')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/period-lock.log'));

// Generate recurring invoices daily at 07:00
Schedule::command('invoices:generate-recurring')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/recurring-invoices.log'));

// Generate recurring bills daily at 07:00
Schedule::command('bills:generate-recurring')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/recurring-bills.log'));

// Send monthly customer account statements on the 1st at 08:00
Schedule::command('account:send-customer-statements')
    ->monthlyOn(1, '08:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/customer-statements.log'));

// Post monthly asset depreciation on the last day of each month at 02:00
Schedule::command('assets:post-depreciation')
    ->lastDayOfMonth('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/asset-depreciation.log'));

// ── How to run ────────────────────────────────────────────────────────────────
// Start scheduler:              php artisan schedule:work
// Run overdue reminders:        php artisan invoices:send-overdue-reminders --dry-run
// Run period lock:              php artisan accounting:auto-lock-periods --dry-run
// Run recurring generation:     php artisan invoices:generate-recurring --dry-run
// Send customer statements:     php artisan account:send-customer-statements --dry-run
// Send to one customer:         php artisan account:send-customer-statements --customer=5 --dry-run
