<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class LogService
{
    /**
     * 记录调试信息
     *
     * @param string $message
     * @param array $context
     * @param string|null $channel
     * @return void
     */
    public function debug(string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('debug', $message, $context, $channel);
    }

    /**
     * 记录信息
     *
     * @param string $message
     * @param array $context
     * @param string|null $channel
     * @return void
     */
    public function info(string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('info', $message, $context, $channel);
    }

    /**
     * 记录警告
     *
     * @param string $message
     * @param array $context
     * @param string|null $channel
     * @return void
     */
    public function warning(string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('warning', $message, $context, $channel);
    }

    /**
     * 记录错误
     *
     * @param string $message
     * @param array $context
     * @param string|null $channel
     * @return void
     */
    public function error(string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('error', $message, $context, $channel);
    }

    /**
     * 记录严重错误
     *
     * @param string $message
     * @param array $context
     * @param string|null $channel
     * @return void
     */
    public function critical(string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('critical', $message, $context, $channel);
    }

    /**
     * 记录紧急情况
     *
     * @param string $message
     * @param array $context
     * @param string|null $channel
     * @return void
     */
    public function emergency(string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('emergency', $message, $context, $channel);
    }

    /**
     * 记录 API 请求
     *
     * @param \Illuminate\Http\Request $request
     * @param array $extra
     * @return void
     */
    public function apiRequest($request, array $extra = []): void
    {
        $this->info('API Request', array_merge([
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => Auth::check() ? Auth::id() : null,
            'params' => $this->sanitizeData($request->all()),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
        ], $extra), 'api');
    }

    /**
     * 记录 API 响应
     *
     * @param \Illuminate\Http\Response|\Illuminate\Http\JsonResponse $response
     * @param array $extra
     * @return void
     */
    public function apiResponse($response, array $extra = []): void
    {
        $statusCode = $response->getStatusCode();
        $level = $statusCode >= 400 ? 'error' : 'info';

        $this->log($level, 'API Response', array_merge([
            'status_code' => $statusCode,
            'response_time' => $extra['response_time'] ?? null,
        ], $extra), 'api');
    }

    /**
     * 记录数据库查询
     *
     * @param string $query
     * @param array $bindings
     * @param float $time
     * @return void
     */
    public function query(string $query, array $bindings = [], float $time = 0): void
    {
        $this->debug('Database Query', [
            'query' => $query,
            'bindings' => $bindings,
            'time' => $time . 'ms',
        ], 'database');
    }

    /**
     * 记录业务操作
     *
     * @param string $action
     * @param array $data
     * @param int|null $userId
     * @return void
     */
    public function business(string $action, array $data = [], ?int $userId = null): void
    {
        $this->info('Business Action', [
            'action' => $action,
            'user_id' => $userId ?? (Auth::check() ? Auth::id() : null),
            'data' => $this->sanitizeData($data),
            'ip' => request()->ip(),
        ], 'business');
    }

    /**
     * 记录性能数据
     *
     * @param string $operation
     * @param float $duration
     * @param array $context
     * @return void
     */
    public function performance(string $operation, float $duration, array $context = []): void
    {
        $level = $duration > 1000 ? 'warning' : 'info';

        $this->log($level, 'Performance', array_merge([
            'operation' => $operation,
            'duration' => $duration . 'ms',
        ], $context), 'performance');
    }

    /**
     * 记录异常
     *
     * @param \Throwable $exception
     * @param array $context
     * @return void
     */
    public function exception(\Throwable $exception, array $context = []): void
    {
        $this->error('Exception', array_merge([
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'code' => $exception->getCode(),
        ], $context), 'exception');
    }

    /**
     * 记录日志
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @param string|null $channel
     * @return void
     */
    protected function log(string $level, string $message, array $context = [], ?string $channel = null): void
    {
        $logger = $channel ? Log::channel($channel) : Log::getFacadeRoot();

        // 添加通用上下文
        $context = array_merge([
            'timestamp' => now()->toDateTimeString(),
            'request_id' => request()->header('X-Request-ID') ?? uniqid('req_', true),
        ], $context);

        $logger->{$level}($message, $context);
    }

    /**
     * 清理敏感数据
     *
     * @param array $data
     * @return array
     */
    protected function sanitizeData(array $data): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'api_key', 'secret', 'credit_card', 'cvv'];

        foreach ($sensitiveKeys as $key) {
            if (isset($data[$key])) {
                $data[$key] = '***HIDDEN***';
            }
        }

        return $data;
    }

    /**
     * 清理敏感请求头
     *
     * @param array $headers
     * @return array
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key'];

        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['***HIDDEN***'];
            }
        }

        return $headers;
    }
}

