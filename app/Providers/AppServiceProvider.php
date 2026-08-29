<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (! env('APP_DEBUG')) {
            URL::forceScheme('https');
        }

        $this->app->singleton('ai.manager', function ($app) {
            return new \App\Services\AI\AiManager($app);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useTailwind();

        Relation::morphMap([
            'vendor' => \App\Models\Vendor::class,
            'employee' => \App\Models\Employee::class,
            'customer' => \App\Models\Customer::class,
        ]);

        // Register commands
        $this->commands([
            \App\Console\Commands\GenerateStatesArray::class,
            \App\Console\Commands\PayrollAutoPost::class,
            \App\Console\Commands\FixDatabaseSequences::class,
        ]);
    }
}
