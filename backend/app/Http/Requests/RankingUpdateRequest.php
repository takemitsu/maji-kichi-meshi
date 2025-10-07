<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RankingUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user owns the ranking
        $ranking = $this->route('ranking');

        return $ranking && $ranking->user_id === auth('api')->id();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'is_public' => 'boolean',
            'shops' => 'required|array|min:1|max:10',
            'shops.*.shop_id' => 'required|exists:shops,id',
            'shops.*.position' => 'required|integer|min:1',
            'shops.*.comment' => 'nullable|string|max:200',
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
            'title.required' => 'タイトルは必須です',
            'title.max' => 'タイトルは255文字以内で入力してください',
            'category_id.exists' => '無効なカテゴリIDです',
            'shops.required' => '店舗の選択は必須です',
            'shops.min' => '最低1店舗を選択してください',
            'shops.max' => '店舗は最大10店舗までです',
            'shops.*.shop_id.required' => '店舗IDは必須です',
            'shops.*.shop_id.exists' => '無効な店舗IDです',
            'shops.*.position.required' => '順位は必須です',
            'shops.*.position.min' => '順位は1以上である必要があります',
            'shops.*.comment.max' => 'コメントは200文字以内で入力してください',
        ];
    }
}
