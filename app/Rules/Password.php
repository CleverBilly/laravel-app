<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;

class Password implements ValidationRule
{
    /**
     * 最小长度
     *
     * @var int
     */
    protected int $minLength;

    /**
     * 是否需要包含数字
     *
     * @var bool
     */
    protected bool $requireNumber;

    /**
     * 是否需要包含字母
     *
     * @var bool
     */
    protected bool $requireLetter;

    /**
     * 是否需要包含特殊字符
     *
     * @var bool
     */
    protected bool $requireSpecialChar;

    /**
     * Create a new rule instance.
     *
     * @param int $minLength
     * @param bool $requireNumber
     * @param bool $requireLetter
     * @param bool $requireSpecialChar
     */
    public function __construct(
        int $minLength = 6,
        bool $requireNumber = false,
        bool $requireLetter = false,
        bool $requireSpecialChar = false
    ) {
        $this->minLength = $minLength;
        $this->requireNumber = $requireNumber;
        $this->requireLetter = $requireLetter;
        $this->requireSpecialChar = $requireSpecialChar;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (strlen($value) < $this->minLength) {
            $fail("密码长度至少为{$this->minLength}位");
            return;
        }

        if ($this->requireNumber && !preg_match('/\d/', $value)) {
            $fail('密码必须包含数字');
            return;
        }

        if ($this->requireLetter && !preg_match('/[a-zA-Z]/', $value)) {
            $fail('密码必须包含字母');
            return;
        }

        if ($this->requireSpecialChar && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $value)) {
            $fail('密码必须包含特殊字符');
            return;
        }
    }
}

