<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\QueueService;

class MonitorQueuesCommand extends Command
{
    /**
     * 命令签名
     *
     * @var string
     */
    protected $signature = 'queue:monitor
                          {--threshold=1000 : 队列大小告警阈值}
                          {--queues=* : 要监控的队列名称}
                          {--driver=redis : 队列驱动}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '监控队列大小并发送告警';

    /**
     * 执行命令
     */
    public function handle(QueueService $queueService): int
    {
        $threshold = (int) $this->option('threshold');
        $queues = $this->option('queues') ?: ['default', 'emails', 'notifications'];
        $driver = $this->option('driver');

        $this->info("🔍 开始监控队列...");
        $this->info("驱动: {$driver}");
        $this->info("阈值: {$threshold}");
        $this->newLine();

        $hasWarning = false;

        foreach ($queues as $queue) {
            try {
                $size = $queueService->size($queue, $driver);

                if ($size > $threshold) {
                    $this->warn("⚠️  队列 [{$queue}] 积压严重！当前: {$size}, 阈值: {$threshold}");
                    $hasWarning = true;

                    // 记录日志
                    logger_warning('队列积压告警', [
                        'queue' => $queue,
                        'size' => $size,
                        'threshold' => $threshold,
                        'driver' => $driver,
                    ]);

                    // 这里可以发送告警通知
                    // $this->sendAlert($queue, $size, $threshold);
                } else {
                    $this->line("✓ 队列 [{$queue}] 正常: {$size} 条消息");
                }
            } catch (\Exception $e) {
                $this->error("✗ 队列 [{$queue}] 检查失败: " . $e->getMessage());
                
                logger_error('队列监控失败', [
                    'queue' => $queue,
                    'driver' => $driver,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        
        if ($hasWarning) {
            $this->warn('⚠️  发现队列积压，请检查消费者是否正常运行');
            return Command::FAILURE;
        }

        $this->info('✓ 所有队列状态正常');
        return Command::SUCCESS;
    }

    /**
     * 发送告警通知（示例）
     */
    protected function sendAlert(string $queue, int $size, int $threshold): void
    {
        // 这里可以集成各种告警渠道
        // 例如：邮件、短信、钉钉、Slack 等
        
        // 示例：记录到专门的告警日志
        logger_critical('队列积压严重告警', [
            'queue' => $queue,
            'current_size' => $size,
            'threshold' => $threshold,
            'alert_time' => now()->toDateTimeString(),
        ], 'alert');
    }
}

