<?php

namespace App\Exceptions;

class ForbiddenException extends BusinessException
{
    /**
     * 创建禁止访问异常
     *
     * @param string $message 错误消息
     * @param mixed $data 错误数据
     */
    public function __construct(string $message = '禁止访问', $data = null)
    {
        parent::__construct($message, 403, $data);
    }
}

