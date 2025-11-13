<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * 不需要报告的异常类型
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        ValidationException::class,
        AuthenticationException::class,
    ];

    /**
     * 不需要闪存的输入键
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'token',
        'api_key',
        'secret',
    ];

    /**
     * 注册异常处理回调
     */
    public function register(): void
    {
        // 统一记录所有需要报告的异常
        $this->reportable(function (Throwable $e) {
            // 记录详细的异常信息
            logger_exception($e, [
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'user_id' => auth()->id(),
                'user_agent' => request()->userAgent(),
            ]);

            // 发送到外部监控服务（如果配置了）
            if (app()->bound('sentry') && config('services.sentry.enabled')) {
                app('sentry')->captureException($e);
            }
        });

        // JSON API 异常渲染
        $this->renderable(function (Throwable $e, $request) {
            if ($request->expectsJson()) {
                return $this->renderJsonException($e, $request);
            }
        });
    }

    /**
     * 渲染 JSON 格式的异常响应
     */
    protected function renderJsonException(Throwable $e, $request)
    {
        // 自定义异常直接返回（已实现 render 方法）
        if (method_exists($e, 'render')) {
            return $e->render($request);
        }

        // Laravel 验证异常
        if ($e instanceof LaravelValidationException) {
            return api_error('验证失败', 422, $e->errors());
        }

        // 认证异常
        if ($e instanceof AuthenticationException) {
            return api_error('未授权访问', 401);
        }

        // 模型未找到异常
        if ($e instanceof ModelNotFoundException) {
            return api_error('资源不存在', 404);
        }

        // 404 异常
        if ($e instanceof NotFoundHttpException) {
            return api_error('请求的资源不存在', 404);
        }

        // 方法不允许异常
        if ($e instanceof MethodNotAllowedHttpException) {
            return api_error('请求方法不允许', 405);
        }

        // 获取状态码
        $statusCode = $this->getStatusCode($e);

        // 生产环境隐藏详细错误信息
        if (app()->environment('production') && $statusCode >= 500) {
            return api_error('服务器内部错误，请稍后重试', $statusCode);
        }

        // 开发环境返回详细信息
        $message = $e->getMessage() ?: $this->getDefaultMessage($statusCode);
        $debug = app()->environment('production') ? null : [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => collect($e->getTrace())->take(5)->map(function ($trace) {
                return [
                    'file' => $trace['file'] ?? 'unknown',
                    'line' => $trace['line'] ?? 0,
                    'function' => $trace['function'] ?? 'unknown',
                ];
            })->toArray(),
        ];

        return api_error($message, $statusCode, $debug);
    }

    /**
     * 获取异常状态码
     */
    protected function getStatusCode(Throwable $e): int
    {
        // 如果异常有 getStatusCode 方法
        if (method_exists($e, 'getStatusCode')) {
            return $e->getStatusCode();
        }

        // 如果异常有 getCode 方法且返回有效的 HTTP 状态码
        if (method_exists($e, 'getCode')) {
            $code = $e->getCode();
            if (is_int($code) && $code >= 100 && $code < 600) {
                return $code;
            }
        }

        // 默认返回 500
        return 500;
    }

    /**
     * 获取默认错误消息
     */
    protected function getDefaultMessage(int $statusCode): string
    {
        return match ($statusCode) {
            400 => '请求参数错误',
            401 => '未授权访问',
            403 => '禁止访问',
            404 => '请求的资源不存在',
            405 => '请求方法不允许',
            422 => '请求参数验证失败',
            429 => '请求过于频繁',
            500 => '服务器内部错误',
            502 => '网关错误',
            503 => '服务暂时不可用',
            504 => '网关超时',
            default => '请求处理失败',
        };
    }

    /**
     * 转换认证异常为 JSON 响应
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return $request->expectsJson()
            ? api_error('未授权访问', 401)
            : redirect()->guest(route('login'));
    }
}

