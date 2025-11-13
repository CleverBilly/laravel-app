<?php

namespace App\Http\Requests\Example;

use App\Http\Requests\BaseFormRequest;
use App\Rules\Phone;
use App\Rules\Password;

class StoreUserRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', new Phone()],
            'password' => ['required', new Password(minLength: 8, requireNumber: true, requireLetter: true)],
            'age' => ['nullable', 'integer', 'min:1', 'max:150'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => '姓名不能为空',
            'name.max' => '姓名长度不能超过255个字符',
            'email.required' => '邮箱不能为空',
            'email.email' => '邮箱格式不正确',
            'email.unique' => '该邮箱已被注册',
            'phone.required' => '手机号不能为空',
            'password.required' => '密码不能为空',
            'age.integer' => '年龄必须是整数',
            'age.min' => '年龄不能小于1',
            'age.max' => '年龄不能大于150',
            'status.required' => '状态不能为空',
            'status.in' => '状态只能是 active 或 inactive',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => '姓名',
            'email' => '邮箱',
            'phone' => '手机号',
            'password' => '密码',
            'age' => '年龄',
            'status' => '状态',
        ];
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    protected function getValidationErrorMessage(): string
    {
        return '用户信息验证失败';
    }
}

