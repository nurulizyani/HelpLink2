<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cookie\CookieJar;
use Illuminate\Contracts\Cookie\QueueingFactory;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ✅ BETUL: bind QueueingFactory ke CookieJar
        $this->app->singleton(
            QueueingFactory::class,
            CookieJar::class
        );
    }

    public function boot(): void
    {
        //
    }
}
