<?php

namespace App\Exceptions;

class NotFoundException extends BusinessException
{
    /**
     * 创建资源未找到异常
     *
     * @param string $message 错误消息
     * @param mixed $data 错误数据
     */
    public function __construct(string $message = '资源不存在', $data = null)
    {
        parent::__construct($message, 404, $data);
    }
}

