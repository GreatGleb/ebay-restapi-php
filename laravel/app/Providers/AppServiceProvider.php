<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Gemini\Gemini;
use Gemini\Client;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, function () {
            return \Gemini::client(config('services.gemini.key'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
