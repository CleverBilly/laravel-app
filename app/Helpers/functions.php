<?php

if (!function_exists('api_success')) {
    /**
     * API 成功响应辅助函数
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    function api_success($data = null, string $message = 'success', int $code = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->timestamp,
        ], $code);
    }
}

if (!function_exists('api_error')) {
    /**
     * API 错误响应辅助函数
     *
     * @param string $message
     * @param int $code
     * @param mixed $data
     * @return \Illuminate\Http\JsonResponse
     */
    function api_error(string $message = 'error', int $code = 400, $data = null): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->timestamp,
        ], $code);
    }
}

if (!function_exists('http_client')) {
    /**
     * 获取 HTTP 客户端实例
     *
     * @param array $config
     * @return \App\Services\HttpService
     */
    function http_client(array $config = []): \App\Services\HttpService
    {
        return app(\App\Services\HttpService::class, ['config' => $config]);
    }
}

if (!function_exists('generate_token')) {
    /**
     * 生成随机 token
     *
     * @param int $length
     * @return string
     */
    function generate_token(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }
}

if (!function_exists('mask_string')) {
    /**
     * 字符串脱敏
     *
     * @param string $string
     * @param int $start 开始位置
     * @param int $length 脱敏长度
     * @param string $mask 脱敏字符
     * @return string
     */
    function mask_string(string $string, int $start = 0, int $length = 0, string $mask = '*'): string
    {
        $strLength = mb_strlen($string);

        if ($length === 0) {
            $length = $strLength - $start;
        }

        if ($start >= $strLength || $start + $length > $strLength) {
            return $string;
        }

        $masked = mb_substr($string, 0, $start)
            . str_repeat($mask, $length)
            . mb_substr($string, $start + $length);

        return $masked;
    }
}

if (!function_exists('mask_phone')) {
    /**
     * 手机号脱敏
     *
     * @param string $phone
     * @return string
     */
    function mask_phone(string $phone): string
    {
        if (strlen($phone) !== 11) {
            return $phone;
        }

        return substr($phone, 0, 3) . '****' . substr($phone, 7);
    }
}

if (!function_exists('mask_email')) {
    /**
     * 邮箱脱敏
     *
     * @param string $email
     * @return string
     */
    function mask_email(string $email): string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        [$local, $domain] = explode('@', $email);
        $localLength = strlen($local);

        if ($localLength <= 2) {
            $maskedLocal = substr($local, 0, 1) . '*';
        } else {
            $maskedLocal = substr($local, 0, 1) . str_repeat('*', $localLength - 2) . substr($local, -1);
        }

        return $maskedLocal . '@' . $domain;
    }
}

if (!function_exists('format_bytes')) {
    /**
     * 格式化字节大小
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    function format_bytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

if (!function_exists('array_get')) {
    /**
     * 使用点号获取数组值
     *
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function array_get(array $array, string $key, $default = null)
    {
        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }

            $array = $array[$segment];
        }

        return $array;
    }
}

if (!function_exists('is_valid_url')) {
    /**
     * 验证 URL 是否有效
     *
     * @param string $url
     * @return bool
     */
    function is_valid_url(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

if (!function_exists('is_valid_email')) {
    /**
     * 验证邮箱是否有效
     *
     * @param string $email
     * @return bool
     */
    function is_valid_email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('is_valid_phone')) {
    /**
     * 验证手机号是否有效（中国大陆）
     *
     * @param string $phone
     * @return bool
     */
    function is_valid_phone(string $phone): bool
    {
        return preg_match('/^1[3-9]\d{9}$/', $phone) === 1;
    }
}

if (!function_exists('get_client_ip')) {
    /**
     * 获取客户端 IP 地址
     *
     * @return string
     */
    function get_client_ip(): string
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

if (!function_exists('array_to_tree')) {
    /**
     * 将数组转换为树形结构
     *
     * @param array $items
     * @param string $idKey
     * @param string $parentKey
     * @param string $childrenKey
     * @return array
     */
    function array_to_tree(array $items, string $idKey = 'id', string $parentKey = 'parent_id', string $childrenKey = 'children'): array
    {
        $tree = [];
        $indexed = [];

        // 建立索引
        foreach ($items as $item) {
            $indexed[$item[$idKey]] = $item;
            $indexed[$item[$idKey]][$childrenKey] = [];
        }

        // 构建树
        foreach ($indexed as $item) {
            $parentId = $item[$parentKey] ?? 0;
            if ($parentId && isset($indexed[$parentId])) {
                $indexed[$parentId][$childrenKey][] = &$indexed[$item[$idKey]];
            } else {
                $tree[] = &$indexed[$item[$idKey]];
            }
        }

        return $tree;
    }
}

if (!function_exists('throw_business_exception')) {
    /**
     * 抛出业务异常
     *
     * @param string $message
     * @param int $code
     * @param mixed $data
     * @throws \App\Exceptions\BusinessException
     */
    function throw_business_exception(string $message = '业务处理失败', int $code = 400, $data = null): void
    {
        throw new \App\Exceptions\BusinessException($message, $code, $data);
    }
}

if (!function_exists('throw_not_found_exception')) {
    /**
     * 抛出资源未找到异常
     *
     * @param string $message
     * @param mixed $data
     * @throws \App\Exceptions\NotFoundException
     */
    function throw_not_found_exception(string $message = '资源不存在', $data = null): void
    {
        throw new \App\Exceptions\NotFoundException($message, $data);
    }
}

if (!function_exists('throw_unauthorized_exception')) {
    /**
     * 抛出未授权异常
     *
     * @param string $message
     * @param mixed $data
     * @throws \App\Exceptions\UnauthorizedException
     */
    function throw_unauthorized_exception(string $message = '未授权访问', $data = null): void
    {
        throw new \App\Exceptions\UnauthorizedException($message, $data);
    }
}

if (!function_exists('throw_forbidden_exception')) {
    /**
     * 抛出禁止访问异常
     *
     * @param string $message
     * @param mixed $data
     * @throws \App\Exceptions\ForbiddenException
     */
    function throw_forbidden_exception(string $message = '禁止访问', $data = null): void
    {
        throw new \App\Exceptions\ForbiddenException($message, $data);
    }
}

if (!function_exists('throw_validation_exception')) {
    /**
     * 抛出验证异常
     *
     * @param string $message
     * @param \Illuminate\Contracts\Validation\Validator|null $validator
     * @param mixed $data
     * @throws \App\Exceptions\ValidationException
     */
    function throw_validation_exception(string $message = '验证失败', ?\Illuminate\Contracts\Validation\Validator $validator = null, $data = null): void
    {
        throw new \App\Exceptions\ValidationException($message, $validator, $data);
    }
}

if (!function_exists('throw_service_exception')) {
    /**
     * 抛出服务异常
     *
     * @param string $message
     * @param int $code
     * @param mixed $data
     * @throws \App\Exceptions\ServiceException
     */
    function throw_service_exception(string $message = '服务处理失败', int $code = 500, $data = null): void
    {
        throw new \App\Exceptions\ServiceException($message, $code, $data);
    }
}

if (!function_exists('logger_debug')) {
    /**
     * 记录调试日志
     *
     * @param string $message
     * @param array $context
     * @param string|null $channel
     * @return void
     */
    function logger_debug(string $message, array $context = [], ?string $channel = null): void
    {
        app(\App\Services\LogService::class)->debug($message, $context, $channel);
    }
}

if (!function_exists('logger_info')) {
    /**
     * 记录信息日志
     *
     * @param string $message
     * @param array $context
     * @param string|null $channel
     * @return void
     */
    function logger_info(string $message, array $context = [], ?string $channel = null): void
    {
        app(\App\Services\LogService::class)->info($message, $context, $channel);
    }
}

if (!function_exists('logger_warning')) {
    /**
     * 记录警告日志
     *
     * @param string $message
     * @param array $context
     * @param string|null $channel
     * @return void
     */
    function logger_warning(string $message, array $context = [], ?string $channel = null): void
    {
        app(\App\Services\LogService::class)->warning($message, $context, $channel);
    }
}

if (!function_exists('logger_error')) {
    /**
     * 记录错误日志
     *
     * @param string $message
     * @param array $context
     * @param string|null $channel
     * @return void
     */
    function logger_error(string $message, array $context = [], ?string $channel = null): void
    {
        app(\App\Services\LogService::class)->error($message, $context, $channel);
    }
}

if (!function_exists('logger_critical')) {
    /**
     * 记录严重错误日志
     *
     * @param string $message
     * @param array $context
     * @param string|null $channel
     * @return void
     */
    function logger_critical(string $message, array $context = [], ?string $channel = null): void
    {
        app(\App\Services\LogService::class)->critical($message, $context, $channel);
    }
}

if (!function_exists('logger_emergency')) {
    /**
     * 记录紧急日志
     *
     * @param string $message
     * @param array $context
     * @param string|null $channel
     * @return void
     */
    function logger_emergency(string $message, array $context = [], ?string $channel = null): void
    {
        app(\App\Services\LogService::class)->emergency($message, $context, $channel);
    }
}

if (!function_exists('logger_api_request')) {
    /**
     * 记录 API 请求日志
     *
     * @param \Illuminate\Http\Request $request
     * @param array $extra
     * @return void
     */
    function logger_api_request($request, array $extra = []): void
    {
        app(\App\Services\LogService::class)->apiRequest($request, $extra);
    }
}

if (!function_exists('logger_api_response')) {
    /**
     * 记录 API 响应日志
     *
     * @param \Illuminate\Http\Response|\Illuminate\Http\JsonResponse $response
     * @param array $extra
     * @return void
     */
    function logger_api_response($response, array $extra = []): void
    {
        app(\App\Services\LogService::class)->apiResponse($response, $extra);
    }
}

if (!function_exists('logger_query')) {
    /**
     * 记录数据库查询日志
     *
     * @param string $query
     * @param array $bindings
     * @param float $time
     * @return void
     */
    function logger_query(string $query, array $bindings = [], float $time = 0): void
    {
        app(\App\Services\LogService::class)->query($query, $bindings, $time);
    }
}

if (!function_exists('logger_business')) {
    /**
     * 记录业务操作日志
     *
     * @param string $action
     * @param array $data
     * @param int|null $userId
     * @return void
     */
    function logger_business(string $action, array $data = [], ?int $userId = null): void
    {
        app(\App\Services\LogService::class)->business($action, $data, $userId);
    }
}

if (!function_exists('logger_performance')) {
    /**
     * 记录性能日志
     *
     * @param string $operation
     * @param float $duration
     * @param array $context
     * @return void
     */
    function logger_performance(string $operation, float $duration, array $context = []): void
    {
        app(\App\Services\LogService::class)->performance($operation, $duration, $context);
    }
}

if (!function_exists('logger_exception')) {
    /**
     * 记录异常日志
     *
     * @param \Throwable $exception
     * @param array $context
     * @return void
     */
    function logger_exception(\Throwable $exception, array $context = []): void
    {
        app(\App\Services\LogService::class)->exception($exception, $context);
    }
}

if (!function_exists('cache_get')) {
    /**
     * 获取缓存值
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function cache_get(string $key, $default = null)
    {
        return app(\App\Services\CacheService::class)->get($key, $default);
    }
}

if (!function_exists('cache_set')) {
    /**
     * 设置缓存值
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl 过期时间（秒），null 使用默认值
     * @return bool
     */
    function cache_set(string $key, $value, ?int $ttl = null): bool
    {
        return app(\App\Services\CacheService::class)->set($key, $value, $ttl);
    }
}

if (!function_exists('cache_delete')) {
    /**
     * 删除缓存
     *
     * @param string $key
     * @return bool
     */
    function cache_delete(string $key): bool
    {
        return app(\App\Services\CacheService::class)->delete($key);
    }
}

if (!function_exists('cache_has')) {
    /**
     * 检查缓存是否存在
     *
     * @param string $key
     * @return bool
     */
    function cache_has(string $key): bool
    {
        return app(\App\Services\CacheService::class)->has($key);
    }
}

if (!function_exists('cache_remember')) {
    /**
     * 获取或设置缓存（如果不存在）
     *
     * @param string $key
     * @param \Closure|mixed $value
     * @param int|null $ttl
     * @return mixed
     */
    function cache_remember(string $key, $value, ?int $ttl = null)
    {
        return app(\App\Services\CacheService::class)->remember($key, $value, $ttl);
    }
}

if (!function_exists('cache_forever')) {
    /**
     * 永久缓存（不过期）
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    function cache_forever(string $key, $value): bool
    {
        return app(\App\Services\CacheService::class)->forever($key, $value);
    }
}

if (!function_exists('cache_flush')) {
    /**
     * 清空所有缓存
     *
     * @return bool
     */
    function cache_flush(): bool
    {
        return app(\App\Services\CacheService::class)->flush();
    }
}

if (!function_exists('cache_increment')) {
    /**
     * 增加缓存值（仅数字）
     *
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    function cache_increment(string $key, int $value = 1)
    {
        return app(\App\Services\CacheService::class)->increment($key, $value);
    }
}

if (!function_exists('cache_decrement')) {
    /**
     * 减少缓存值（仅数字）
     *
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    function cache_decrement(string $key, int $value = 1)
    {
        return app(\App\Services\CacheService::class)->decrement($key, $value);
    }
}

if (!function_exists('cache_tags')) {
    /**
     * 使用标签缓存（Redis 支持）
     *
     * @param array|string $tags
     * @return \Illuminate\Cache\TaggedCache
     */
    function cache_tags($tags)
    {
        return app(\App\Services\CacheService::class)->tags($tags);
    }
}

if (!function_exists('cache_service')) {
    /**
     * 获取缓存服务实例
     *
     * @return \App\Services\CacheService
     */
    function cache_service(): \App\Services\CacheService
    {
        return app(\App\Services\CacheService::class);
    }
}

if (!function_exists('queue_push')) {
    /**
     * 推送消息到队列
     *
     * @param mixed $message 消息内容
     * @param string|null $queue 队列名称
     * @param string|null $driver 驱动名称
     * @param array $options 选项（delay, priority等）
     * @return mixed 消息ID
     */
    function queue_push($message, ?string $queue = null, ?string $driver = null, array $options = [])
    {
        return app(\App\Services\QueueService::class)->push($message, $queue, $driver, $options);
    }
}

if (!function_exists('queue_later')) {
    /**
     * 延迟推送消息到队列
     *
     * @param mixed $message 消息内容
     * @param int $delay 延迟时间（秒）
     * @param string|null $queue 队列名称
     * @param string|null $driver 驱动名称
     * @param array $options 其他选项
     * @return mixed 消息ID
     */
    function queue_later($message, int $delay, ?string $queue = null, ?string $driver = null, array $options = [])
    {
        return app(\App\Services\QueueService::class)->later($message, $delay, $queue, $driver, $options);
    }
}

if (!function_exists('queue_bulk')) {
    /**
     * 批量推送消息到队列
     *
     * @param array $messages 消息数组
     * @param string|null $queue 队列名称
     * @param string|null $driver 驱动名称
     * @param array $options 选项
     * @return array 消息ID数组
     */
    function queue_bulk(array $messages, ?string $queue = null, ?string $driver = null, array $options = []): array
    {
        return app(\App\Services\QueueService::class)->bulk($messages, $queue, $driver, $options);
    }
}

if (!function_exists('queue_pull')) {
    /**
     * 从队列拉取消息
     *
     * @param string|null $queue 队列名称
     * @param string|null $driver 驱动名称
     * @param int $timeout 超时时间（秒）
     * @return mixed|null
     */
    function queue_pull(?string $queue = null, ?string $driver = null, int $timeout = 0)
    {
        return app(\App\Services\QueueService::class)->pull($queue, $driver, $timeout);
    }
}

if (!function_exists('queue_size')) {
    /**
     * 获取队列大小
     *
     * @param string|null $queue
     * @param string|null $driver
     * @return int
     */
    function queue_size(?string $queue = null, ?string $driver = null): int
    {
        return app(\App\Services\QueueService::class)->size($queue, $driver);
    }
}

if (!function_exists('queue_clear')) {
    /**
     * 清空队列
     *
     * @param string|null $queue
     * @param string|null $driver
     * @return bool
     */
    function queue_clear(?string $queue = null, ?string $driver = null): bool
    {
        return app(\App\Services\QueueService::class)->clear($queue, $driver);
    }
}

if (!function_exists('queue_delete')) {
    /**
     * 删除消息
     *
     * @param mixed $messageId 消息ID
     * @param string|null $queue
     * @param string|null $driver
     * @return bool
     */
    function queue_delete($messageId, ?string $queue = null, ?string $driver = null): bool
    {
        return app(\App\Services\QueueService::class)->delete($messageId, $queue, $driver);
    }
}

if (!function_exists('queue_service')) {
    /**
     * 获取队列服务实例
     *
     * @return \App\Services\QueueService
     */
    function queue_service(): \App\Services\QueueService
    {
        return app(\App\Services\QueueService::class);
    }
}

