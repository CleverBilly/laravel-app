<?php

namespace App\Queue\Drivers;

use App\Queue\Contracts\QueueDriverInterface;
use Illuminate\Support\Facades\Log;

/**
 * 消息队列驱动抽象类
 */
abstract class AbstractQueueDriver implements QueueDriverInterface
{
    /**
     * 连接配置
     *
     * @var array
     */
    protected array $config;

    /**
     * 连接实例
     *
     * @var mixed
     */
    protected $connection;

    /**
     * 是否已连接
     *
     * @var bool
     */
    protected bool $connected = false;

    /**
     * 构造函数
     *
     * @param array $config 配置信息
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * 推送消息到队列
     *
     * @param string $queue 队列名称
     * @param mixed $message 消息内容
     * @param array $options 选项
     * @return mixed
     */
    abstract public function push(string $queue, $message, array $options = []);

    /**
     * 从队列中拉取消息
     *
     * @param string $queue 队列名称
     * @param int $timeout 超时时间（秒）
     * @return mixed|null
     */
    abstract public function pull(string $queue, int $timeout = 0);

    /**
     * 批量推送消息
     *
     * @param string $queue 队列名称
     * @param array $messages 消息数组
     * @param array $options 选项
     * @return array
     */
    public function pushBatch(string $queue, array $messages, array $options = []): array
    {
        $messageIds = [];
        foreach ($messages as $message) {
            $messageIds[] = $this->push($queue, $message, $options);
        }
        return $messageIds;
    }

    /**
     * 获取队列大小
     *
     * @param string $queue 队列名称
     * @return int
     */
    abstract public function size(string $queue): int;

    /**
     * 清空队列
     *
     * @param string $queue 队列名称
     * @return bool
     */
    abstract public function clear(string $queue): bool;

    /**
     * 删除消息
     *
     * @param string $queue 队列名称
     * @param mixed $messageId 消息ID
     * @return bool
     */
    abstract public function delete(string $queue, $messageId): bool;

    /**
     * 确认消息已处理
     *
     * @param string $queue 队列名称
     * @param mixed $messageId 消息ID
     * @return bool
     */
    abstract public function acknowledge(string $queue, $messageId): bool;

    /**
     * 检查连接是否可用
     *
     * @return bool
     */
    abstract public function isConnected(): bool;

    /**
     * 关闭连接
     *
     * @return void
     */
    abstract public function close(): void;

    /**
     * 序列化消息
     *
     * @param mixed $message
     * @return string
     */
    protected function serializeMessage($message): string
    {
        if (is_string($message)) {
            return $message;
        }
        return json_encode($message, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 反序列化消息
     *
     * @param string $data
     * @return mixed
     */
    protected function unserializeMessage(string $data)
    {
        $decoded = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        return $data;
    }

    /**
     * 记录日志
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        $context['driver'] = static::class;
        Log::channel('queue')->{$level}($message, $context);
    }

    /**
     * 获取配置值
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
}

