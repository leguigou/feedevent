<?php

namespace App\Providers;

use App\Services\SettingManager;
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
    public function boot(SettingManager $settings): void
    {
        config([
            'app.name' => $settings->get('site.name', config('app.name')),
            'mail.from.address' => $settings->get('site.support_email', config('mail.from.address')),
        ]);
    }
}
