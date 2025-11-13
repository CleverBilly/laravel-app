<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;

class ExistsInArray implements ValidationRule
{
    /**
     * 允许的值数组
     *
     * @var array
     */
    protected array $allowedValues;

    /**
     * Create a new rule instance.
     *
     * @param array $allowedValues
     */
    public function __construct(array $allowedValues)
    {
        $this->allowedValues = $allowedValues;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (!in_array($value, $this->allowedValues, true)) {
            $allowed = implode(', ', $this->allowedValues);
            $fail("只能选择以下值：{$allowed}");
        }
    }
}

