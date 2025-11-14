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
        Gate::define('viewHorizon', function ($user = null) {
            // 本地环境允许所有访问
            if (app()->environment('local')) {
                return true;
            }

            // 测试环境允许所有认证用户访问
            if (app()->environment('testing')) {
                return $user !== null;
            }

            // 生产环境访问控制
            // 方式1: IP 白名单(通过环境变量配置)
            $allowedIps = explode(',', env('HORIZON_ALLOWED_IPS', ''));
            if (!empty($allowedIps) && in_array(request()->ip(), $allowedIps)) {
                return true;
            }

            // 方式2: 检查用户是否已认证
            if ($user === null) {
                return false;
            }

            // 方式3: 检查用户权限(如果你有角色系统)
            // 例如：仅允许管理员访问
            // return in_array($user->email, [
            //     'admin@example.com',
            //     'developer@example.com',
            // ]);

            // 方式4: 检查用户角色字段(如果数据库中有 role 字段)
            // return $user->role === 'admin';

            // 默认拒绝访问
            return false;
        });
    }
}
