<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Prevent lazy loading in non-production to catch N+1 queries early
        Model::preventLazyLoading(! $this->app->isProduction());

        // Unguard models globally for clean mass assignment with validated FormRequests
        Model::unguard();
    }
}
