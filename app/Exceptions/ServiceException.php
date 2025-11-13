<?php

namespace App\Exceptions;

class ServiceException extends BusinessException
{
    /**
     * 创建服务异常
     *
     * @param string $message 错误消息
     * @param int $code HTTP 状态码
     * @param mixed $data 错误数据
     */
    public function __construct(string $message = '服务处理失败', int $code = 500, $data = null)
    {
        parent::__construct($message, $code, $data);
    }
}

