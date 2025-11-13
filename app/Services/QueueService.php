<?php

namespace App\Services;

use App\Queue\QueueManager;
use App\Queue\Contracts\QueueDriverInterface;

/**
 * 队列服务类
 *
 * 基于队列抽象层，支持多种消息队列驱动
 */
class QueueService
{
    /**
     * 队列管理器
     *
     * @var QueueManager
     */
    protected QueueManager $manager;

    /**
     * 默认驱动名称
     *
     * @var string
     */
    protected string $defaultDriver = 'redis';

    /**
     * 默认队列名称
     *
     * @var string
     */
    protected string $defaultQueue = 'default';

    /**
     * 构造函数
     *
     * @param QueueManager $manager
     */
    public function __construct(QueueManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * 推送消息到队列
     *
     * @param mixed $message 消息内容
     * @param string|null $queue 队列名称
     * @param string|null $driver 驱动名称
     * @param array $options 选项（delay, priority等）
     * @return mixed 消息ID
     */
    public function push($message, ?string $queue = null, ?string $driver = null, array $options = [])
    {
        $queue = $queue ?? $this->defaultQueue;
        $driver = $driver ?? $this->defaultDriver;

        $queueDriver = $this->manager->driver($driver);

        logger_info('消息已推送到队列', [
            'queue' => $queue,
            'driver' => $driver,
            'options' => $options,
        ], 'queue');

        return $queueDriver->push($queue, $message, $options);
    }

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
    public function later($message, int $delay, ?string $queue = null, ?string $driver = null, array $options = [])
    {
        $options['delay'] = $delay;
        return $this->push($message, $queue, $driver, $options);
    }

    /**
     * 批量推送消息
     *
     * @param array $messages 消息数组
     * @param string|null $queue 队列名称
     * @param string|null $driver 驱动名称
     * @param array $options 选项
     * @return array 消息ID数组
     */
    public function bulk(array $messages, ?string $queue = null, ?string $driver = null, array $options = []): array
    {
        $queue = $queue ?? $this->defaultQueue;
        $driver = $driver ?? $this->defaultDriver;

        $queueDriver = $this->manager->driver($driver);

        logger_info('批量消息已推送到队列', [
            'count' => count($messages),
            'queue' => $queue,
            'driver' => $driver,
        ], 'queue');

        return $queueDriver->pushBatch($queue, $messages, $options);
    }

    /**
     * 从队列拉取消息
     *
     * @param string|null $queue 队列名称
     * @param string|null $driver 驱动名称
     * @param int $timeout 超时时间（秒）
     * @return mixed|null
     */
    public function pull(?string $queue = null, ?string $driver = null, int $timeout = 0)
    {
        $queue = $queue ?? $this->defaultQueue;
        $driver = $driver ?? $this->defaultDriver;

        $queueDriver = $this->manager->driver($driver);
        return $queueDriver->pull($queue, $timeout);
    }

    /**
     * 获取队列大小
     *
     * @param string|null $queue
     * @param string|null $driver
     * @return int
     */
    public function size(?string $queue = null, ?string $driver = null): int
    {
        $queue = $queue ?? $this->defaultQueue;
        $driver = $driver ?? $this->defaultDriver;

        try {
            $queueDriver = $this->manager->driver($driver);
            return $queueDriver->size($queue);
        } catch (\Exception $e) {
            logger_error('获取队列大小失败', [
                'queue' => $queue,
                'driver' => $driver,
                'error' => $e->getMessage(),
            ], 'queue');
            return 0;
        }
    }

    /**
     * 清空队列
     *
     * @param string|null $queue
     * @param string|null $driver
     * @return bool
     */
    public function clear(?string $queue = null, ?string $driver = null): bool
    {
        $queue = $queue ?? $this->defaultQueue;
        $driver = $driver ?? $this->defaultDriver;

        try {
            $queueDriver = $this->manager->driver($driver);
            $result = $queueDriver->clear($queue);

            logger_info('队列已清空', [
                'queue' => $queue,
                'driver' => $driver,
            ], 'queue');

            return $result;
        } catch (\Exception $e) {
            logger_error('清空队列失败', [
                'queue' => $queue,
                'driver' => $driver,
                'error' => $e->getMessage(),
            ], 'queue');
            return false;
        }
    }

    /**
     * 删除消息
     *
     * @param mixed $messageId 消息ID
     * @param string|null $queue
     * @param string|null $driver
     * @return bool
     */
    public function delete($messageId, ?string $queue = null, ?string $driver = null): bool
    {
        $queue = $queue ?? $this->defaultQueue;
        $driver = $driver ?? $this->defaultDriver;

        try {
            $queueDriver = $this->manager->driver($driver);
            return $queueDriver->delete($queue, $messageId);
        } catch (\Exception $e) {
            logger_error('删除消息失败', [
                'queue' => $queue,
                'driver' => $driver,
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ], 'queue');
            return false;
        }
    }

    /**
     * 确认消息已处理
     *
     * @param mixed $messageId 消息ID
     * @param string|null $queue
     * @param string|null $driver
     * @return bool
     */
    public function acknowledge($messageId, ?string $queue = null, ?string $driver = null): bool
    {
        $queue = $queue ?? $this->defaultQueue;
        $driver = $driver ?? $this->defaultDriver;

        try {
            $queueDriver = $this->manager->driver($driver);
            return $queueDriver->acknowledge($queue, $messageId);
        } catch (\Exception $e) {
            logger_error('确认消息失败', [
                'queue' => $queue,
                'driver' => $driver,
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ], 'queue');
            return false;
        }
    }

    /**
     * 设置默认驱动
     *
     * @param string $driver
     * @return $this
     */
    public function setDefaultDriver(string $driver): self
    {
        $this->defaultDriver = $driver;
        $this->manager->setDefaultDriver($driver);
        return $this;
    }

    /**
     * 设置默认队列名称
     *
     * @param string $queue
     * @return $this
     */
    public function setDefaultQueue(string $queue): self
    {
        $this->defaultQueue = $queue;
        return $this;
    }

    /**
     * 获取队列驱动实例
     *
     * @param string|null $driver
     * @return QueueDriverInterface
     */
    public function driver(?string $driver = null): QueueDriverInterface
    {
        $driver = $driver ?? $this->defaultDriver;
        return $this->manager->driver($driver);
    }
}

