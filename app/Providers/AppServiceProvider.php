<?php

namespace App\Providers;

use App\Models\AppSetting;
use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
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
        User::observe(UserObserver::class);

        if (Schema::hasTable('app_settings')) {
            $settings = AppSetting::current();
            $settings->applyMailConfig();
            $settings->applyMetaConfig();
        }

        View::composer('layouts.app', function ($view) {
            $settings = AppSetting::current();
            $view->with([
                'appBrandName' => $settings->brand_name,
                'appBrandLogoUrl' => $settings->logoPublicUrl(),
                'appBrandFaviconUrl' => $settings->faviconPublicUrl(),
                'appThemeStyle' => $settings->themeStyleTag(),
            ]);
        });

        View::composer('layouts.guest', function ($view) {
            $settings = AppSetting::current();
            $view->with([
                'appBrandName' => $settings->brand_name,
                'appBrandLogoUrl' => $settings->logoPublicUrl(),
                'appBrandFaviconUrl' => $settings->faviconPublicUrl(),
                'appThemeStyle' => $settings->themeStyleTag(),
            ]);
        });
    }
}
