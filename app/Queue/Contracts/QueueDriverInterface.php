<?php

namespace App\Queue\Contracts;

/**
 * 消息队列驱动接口
 */
interface QueueDriverInterface
{
    /**
     * 推送消息到队列
     *
     * @param string $queue 队列名称
     * @param mixed $message 消息内容
     * @param array $options 选项（延迟时间、优先级等）
     * @return mixed 返回消息ID或结果
     */
    public function push(string $queue, $message, array $options = []);

    /**
     * 从队列中拉取消息
     *
     * @param string $queue 队列名称
     * @param int $timeout 超时时间（秒）
     * @return mixed|null 消息内容，超时返回 null
     */
    public function pull(string $queue, int $timeout = 0);

    /**
     * 批量推送消息
     *
     * @param string $queue 队列名称
     * @param array $messages 消息数组
     * @param array $options 选项
     * @return array 返回消息ID数组
     */
    public function pushBatch(string $queue, array $messages, array $options = []): array;

    /**
     * 获取队列大小
     *
     * @param string $queue 队列名称
     * @return int 队列中消息数量
     */
    public function size(string $queue): int;

    /**
     * 清空队列
     *
     * @param string $queue 队列名称
     * @return bool 是否成功
     */
    public function clear(string $queue): bool;

    /**
     * 删除消息
     *
     * @param string $queue 队列名称
     * @param mixed $messageId 消息ID
     * @return bool 是否成功
     */
    public function delete(string $queue, $messageId): bool;

    /**
     * 确认消息已处理
     *
     * @param string $queue 队列名称
     * @param mixed $messageId 消息ID
     * @return bool 是否成功
     */
    public function acknowledge(string $queue, $messageId): bool;

    /**
     * 检查连接是否可用
     *
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * 关闭连接
     *
     * @return void
     */
    public function close(): void;
}

