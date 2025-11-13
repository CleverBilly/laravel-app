<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class BusinessException extends Exception
{
    /**
     * 错误码
     *
     * @var int
     */
    protected $code = 400;

    /**
     * 错误数据
     *
     * @var mixed
     */
    protected $data = null;

    /**
     * 创建业务异常实例
     *
     * @param string $message 错误消息
     * @param int $code HTTP 状态码
     * @param mixed $data 错误数据
     * @param \Throwable|null $previous 前一个异常
     */
    public function __construct(string $message = '', int $code = 400, $data = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->data = $data;
    }

    /**
     * 获取错误数据
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 渲染异常为 HTTP 响应
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function render($request): JsonResponse
    {
        return api_error(
            $this->getMessage() ?: '业务处理失败',
            $this->getCode(),
            $this->getData()
        );
    }
}

