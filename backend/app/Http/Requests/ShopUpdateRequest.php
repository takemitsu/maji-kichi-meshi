<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShopUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
    public function rules(): array
    {
        $shopId = $this->route('shop')?->id;

        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:500',
            'google_place_id' => 'nullable|string|unique:shops,google_place_id,' . $shopId,
            'is_closed' => 'sometimes|boolean',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
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
            'name.required' => '店舗名は必須です',
            'name.max' => '店舗名は255文字以内で入力してください',
            'latitude.between' => '緯度は-90から90の範囲で入力してください',
            'longitude.between' => '経度は-180から180の範囲で入力してください',
            'website.url' => '有効なURLを入力してください',
            'google_place_id.unique' => 'このGoogle Place IDは既に登録されています',
            'category_ids.*.exists' => '無効なカテゴリIDが含まれています',
        ];
    }
}
