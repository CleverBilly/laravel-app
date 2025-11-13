<?php

namespace App\Exceptions;

use Illuminate\Contracts\Validation\Validator;

class ValidationException extends BusinessException
{
    /**
     * 验证器实例
     *
     * @var Validator|null
     */
    protected $validator;

    /**
     * 创建验证异常
     *
     * @param string $message 错误消息
     * @param Validator|null $validator 验证器实例
     * @param mixed $data 错误数据
     */
    public function __construct(string $message = '验证失败', ?Validator $validator = null, $data = null)
    {
        parent::__construct($message, 422, $data);
        $this->validator = $validator;
    }

    /**
     * 获取验证器实例
     *
     * @return Validator|null
     */
    public function getValidator(): ?Validator
    {
        return $this->validator;
    }

    /**
     * 获取验证错误信息
     *
     * @return array
     */
    public function getErrors(): array
    {
        if ($this->validator) {
            return $this->validator->errors()->toArray();
        }

        return $this->getData() ?? [];
    }
}

