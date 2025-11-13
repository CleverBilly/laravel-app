<?php

namespace App\Exceptions;

class UnauthorizedException extends BusinessException
{
    /**
     * 创建未授权异常
     *
     * @param string $message 错误消息
     * @param mixed $data 错误数据
     */
    public function __construct(string $message = '未授权访问', $data = null)
    {
        parent::__construct($message, 401, $data);
    }
}

