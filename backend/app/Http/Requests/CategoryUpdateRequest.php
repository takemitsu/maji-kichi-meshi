<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class CategoryUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-generate slug if name changed but slug not provided
        if ($this->has('name') && !$this->has('slug')) {
            $this->merge([
                'slug' => Str::slug($this->input('name')),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'name' => 'sometimes|required|string|max:255|unique:categories,name,' . $categoryId,
            'slug' => 'sometimes|required|string|max:255|unique:categories,slug,' . $categoryId,
            'type' => 'sometimes|required|in:basic,time,ranking',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'カテゴリ名は必須です',
            'name.max' => 'カテゴリ名は255文字以内で入力してください',
            'name.unique' => 'このカテゴリ名は既に使用されています',
            'slug.required' => 'スラッグは必須です',
            'slug.max' => 'スラッグは255文字以内で入力してください',
            'slug.unique' => 'このスラッグは既に使用されています',
            'type.required' => 'タイプは必須です',
            'type.in' => 'タイプはbasic、time、rankingのいずれかである必要があります',
        ];
    }
}
