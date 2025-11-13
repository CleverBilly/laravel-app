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

        // 注册队列管理器
        $this->app->singleton(\App\Queue\QueueManager::class, function ($app) {
            $queueConfig = config('queue', []);
            $config = [
                'default' => $queueConfig['driver'] ?? 'redis',
                'drivers' => $queueConfig['driver_config'] ?? [],
            ];
            return new \App\Queue\QueueManager($config);
        });

        // 注册队列服务
        $this->app->singleton(\App\Services\QueueService::class, function ($app) {
            $manager = $app->make(\App\Queue\QueueManager::class);
            return new \App\Services\QueueService($manager);
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
