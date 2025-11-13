<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Carbon\Carbon;

class DateRange implements ValidationRule
{
    /**
     * 开始日期字段名
     *
     * @var string
     */
    protected string $startDateField;

    /**
     * 结束日期字段名
     *
     * @var string
     */
    protected string $endDateField;

    /**
     * Create a new rule instance.
     *
     * @param string $startDateField
     * @param string $endDateField
     */
    public function __construct(string $startDateField = 'start_date', string $endDateField = 'end_date')
    {
        $this->startDateField = $startDateField;
        $this->endDateField = $endDateField;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $data = request()->all();
        $startDate = $data[$this->startDateField] ?? null;
        $endDate = $data[$this->endDateField] ?? null;

        if (!$startDate || !$endDate) {
            return; // 让 required 规则处理
        }

        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            if ($start->gt($end)) {
                $fail('开始日期不能大于结束日期');
            }
        } catch (\Exception $e) {
            $fail('日期格式不正确');
        }
    }
}

