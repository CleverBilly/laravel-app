<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt.auth' => \App\Http\Middleware\JwtAuthMiddleware::class,
            'request.log' => \App\Http\Middleware\RequestLog::class,
        ]);

        // API 路由自动记录日志
        $middleware->api(prepend: [
            \App\Http\Middleware\RequestLog::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API 请求异常处理
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            // 只处理 API 请求
            if (!$request->expectsJson() && !$request->is('api/*')) {
                return null;
            }

            // 自定义业务异常
            if ($e instanceof \App\Exceptions\BusinessException) {
                return $e->render($request);
            }

            // JWT 异常处理
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                return api_error('Token 已过期', 401);
            }

            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return api_error('Token 无效', 401);
            }

            if ($e instanceof \Tymon\JWTAuth\Exceptions\JWTException) {
                return api_error('Token 缺失', 401);
            }

            // Laravel 验证异常
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return api_error(
                    $e->getMessage() ?: '验证失败',
                    422,
                    $e->errors()
                );
            }

            // 模型未找到异常
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return api_error('资源不存在', 404);
            }

            // 路由未找到异常
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return api_error('接口不存在', 404);
            }

            // 方法不允许异常
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                return api_error('请求方法不允许', 405);
            }

            // 认证异常
            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return api_error('未授权访问', 401);
            }

            // 授权异常
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return api_error('禁止访问', 403);
            }

            // 其他异常
            // 生产环境不显示详细错误信息
            $message = app()->environment('production')
                ? '服务器内部错误'
                : $e->getMessage();

            // 记录异常日志
            \Illuminate\Support\Facades\Log::error('Exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return api_error($message, 500, [
                'file' => app()->environment('production') ? null : $e->getFile(),
                'line' => app()->environment('production') ? null : $e->getLine(),
            ]);
        });
    })->create();
