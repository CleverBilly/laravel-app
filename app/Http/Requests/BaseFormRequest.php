<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Exceptions\ValidationException;

abstract class BaseFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    abstract public function rules(): array;

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        //
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \App\Exceptions\ValidationException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new ValidationException(
            $this->getValidationErrorMessage(),
            $validator,
            $validator->errors()->toArray()
        );
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    protected function getValidationErrorMessage(): string
    {
        return '验证失败';
    }

    /**
     * Get validated data with defaults.
     *
     * @param array $defaults
     * @return array
     */
    public function validatedWithDefaults(array $defaults = []): array
    {
        return array_merge($defaults, $this->validated());
    }

    /**
     * Get a single validated value or default.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getValidated(string $key, $default = null)
    {
        return $this->validated()[$key] ?? $default;
    }
}

