<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * 数据库查询日志中间件
 *
 * 记录慢查询和查询过多的请求
 */
class QueryLogger
{
    /**
     * 慢查询阈值（毫秒）
     *
     * @var int
     */
    protected int $slowQueryThreshold;

    /**
     * 查询数量阈值
     *
     * @var int
     */
    protected int $queryCountThreshold;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->slowQueryThreshold = config('database.slow_query_threshold', 100);
        $this->queryCountThreshold = config('database.query_count_threshold', 20);
    }

    /**
     * 处理请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 只在非生产环境或开启了调试模式时启用
        if (!config('app.debug') && app()->environment('production')) {
            return $next($request);
        }

        // 启用查询日志
        DB::enableQueryLog();

        $response = $next($request);

        // 记录慢查询
        $this->logSlowQueries($request);

        // 记录查询过多的请求
        $this->logExcessiveQueries($request);

        return $response;
    }

    /**
     * 记录慢查询
     *
     * @param Request $request
     * @return void
     */
    protected function logSlowQueries(Request $request): void
    {
        $queries = DB::getQueryLog();
        
        foreach ($queries as $query) {
            if ($query['time'] > $this->slowQueryThreshold) {
                logger_warning('慢查询检测', [
                    'sql' => $query['query'],
                    'bindings' => $query['bindings'],
                    'time' => $query['time'] . 'ms',
                    'threshold' => $this->slowQueryThreshold . 'ms',
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                ], 'database');
            }
        }
    }

    /**
     * 记录查询过多的请求
     *
     * @param Request $request
     * @return void
     */
    protected function logExcessiveQueries(Request $request): void
    {
        $queries = DB::getQueryLog();
        $count = count($queries);
        
        if ($count > $this->queryCountThreshold) {
            logger_warning('单次请求查询过多', [
                'count' => $count,
                'threshold' => $this->queryCountThreshold,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'queries' => collect($queries)->take(10)->map(function ($query) {
                    return [
                        'sql' => $query['query'],
                        'time' => $query['time'] . 'ms',
                    ];
                })->toArray(),
            ], 'database');
        }
    }
}

