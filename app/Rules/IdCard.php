<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;

class IdCard implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        // 18位身份证号验证
        if (!preg_match('/^[1-9]\d{5}(18|19|20)\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])\d{3}[\dXx]$/', $value)) {
            $fail('身份证号格式不正确');
        }
    }
}

