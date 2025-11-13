<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheService
{
    /**
     * 默认缓存时间（秒）
     *
     * @var int
     */
    protected int $defaultTtl = 3600;

    /**
     * 获取缓存值
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return Cache::get($key, $default);
    }

    /**
     * 设置缓存值
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl 过期时间（秒），null 使用默认值
     * @return bool
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        return Cache::put($key, $value, $ttl);
    }

    /**
     * 永久缓存（不过期）
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function forever(string $key, $value): bool
    {
        return Cache::forever($key, $value);
    }

    /**
     * 删除缓存
     *
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * 检查缓存是否存在
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * 获取或设置缓存（如果不存在）
     *
     * @param string $key
     * @param \Closure|mixed $value
     * @param int|null $ttl
     * @return mixed
     */
    public function remember(string $key, $value, ?int $ttl = null)
    {
        $ttl = $ttl ?? $this->defaultTtl;
        return Cache::remember($key, $ttl, $value);
    }

    /**
     * 获取或设置永久缓存（如果不存在）
     *
     * @param string $key
     * @param \Closure|mixed $value
     * @return mixed
     */
    public function rememberForever(string $key, $value)
    {
        return Cache::rememberForever($key, $value);
    }

    /**
     * 增加缓存值（仅数字）
     *
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public function increment(string $key, int $value = 1)
    {
        return Cache::increment($key, $value);
    }

    /**
     * 减少缓存值（仅数字）
     *
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public function decrement(string $key, int $value = 1)
    {
        return Cache::decrement($key, $value);
    }

    /**
     * 清空所有缓存
     *
     * @return bool
     */
    public function flush(): bool
    {
        return Cache::flush();
    }

    /**
     * 批量获取缓存（优化版 - 使用 Laravel 内置方法）
     *
     * @param array $keys
     * @return array
     */
    public function getMultiple(array $keys): array
    {
        if (empty($keys)) {
            return [];
        }
        
        // 使用 Laravel 内置的 many 方法，性能更好
        return Cache::many($keys);
    }

    /**
     * 批量设置缓存（优化版 - 使用 Redis Pipeline）
     *
     * @param array $values
     * @param int|null $ttl
     * @return bool
     */
    public function setMultiple(array $values, ?int $ttl = null): bool
    {
        if (empty($values)) {
            return true;
        }
        
        $ttl = $ttl ?? $this->defaultTtl;
        
        try {
            // 检查是否使用 Redis 驱动
            $driver = config('cache.default');
            
            if ($driver === 'redis') {
                // 使用 Redis Pipeline 批量设置，性能更好
                Redis::pipeline(function ($pipe) use ($values, $ttl) {
                    $prefix = config('cache.prefix', '');
                    foreach ($values as $key => $value) {
                        $fullKey = $prefix . $key;
                        $serialized = serialize($value);
                        $pipe->setex($fullKey, $ttl, $serialized);
                    }
                });
            } else {
                // 其他驱动使用传统方法
                foreach ($values as $key => $value) {
                    Cache::put($key, $value, $ttl);
                }
            }
            
            return true;
        } catch (\Exception $e) {
            logger_error('批量设置缓存失败', [
                'count' => count($values),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 批量删除缓存（优化版 - 使用 Redis 批量删除）
     *
     * @param array $keys
     * @return bool
     */
    public function deleteMultiple(array $keys): bool
    {
        if (empty($keys)) {
            return true;
        }
        
        try {
            // 检查是否使用 Redis 驱动
            $driver = config('cache.default');
            
            if ($driver === 'redis') {
                // 使用 Redis 批量删除
                $prefix = config('cache.prefix', '');
                $prefixedKeys = array_map(fn($key) => $prefix . $key, $keys);
                Redis::del(...$prefixedKeys);
            } else {
                // 其他驱动使用传统方法
                foreach ($keys as $key) {
                    Cache::forget($key);
                }
            }
            
            return true;
        } catch (\Exception $e) {
            logger_error('批量删除缓存失败', [
                'count' => count($keys),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 防缓存穿透的 remember（新增）
     *
     * @param string $key
     * @param \Closure|mixed $value
     * @param int|null $ttl
     * @return mixed
     */
    public function rememberSafe(string $key, $value, ?int $ttl = null)
    {
        $ttl = $ttl ?? $this->defaultTtl;
        
        // 先检查缓存
        $cached = $this->get($key);
        
        // 如果是空值标记，返回 null
        if ($cached === '__NULL_CACHE__') {
            return null;
        }
        
        // 如果有缓存，直接返回
        if ($cached !== null) {
            return $cached;
        }
        
        // 计算新值
        $result = is_callable($value) ? $value() : $value;
        
        // 如果结果为 null，缓存一个特殊标记，防止缓存穿透
        if ($result === null) {
            $this->set($key, '__NULL_CACHE__', 60); // 空值缓存 1 分钟
            return null;
        }
        
        // 正常缓存结果
        $this->set($key, $result, $ttl);
        
        return $result;
    }

    /**
     * 防缓存雪崩的 set（添加随机过期时间）
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @param bool $addJitter 是否添加随机时间
     * @return bool
     */
    public function setWithJitter(string $key, $value, ?int $ttl = null, bool $addJitter = true): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        
        if ($addJitter && $ttl > 0) {
            // 添加 0-10% 的随机时间，避免缓存同时过期
            $jitter = rand(0, (int)($ttl * 0.1));
            $ttl = $ttl + $jitter;
        }
        
        return Cache::put($key, $value, $ttl);
    }

    /**
     * 使用标签缓存（Redis 支持）
     *
     * @param array|string $tags
     * @return \Illuminate\Cache\TaggedCache
     */
    public function tags($tags)
    {
        return Cache::tags($tags);
    }

    /**
     * 获取缓存键列表（使用 Redis）
     *
     * @param string $pattern
     * @return array
     */
    public function keys(string $pattern = '*'): array
    {
        try {
            return Redis::keys($pattern);
        } catch (\Exception $e) {
            logger_error('获取缓存键列表失败', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * 获取缓存剩余过期时间
     *
     * @param string $key
     * @return int -1 表示永久，-2 表示不存在，正数表示剩余秒数
     */
    public function ttl(string $key): int
    {
        try {
            return Redis::ttl($key);
        } catch (\Exception $e) {
            logger_error('获取缓存过期时间失败', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return -2;
        }
    }

    /**
     * 设置缓存过期时间
     *
     * @param string $key
     * @param int $ttl
     * @return bool
     */
    public function expire(string $key, int $ttl): bool
    {
        try {
            return Redis::expire($key, $ttl);
        } catch (\Exception $e) {
            logger_error('设置缓存过期时间失败', [
                'key' => $key,
                'ttl' => $ttl,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 移除缓存过期时间（永久）
     *
     * @param string $key
     * @return bool
     */
    public function persist(string $key): bool
    {
        try {
            return Redis::persist($key);
        } catch (\Exception $e) {
            logger_error('移除缓存过期时间失败', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 获取缓存信息
     *
     * @param string $key
     * @return array
     */
    public function info(string $key): array
    {
        return [
            'key' => $key,
            'exists' => $this->has($key),
            'ttl' => $this->ttl($key),
            'value' => $this->has($key) ? $this->get($key) : null,
        ];
    }

    /**
     * 设置默认过期时间
     *
     * @param int $ttl
     * @return $this
     */
    public function setDefaultTtl(int $ttl): self
    {
        $this->defaultTtl = $ttl;
        return $this;
    }

    /**
     * 获取默认过期时间
     *
     * @return int
     */
    public function getDefaultTtl(): int
    {
        return $this->defaultTtl;
    }
}

