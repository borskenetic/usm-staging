<?php

namespace App\Providers;

use App\Services\BrandingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BrandingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share branding once per request. A composer('*') that re-resolves on every
        // Blade include previously hammered database cache during sidebar dashboards.
        View::composer('*', function (): void {
            static $shared = false;
            if ($shared) {
                return;
            }
            $shared = true;

            View::share(app(BrandingService::class)->viewShares());
        });

        // Set PHP's default timezone to Asia/Manila
        date_default_timezone_set(Config::get('app.timezone'));

        // Optional: Set Carbon locale if you use translated dates
        Carbon::setLocale(Config::get('app.locale'));

        // Hostinger / shared hosting: ensure generated asset() URLs use HTTPS in production
        // (prevents mixed-content and \"unstyled\" pages when APP_URL is mis-set to http://)
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
