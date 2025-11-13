<?php

namespace App\Queue\Drivers;

use Illuminate\Support\Facades\Redis;

/**
 * Redis 队列驱动
 */
class RedisQueueDriver extends AbstractQueueDriver
{
    /**
     * Redis 连接名称
     *
     * @var string
     */
    protected string $connectionName;

    /**
     * 队列前缀
     *
     * @var string
     */
    protected string $prefix;

    /**
     * 构造函数
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->connectionName = $config['connection'] ?? 'default';
        $this->prefix = $config['prefix'] ?? 'queue:';
    }

    /**
     * 获取 Redis 连接
     *
     * @return \Illuminate\Redis\Connections\Connection
     */
    protected function getRedis()
    {
        if (!$this->connected) {
            $this->connect();
        }
        return Redis::connection($this->connectionName);
    }

    /**
     * 建立连接
     *
     * @return void
     */
    protected function connect(): void
    {
        try {
            $redis = Redis::connection($this->connectionName);
            $redis->ping();
            $this->connection = $redis;
            $this->connected = true;
        } catch (\Exception $e) {
            $this->log('error', 'Redis 连接失败', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 推送消息到队列
     *
     * @param string $queue 队列名称
     * @param mixed $message 消息内容
     * @param array $options 选项
     * @return string 消息ID
     */
    public function push(string $queue, $message, array $options = []): string
    {
        $redis = $this->getRedis();
        $queueKey = $this->getQueueKey($queue);
        $messageData = $this->serializeMessage($message);
        $messageId = $this->generateMessageId();

        // 如果有延迟时间
        if (isset($options['delay']) && $options['delay'] > 0) {
            $delay = is_int($options['delay']) ? $options['delay'] : (int) $options['delay'];
            $redis->zadd($queueKey . ':delayed', time() + $delay, json_encode([
                'id' => $messageId,
                'data' => $messageData,
            ]));
        } else {
            // 如果有优先级
            if (isset($options['priority'])) {
                $priority = (int) $options['priority'];
                $redis->zadd($queueKey . ':priority', $priority, json_encode([
                    'id' => $messageId,
                    'data' => $messageData,
                ]));
            } else {
                // 普通队列
                $redis->rpush($queueKey, json_encode([
                    'id' => $messageId,
                    'data' => $messageData,
                ]));
            }
        }

        $this->log('info', '消息已推送到队列', [
            'queue' => $queue,
            'message_id' => $messageId,
        ]);

        return $messageId;
    }

    /**
     * 从队列中拉取消息
     *
     * @param string $queue 队列名称
     * @param int $timeout 超时时间（秒）
     * @return mixed|null
     */
    public function pull(string $queue, int $timeout = 0)
    {
        $redis = $this->getRedis();
        $queueKey = $this->getQueueKey($queue);

        // 先检查延迟队列
        $this->processDelayedMessages($queue);

        // 先检查优先级队列
        $priorityMessage = $redis->zrevrange($queueKey . ':priority', 0, 0);
        if (!empty($priorityMessage)) {
            $message = json_decode($priorityMessage[0], true);
            $redis->zrem($queueKey . ':priority', $priorityMessage[0]);
            return $this->unserializeMessage($message['data']);
        }

        // 从普通队列拉取
        if ($timeout > 0) {
            $result = $redis->blpop($queueKey, $timeout);
            if ($result === null) {
                return null;
            }
            $messageData = $result[1];
        } else {
            $messageData = $redis->lpop($queueKey);
            if ($messageData === null) {
                return null;
            }
        }

        $message = json_decode($messageData, true);
        return $this->unserializeMessage($message['data'] ?? $messageData);
    }

    /**
     * 处理延迟消息
     *
     * @param string $queue
     * @return void
     */
    protected function processDelayedMessages(string $queue): void
    {
        $redis = $this->getRedis();
        $delayedKey = $this->getQueueKey($queue) . ':delayed';
        $now = time();

        // 获取到期的消息
        $messages = $redis->zrangebyscore($delayedKey, 0, $now);
        if (!empty($messages)) {
            $queueKey = $this->getQueueKey($queue);
            foreach ($messages as $message) {
                $redis->rpush($queueKey, $message);
                $redis->zrem($delayedKey, $message);
            }
        }
    }

    /**
     * 获取队列大小
     *
     * @param string $queue 队列名称
     * @return int
     */
    public function size(string $queue): int
    {
        $redis = $this->getRedis();
        $queueKey = $this->getQueueKey($queue);
        
        // 处理延迟消息
        $this->processDelayedMessages($queue);

        $size = $redis->llen($queueKey);
        $prioritySize = $redis->zcard($queueKey . ':priority');
        $delayedSize = $redis->zcard($queueKey . ':delayed');

        return $size + $prioritySize + $delayedSize;
    }

    /**
     * 清空队列
     *
     * @param string $queue 队列名称
     * @return bool
     */
    public function clear(string $queue): bool
    {
        try {
            $redis = $this->getRedis();
            $queueKey = $this->getQueueKey($queue);
            
            $redis->del($queueKey);
            $redis->del($queueKey . ':priority');
            $redis->del($queueKey . ':delayed');

            $this->log('info', '队列已清空', ['queue' => $queue]);
            return true;
        } catch (\Exception $e) {
            $this->log('error', '清空队列失败', [
                'queue' => $queue,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 删除消息
     *
     * @param string $queue 队列名称
     * @param mixed $messageId 消息ID
     * @return bool
     */
    public function delete(string $queue, $messageId): bool
    {
        // Redis 队列中，消息一旦被拉取就自动删除
        // 这里主要用于清理延迟队列中的消息
        try {
            $redis = $this->getRedis();
            $delayedKey = $this->getQueueKey($queue) . ':delayed';
            
            // 查找并删除包含该 messageId 的消息
            $messages = $redis->zrange($delayedKey, 0, -1);
            foreach ($messages as $message) {
                $data = json_decode($message, true);
                if (isset($data['id']) && $data['id'] === $messageId) {
                    $redis->zrem($delayedKey, $message);
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            $this->log('error', '删除消息失败', [
                'queue' => $queue,
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 确认消息已处理
     *
     * @param string $queue 队列名称
     * @param mixed $messageId 消息ID
     * @return bool
     */
    public function acknowledge(string $queue, $messageId): bool
    {
        // Redis 队列中，消息拉取后自动删除，无需确认
        return true;
    }

    /**
     * 检查连接是否可用
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        if (!$this->connected) {
            try {
                $this->connect();
            } catch (\Exception $e) {
                return false;
            }
        }

        try {
            $this->getRedis()->ping();
            return true;
        } catch (\Exception $e) {
            $this->connected = false;
            return false;
        }
    }

    /**
     * 关闭连接
     *
     * @return void
     */
    public function close(): void
    {
        $this->connected = false;
        $this->connection = null;
    }

    /**
     * 获取队列键名
     *
     * @param string $queue
     * @return string
     */
    protected function getQueueKey(string $queue): string
    {
        return $this->prefix . $queue;
    }

    /**
     * 生成消息ID
     *
     * @return string
     */
    protected function generateMessageId(): string
    {
        return uniqid('msg_', true);
    }
}

