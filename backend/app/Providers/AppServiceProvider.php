<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * daftarkan service aplikasi
     */
    public function register(): void
    {
        //
    }

    /**
     * bootstrap service aplikasi
     */
    public function boot(): void
    {
        // cegah lazy loading di non-production untuk deteksi dini query N+1
        Model::preventLazyLoading(! $this->app->isProduction());

        // unguard model secara global untuk mass assignment bersih dengan validated FormRequest
        Model::unguard();

        // batasi percobaan pembukaan sesi kasir per perangkat POS dan kasir
        RateLimiter::for('pos-session-open', function (Request $request): Limit {
            $deviceToken = (string) ($request->header('X-Device-Token') ?: $request->bearerToken() ?: $request->ip());
            $cashierId = (string) $request->input('cashier_user_id', '');

            return Limit::perMinute(5)->by($deviceToken . '|' . $cashierId);
        });
    }
}
