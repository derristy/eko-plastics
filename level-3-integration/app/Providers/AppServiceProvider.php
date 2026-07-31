<?php

namespace App\Providers;

use App\Services\Dilovod;
use App\Services\SalesDrive;
use App\Services\Telegram;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SalesDrive::class, fn() => new SalesDrive(
            config('integrations.salesdrive.url'),
            config('integrations.salesdrive.api_key'),
        ));

        $this->app->singleton(Dilovod::class, fn() => new Dilovod(
            config('integrations.dilovod.url'),
            config('integrations.dilovod.key'),
            config('integrations.dilovod.person_type'),
            config('integrations.dilovod.group'),
        ));

        $this->app->singleton(Telegram::class, fn() => new Telegram(
            config('integrations.telegram.token'),
            config('integrations.telegram.chat_id'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
