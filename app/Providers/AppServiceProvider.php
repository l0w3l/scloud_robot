<?php

namespace App\Providers;

use App\Auth\TelegramGuard;
use Illuminate\Support\Facades\Auth;
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
        Auth::extend('telegram', function ($app, $name, array $config) {
            return new TelegramGuard(
                Auth::createUserProvider($config['provider']),
                $app['request']
            );
        });
    }
}
