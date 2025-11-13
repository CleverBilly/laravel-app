<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任务尝试次数
     *
     * @var int
     */
    public $tries = 5;

    /**
     * 任务超时时间（秒）
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * 订单 ID
     *
     * @var int
     */
    public int $orderId;

    /**
     * Create a new job instance.
     *
     * @param int $orderId
     */
    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        logger_info('处理订单任务开始', [
            'order_id' => $this->orderId,
        ], 'queue');

        // 模拟订单处理逻辑
        // $order = Order::findOrFail($this->orderId);
        // $order->process();

        sleep(2); // 模拟处理耗时

        logger_info('订单处理完成', [
            'order_id' => $this->orderId,
        ], 'queue');
    }

    /**
     * 计算任务重试延迟时间（秒）
     *
     * @return array
     */
    public function backoff(): array
    {
        return [10, 30, 60, 120, 300]; // 重试延迟时间
    }

    /**
     * 任务失败时的处理
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        logger_error('订单处理失败', [
            'order_id' => $this->orderId,
            'error' => $exception->getMessage(),
        ], 'queue');
    }
}
