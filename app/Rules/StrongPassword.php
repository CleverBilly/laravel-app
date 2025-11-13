<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 强密码验证规则
 *
 * 验证密码强度，支持自定义要求
 */
class StrongPassword implements ValidationRule
{
    protected int $minLength;
    protected bool $requireUppercase;
    protected bool $requireLowercase;
    protected bool $requireNumbers;
    protected bool $requireSpecialChars;

    /**
     * 构造函数
     *
     * @param int $minLength 最小长度
     * @param bool $requireUppercase 是否需要大写字母
     * @param bool $requireLowercase 是否需要小写字母
     * @param bool $requireNumbers 是否需要数字
     * @param bool $requireSpecialChars 是否需要特殊字符
     */
    public function __construct(
        int $minLength = 8,
        bool $requireUppercase = true,
        bool $requireLowercase = true,
        bool $requireNumbers = true,
        bool $requireSpecialChars = false
    ) {
        $this->minLength = $minLength;
        $this->requireUppercase = $requireUppercase;
        $this->requireLowercase = $requireLowercase;
        $this->requireNumbers = $requireNumbers;
        $this->requireSpecialChars = $requireSpecialChars;
    }

    /**
     * 验证规则
     *
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('密码必须是字符串');
            return;
        }

        // 检查长度
        if (strlen($value) < $this->minLength) {
            $fail("密码长度至少需要 {$this->minLength} 位");
            return;
        }

        // 检查大写字母
        if ($this->requireUppercase && !preg_match('/[A-Z]/', $value)) {
            $fail('密码必须包含至少一个大写字母 (A-Z)');
            return;
        }

        // 检查小写字母
        if ($this->requireLowercase && !preg_match('/[a-z]/', $value)) {
            $fail('密码必须包含至少一个小写字母 (a-z)');
            return;
        }

        // 检查数字
        if ($this->requireNumbers && !preg_match('/[0-9]/', $value)) {
            $fail('密码必须包含至少一个数字 (0-9)');
            return;
        }

        // 检查特殊字符
        if ($this->requireSpecialChars && !preg_match('/[^A-Za-z0-9]/', $value)) {
            $fail('密码必须包含至少一个特殊字符 (!@#$%^&*等)');
            return;
        }

        // 检查常见弱密码
        $weakPasswords = [
            'password', 'password123', '12345678', '87654321',
            'qwerty', 'qwerty123', 'abc123', 'abcd1234',
            '123456', '1234567', '123456789', '1234567890',
            'admin', 'admin123', 'root', 'root123',
            'guest', 'test', 'test123', 'user', 'user123',
        ];

        if (in_array(strtolower($value), $weakPasswords, true)) {
            $fail('密码过于简单，请使用更复杂的密码');
            return;
        }

        // 检查重复字符
        if (preg_match('/(.)\1{3,}/', $value)) {
            $fail('密码不能包含连续重复的字符（如 aaaa、1111）');
            return;
        }

        // 检查连续字符
        if ($this->hasSequentialChars($value)) {
            $fail('密码不能包含连续字符序列（如 1234、abcd）');
            return;
        }
    }

    /**
     * 检查是否包含连续字符
     *
     * @param string $password
     * @return bool
     */
    protected function hasSequentialChars(string $password): bool
    {
        $sequences = [
            '0123456789', 'abcdefghijklmnopqrstuvwxyz',
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'qwertyuiop', 'asdfghjkl', 'zxcvbnm',
        ];

        foreach ($sequences as $sequence) {
            // 检查正序和倒序
            for ($i = 0; $i < strlen($sequence) - 3; $i++) {
                $substr = substr($sequence, $i, 4);
                $reversed = strrev($substr);

                if (stripos($password, $substr) !== false || stripos($password, $reversed) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 创建默认密码规则（常规强度）
     *
     * @return static
     */
    public static function default(): static
    {
        return new static(
            minLength: 8,
            requireUppercase: true,
            requireLowercase: true,
            requireNumbers: true,
            requireSpecialChars: false
        );
    }

    /**
     * 创建高强度密码规则
     *
     * @return static
     */
    public static function strong(): static
    {
        return new static(
            minLength: 12,
            requireUppercase: true,
            requireLowercase: true,
            requireNumbers: true,
            requireSpecialChars: true
        );
    }

    /**
     * 创建宽松密码规则
     *
     * @return static
     */
    public static function relaxed(): static
    {
        return new static(
            minLength: 6,
            requireUppercase: false,
            requireLowercase: true,
            requireNumbers: true,
            requireSpecialChars: false
        );
    }
}

