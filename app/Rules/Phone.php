<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;

class Phone implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        // 中国大陆手机号验证
        if (!preg_match('/^1[3-9]\d{9}$/', $value)) {
            $fail('手机号格式不正确');
        }
    }
}

