<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        // 本地环境允许所有访问
        if (app()->environment('local')) {
            Gate::define('viewHorizon', function ($user = null) {
                return true;
            });
            return;
        }

        // 生产环境需要认证
        Gate::define('viewHorizon', function ($user = null) {
            // 这里可以添加权限检查逻辑
            // 例如：检查用户角色、IP 白名单等
            return $user !== null;
        });
    }
}
