<?php

namespace App\Queue\Drivers;

/**
 * RabbitMQ 队列驱动
 *
 * 基于 php-amqplib/php-amqplib 实现
 *
 * @see https://github.com/php-amqplib/php-amqplib
 */
class RabbitMQQueueDriver extends AbstractQueueDriver
{
    /**
     * AMQP 连接
     *
     * @var \PhpAmqpLib\Connection\AMQPStreamConnection|null
     */
    protected $amqpConnection = null;

    /**
     * AMQP 通道
     *
     * @var \PhpAmqpLib\Channel\AMQPChannel|null
     */
    protected $channel = null;

    /**
     * 构造函数
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    /**
     * 建立连接
     *
     * @return void
     */
    protected function connect(): void
    {
        if ($this->connected && $this->amqpConnection !== null) {
            return;
        }

        try {
            $host = $this->getConfig('host', 'localhost');
            $port = $this->getConfig('port', 5672);
            $user = $this->getConfig('user', 'guest');
            $password = $this->getConfig('password', 'guest');
            $vhost = $this->getConfig('vhost', '/');

            $this->amqpConnection = new \PhpAmqpLib\Connection\AMQPStreamConnection(
                $host,
                $port,
                $user,
                $password,
                $vhost
            );

            $this->channel = $this->amqpConnection->channel();
            $this->connected = true;

            $this->log('info', 'RabbitMQ 连接成功');
        } catch (\Exception $e) {
            $this->log('error', 'RabbitMQ 连接失败', ['error' => $e->getMessage()]);
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
        $this->connect();

        $messageData = $this->serializeMessage($message);
        $messageId = $this->generateMessageId();

        // 声明队列
        $this->channel->queue_declare($queue, false, true, false, false);

        // 创建消息
        $amqpMessage = new \PhpAmqpLib\Message\AMQPMessage($messageData, [
            'delivery_mode' => \PhpAmqpLib\Message\AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'message_id' => $messageId,
        ]);

        // 设置延迟（使用延迟插件）
        if (isset($options['delay']) && $options['delay'] > 0) {
            $delay = is_int($options['delay']) ? $options['delay'] : (int) $options['delay'];
            $amqpMessage->set('application_headers', new \PhpAmqpLib\Wire\AMQPTable([
                'x-delay' => $delay * 1000, // 转换为毫秒
            ]));
        }

        // 设置优先级
        if (isset($options['priority'])) {
            $amqpMessage->set('priority', (int) $options['priority']);
        }

        // 发布消息
        $this->channel->basic_publish($amqpMessage, '', $queue);

        $this->log('info', '消息已推送到队列', [
            'queue' => $queue,
            'message_id' => $messageId,
        ]);

        return $messageId;
    }

    /**
     * 消费者标签
     *
     * @var string|null
     */
    protected $consumerTag = null;

    /**
     * 从队列中拉取消息
     *
     * @param string $queue 队列名称
     * @param int $timeout 超时时间（秒）
     * @return mixed|null
     */
    public function pull(string $queue, int $timeout = 0)
    {
        $this->connect();

        // 声明队列
        $this->channel->queue_declare($queue, false, true, false, false);

        $message = null;
        $this->consumerTag = 'consumer_' . uniqid('', true);
        
        $callback = function ($msg) use (&$message) {
            $message = $msg;
            $msg->ack();
        };

        try {
            // 设置消费者
            $this->channel->basic_consume(
                $queue, 
                $this->consumerTag, 
                false, 
                false, 
                false, 
                false, 
                $callback
            );

            // 等待消息
            $startTime = time();
            while ($message === null) {
                if ($timeout > 0 && (time() - $startTime) >= $timeout) {
                    break;
                }
                
                try {
                    $this->channel->wait(null, false, $timeout > 0 ? min($timeout, 1) : 1);
                } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                    // 超时是正常的，继续循环或退出
                    break;
                } catch (\Exception $e) {
                    $this->log('error', 'RabbitMQ wait 失败', [
                        'queue' => $queue,
                        'error' => $e->getMessage(),
                    ]);
                    break;
                }
            }

            return $message ? $this->unserializeMessage($message->body) : null;
            
        } finally {
            // 确保取消消费者
            if ($this->consumerTag && $this->channel) {
                try {
                    $this->channel->basic_cancel($this->consumerTag, false, true);
                } catch (\Exception $e) {
                    $this->log('warning', '取消消费者失败', [
                        'consumer_tag' => $this->consumerTag,
                        'error' => $e->getMessage(),
                    ]);
                }
                $this->consumerTag = null;
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
        $this->connect();

        try {
            // 声明队列并获取消息数量
            list(, $messageCount) = $this->channel->queue_declare($queue, true, true, false, false);
            return $messageCount;
        } catch (\Exception $e) {
            $this->log('error', '获取队列大小失败', [
                'queue' => $queue,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * 清空队列
     *
     * @param string $queue 队列名称
     * @return bool
     */
    public function clear(string $queue): bool
    {
        $this->connect();

        try {
            // 删除并重新声明队列
            $this->channel->queue_delete($queue);
            $this->channel->queue_declare($queue, false, true, false, false);

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
        // RabbitMQ 中，消息一旦被确认就自动删除
        // 这里主要用于取消未确认的消息
        return true;
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
        // 在 pull 方法中已经自动确认
        return true;
    }

    /**
     * 检查连接是否可用
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        if (!$this->connected || $this->amqpConnection === null) {
            try {
                $this->connect();
            } catch (\Exception $e) {
                return false;
            }
        }

        try {
            return $this->amqpConnection->isConnected();
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
        if ($this->channel !== null) {
            try {
                $this->channel->close();
            } catch (\Exception $e) {
                // 忽略关闭错误
            }
            $this->channel = null;
        }

        if ($this->amqpConnection !== null) {
            try {
                $this->amqpConnection->close();
            } catch (\Exception $e) {
                // 忽略关闭错误
            }
            $this->amqpConnection = null;
        }

        $this->connected = false;
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

