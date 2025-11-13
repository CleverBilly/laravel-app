<?php

namespace App\Queue;

use App\Queue\Contracts\QueueDriverInterface;
use App\Queue\Drivers\RedisQueueDriver;
use App\Queue\Drivers\RabbitMQQueueDriver;
use InvalidArgumentException;

/**
 * 队列管理器
 * 
 * 负责创建和管理不同的队列驱动实例
 */
class QueueManager
{
    /**
     * 已创建的驱动实例
     *
     * @var array<string, QueueDriverInterface>
     */
    protected array $drivers = [];

    /**
     * 默认驱动名称
     *
     * @var string
     */
    protected string $defaultDriver;

    /**
     * 驱动配置
     *
     * @var array
     */
    protected array $config;

    /**
     * 构造函数
     *
     * @param array $config 配置信息
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->defaultDriver = $config['default'] ?? 'redis';
    }

    /**
     * 获取驱动实例
     *
     * @param string|null $driver 驱动名称，null 使用默认驱动
     * @return QueueDriverInterface
     */
    public function driver(?string $driver = null): QueueDriverInterface
    {
        $driver = $driver ?? $this->defaultDriver;

        if (isset($this->drivers[$driver])) {
            return $this->drivers[$driver];
        }

        return $this->drivers[$driver] = $this->createDriver($driver);
    }

    /**
     * 创建驱动实例
     *
     * @param string $driver 驱动名称
     * @return QueueDriverInterface
     * @throws InvalidArgumentException
     */
    protected function createDriver(string $driver): QueueDriverInterface
    {
        $driverConfig = $this->config['drivers'][$driver] ?? [];

        return match ($driver) {
            'redis' => new RedisQueueDriver($driverConfig),
            'rabbitmq' => new RabbitMQQueueDriver($driverConfig),
            default => throw new InvalidArgumentException("不支持的队列驱动: {$driver}"),
        };
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
        return $this;
    }

    /**
     * 获取默认驱动
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->defaultDriver;
    }

    /**
     * 关闭所有驱动连接
     *
     * @return void
     */
    public function closeAll(): void
    {
        foreach ($this->drivers as $driver) {
            $driver->close();
        }
        $this->drivers = [];
    }
}

