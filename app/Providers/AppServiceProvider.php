<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 注册 HTTP 服务
        $this->app->singleton(\App\Services\HttpService::class, function ($app) {
            return new \App\Services\HttpService();
        });

        // 注册日志服务
        $this->app->singleton(\App\Services\LogService::class, function ($app) {
            return new \App\Services\LogService();
        });

        // 注册缓存服务
        $this->app->singleton(\App\Services\CacheService::class, function ($app) {
            return new \App\Services\CacheService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

    }
}
