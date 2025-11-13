<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;

class RegisterRequest extends BaseFormRequest
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
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => '用户名不能为空',
            'name.max' => '用户名长度不能超过255个字符',
            'email.required' => '邮箱不能为空',
            'email.email' => '邮箱格式不正确',
            'email.unique' => '该邮箱已被注册',
            'password.required' => '密码不能为空',
            'password.min' => '密码长度至少为6位',
            'password.confirmed' => '两次输入的密码不一致',
        ];
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    protected function getValidationErrorMessage(): string
    {
        return '注册信息验证失败';
    }
}

