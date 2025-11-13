<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任务尝试次数
     *
     * @var int
     */
    public $tries = 3;

    /**
     * 任务超时时间（秒）
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * 任务失败前的最大异常数
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * 邮件数据
     *
     * @var array
     */
    public array $emailData;

    /**
     * Create a new job instance.
     *
     * @param array $emailData
     */
    public function __construct(array $emailData)
    {
        $this->emailData = $emailData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        logger_info('发送邮件任务开始', [
            'to' => $this->emailData['to'] ?? null,
            'subject' => $this->emailData['subject'] ?? null,
        ], 'queue');

        // 模拟发送邮件
        // Mail::to($this->emailData['to'])->send(new EmailTemplate($this->emailData));

        // 这里可以调用实际的邮件服务
        sleep(1); // 模拟邮件发送耗时

        logger_info('邮件发送成功', [
            'to' => $this->emailData['to'] ?? null,
        ], 'queue');
    }

    /**
     * 任务失败时的处理
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        logger_error('邮件发送失败', [
            'email_data' => $this->emailData,
            'error' => $exception->getMessage(),
        ], 'queue');
    }
}
