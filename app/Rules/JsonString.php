<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;

class JsonString implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('必须是字符串');
            return;
        }

        json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $fail('JSON 格式不正确');
        }
    }
}

