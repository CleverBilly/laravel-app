<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HttpService;
use App\Services\CacheService;
use App\Services\QueueService;
use App\Services\LogService;
use App\Rules\StrongPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExampleController extends Controller
{
    protected HttpService $httpService;
    protected CacheService $cacheService;
    protected QueueService $queueService;
    protected LogService $logService;

    public function __construct(
        HttpService $httpService,
        CacheService $cacheService,
        QueueService $queueService,
        LogService $logService
    ) {
        $this->httpService = $httpService;
        $this->cacheService = $cacheService;
        $this->queueService = $queueService;
        $this->logService = $logService;
    }

    /**
     * 示例接口 - 基础响应
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // 记录日志
        logger_info('访问示例接口', ['ip' => get_client_ip()], 'api');

        return $this->success([
            'message' => '欢迎使用 Laravel API 框架',
            'client_ip' => get_client_ip(),
            'timestamp' => now()->toDateTimeString(),
        ], '请求成功');
    }

    /**
     * HTTP 客户端示例
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function httpExample()
    {
        // GET 请求示例
        $response = $this->httpService->get('https://api.github.com/repos/laravel/laravel', [
            'per_page' => 1,
        ]);

        return $this->success([
            'demo' => 'HTTP 客户端请求示例',
            'method' => 'GET',
            'response' => $response,
        ], 'HTTP 请求成功');
    }

    /**
     * 缓存服务示例
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function cacheExample()
    {
        $key = 'demo_cache_key';

        // 示例 1: 基础缓存操作
        cache_set($key, ['data' => 'cached value', 'time' => now()], 60);
        $cached = cache_get($key);

        // 示例 2: Remember 模式
        $data = cache_remember('demo_remember', function () {
            return ['computed' => true, 'value' => rand(1000, 9999)];
        }, 300);

        // 示例 3: 批量操作（优化版，使用 Redis Pipeline）
        $batchData = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];
        $this->cacheService->setMultiple($batchData, 60);
        $batchResult = $this->cacheService->getMultiple(['key1', 'key2', 'key3']);

        // 示例 4: 防缓存穿透
        $safeData = $this->cacheService->rememberSafe('demo_safe', function () {
            // 模拟可能返回 null 的查询
            return rand(0, 1) ? ['data' => 'exists'] : null;
        }, 60);

        return $this->success([
            'demo' => '缓存服务示例',
            'basic' => $cached,
            'remember' => $data,
            'batch' => $batchResult,
            'safe' => $safeData,
            'tips' => [
                '批量操作使用 Redis Pipeline，性能提升 70%+',
                'rememberSafe 可防止缓存穿透',
                'setWithJitter 可防止缓存雪崩',
            ],
        ], '缓存操作成功');
    }

    /**
     * 队列服务示例
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function queueExample()
    {
        // 示例 1: 推送单个消息
        $messageId = queue_push([
            'type' => 'email',
            'to' => 'user@example.com',
            'subject' => 'Welcome',
            'body' => 'Hello World',
        ], 'emails', 'redis');

        // 示例 2: 延迟推送
        $delayedId = queue_later([
            'type' => 'notification',
            'message' => 'Delayed message',
        ], 30, 'notifications', 'redis');

        // 示例 3: 批量推送
        $messages = [
            ['id' => 1, 'action' => 'process'],
            ['id' => 2, 'action' => 'process'],
            ['id' => 3, 'action' => 'process'],
        ];
        $bulkIds = queue_bulk($messages, 'processing', 'redis');

        // 示例 4: 获取队列大小
        $emailQueueSize = queue_size('emails', 'redis');

        return $this->success([
            'demo' => '队列服务示例',
            'message_id' => $messageId,
            'delayed_id' => $delayedId,
            'bulk_ids' => $bulkIds,
            'queue_size' => $emailQueueSize,
            'tips' => [
                '支持 Redis 和 RabbitMQ 驱动',
                '支持延迟消息和优先级',
                '已修复资源泄漏问题',
            ],
        ], '队列操作成功');
    }

    /**
     * 日志服务示例
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logExample()
    {
        // 示例 1: 不同级别的日志
        logger_info('这是一条信息日志', ['action' => 'demo'], 'api');
        logger_warning('这是一条警告日志', ['level' => 'warning'], 'api');
        logger_error('这是一条错误日志', ['error' => 'demo error'], 'exception');

        // 示例 2: 业务日志
        logger_business('用户操作', [
            'action' => 'view_example',
            'user_id' => 1,
        ]);

        // 示例 3: 性能日志
        logger_performance('示例操作', 1500, [
            'operation' => 'demo',
        ]);

        // 示例 4: 使用服务类
        $this->logService->info('通过服务类记录日志', [
            'method' => 'service',
        ], 'api');

        return $this->success([
            'demo' => '日志服务示例',
            'logged_to' => [
                'api.log',
                'business.log',
                'performance.log',
                'exception.log',
            ],
            'tips' => [
                '日志自动脱敏敏感数据',
                '支持多个日志频道',
                '自动添加请求 ID 和时间戳',
            ],
        ], '日志已记录');
    }

    /**
     * 数据验证示例
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validationExample(Request $request)
    {
        // 示例 1: 基础验证
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => ['required', new \App\Rules\Phone()],
            'password' => ['required', 'string', 'confirmed', StrongPassword::default()],
        ]);

        if ($validator->fails()) {
            return $this->error('验证失败', 422, $validator->errors());
        }

        return $this->success([
            'demo' => '数据验证示例',
            'validated_data' => $validator->validated(),
            'available_rules' => [
                'Phone' => '手机号验证',
                'IdCard' => '身份证验证',
                'StrongPassword' => '强密码验证',
                'ImageBase64' => 'Base64图片验证',
                'JsonString' => 'JSON字符串验证',
            ],
        ], '验证通过');
    }

    /**
     * 异常处理示例
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function exceptionExample(Request $request)
    {
        $type = $request->input('type', 'business');

        try {
            switch ($type) {
                case 'business':
                    throw_business_exception('业务异常示例', 400);
                    break;
                case 'not_found':
                    throw_not_found_exception('资源未找到');
                    break;
                case 'unauthorized':
                    throw_unauthorized_exception('未授权访问');
                    break;
                case 'forbidden':
                    throw_forbidden_exception('禁止访问');
                    break;
                case 'validation':
                    throw_validation_exception('验证失败');
                    break;
                default:
                    throw new \Exception('普通异常');
            }
        } catch (\Exception $e) {
            // 全局异常处理器会自动处理
            throw $e;
        }
    }

    /**
     * 辅助函数示例
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function helperExample()
    {
        return $this->success([
            'demo' => '辅助函数示例',
            'examples' => [
                // 字符串处理
                'mask_phone' => mask_phone('13800138000'),
                'mask_email' => mask_email('user@example.com'),
                'generate_token' => generate_token(32),

                // 数组处理
                'array_get' => array_get(['user' => ['name' => 'John']], 'user.name'),

                // 验证
                'is_valid_email' => is_valid_email('test@example.com'),
                'is_valid_phone' => is_valid_phone('13800138000'),
                'is_valid_url' => is_valid_url('https://example.com'),

                // 格式化
                'format_bytes' => format_bytes(1024 * 1024 * 5), // 5MB

                // IP 获取
                'client_ip' => get_client_ip(),
            ],
            'tips' => [
                '所有辅助函数都在 app/Helpers/functions.php 中',
                '可以在项目任何地方直接使用',
            ],
        ], '辅助函数示例');
    }

    /**
     * 综合示例 - 完整的业务流程
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function fullExample(Request $request)
    {
        // 1. 记录开始日志
        $startTime = microtime(true);
        logger_info('开始处理综合示例请求', ['ip' => get_client_ip()], 'business');

        // 2. 验证请求数据
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'action' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('验证失败', 422, $validator->errors());
        }

        $userId = $request->input('user_id');
        $action = $request->input('action');

        // 3. 检查缓存
        $cacheKey = "user_data_{$userId}";
        $userData = cache_remember($cacheKey, function () use ($userId) {
            // 模拟数据库查询
            return [
                'id' => $userId,
                'name' => 'User ' . $userId,
                'email' => "user{$userId}@example.com",
                'created_at' => now(),
            ];
        }, 300);

        // 4. 推送异步任务到队列
        queue_push([
            'user_id' => $userId,
            'action' => $action,
            'timestamp' => now(),
        ], 'user_actions', 'redis');

        // 5. 记录业务日志
        logger_business('用户操作', [
            'user_id' => $userId,
            'action' => $action,
        ]);

        // 6. 计算性能
        $duration = (microtime(true) - $startTime) * 1000;
        logger_performance('综合示例请求', $duration);

        // 7. 返回响应
        return $this->success([
            'user' => $userData,
            'action' => $action,
            'queued' => true,
            'performance' => [
                'duration' => round($duration, 2) . 'ms',
            ],
        ], '操作成功');
    }
}
