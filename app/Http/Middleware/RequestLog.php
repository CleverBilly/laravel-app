<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\LogService;

class RequestLog
{
    protected LogService $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // 记录请求
        $this->logService->apiRequest($request);

        // 处理请求
        $response = $next($request);

        // 计算响应时间
        $responseTime = (microtime(true) - $startTime) * 1000; // 转换为毫秒

        // 记录响应
        $this->logService->apiResponse($response, [
            'response_time' => round($responseTime, 2) . 'ms',
        ]);

        // 记录性能数据（如果响应时间过长）
        if ($responseTime > 1000) {
            $this->logService->performance('API Request', $responseTime, [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
            ]);
        }

        return $response;
    }
}

