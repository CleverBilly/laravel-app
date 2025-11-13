<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;

class ImageBase64 implements ValidationRule
{
    /**
     * 允许的图片格式
     *
     * @var array
     */
    protected array $allowedFormats;

    /**
     * 最大文件大小（字节）
     *
     * @var int
     */
    protected int $maxSize;

    /**
     * Create a new rule instance.
     *
     * @param array $allowedFormats
     * @param int $maxSize
     */
    public function __construct(array $allowedFormats = ['jpg', 'jpeg', 'png', 'gif'], int $maxSize = 5242880)
    {
        $this->allowedFormats = $allowedFormats;
        $this->maxSize = $maxSize;
    }

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

        // 检查是否是 base64 格式
        if (!preg_match('/^data:image\/(\w+);base64,/', $value, $matches)) {
            $fail('图片格式不正确，必须是 base64 编码的图片');
            return;
        }

        $format = strtolower($matches[1]);
        if (!in_array($format, $this->allowedFormats)) {
            $allowed = implode(', ', $this->allowedFormats);
            $fail("图片格式只能是：{$allowed}");
            return;
        }

        // 解码 base64 获取文件大小
        $base64Data = substr($value, strpos($value, ',') + 1);
        $imageData = base64_decode($base64Data, true);

        if ($imageData === false) {
            $fail('Base64 解码失败');
            return;
        }

        $size = strlen($imageData);
        if ($size > $this->maxSize) {
            $maxSizeMB = round($this->maxSize / 1024 / 1024, 2);
            $fail("图片大小不能超过 {$maxSizeMB}MB");
        }
    }
}

