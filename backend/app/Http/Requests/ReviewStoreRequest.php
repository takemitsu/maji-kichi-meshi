<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewStoreRequest extends FormRequest
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
        // フロントエンドからのcommentをmemoに変換
        if ($this->has('comment')) {
            $this->merge([
                'memo' => $this->get('comment'),
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
        return [
            'shop_id' => 'required|exists:shops,id',
            'rating' => 'required|integer|min:1|max:5',
            'repeat_intention' => 'required|in:yes,maybe,no',
            'memo' => 'nullable|string|max:1000',
            'visited_at' => 'required|date|before_or_equal:today',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB
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
            'shop_id.required' => '店舗の選択は必須です',
            'shop_id.exists' => '選択された店舗が見つかりません',
            'rating.required' => '評価は必須です',
            'rating.min' => '評価は1以上である必要があります',
            'rating.max' => '評価は5以下である必要があります',
            'repeat_intention.required' => 'リピート意向は必須です',
            'repeat_intention.in' => 'リピート意向はyes、maybe、noのいずれかである必要があります',
            'memo.max' => 'メモは1000文字以内で入力してください',
            'visited_at.required' => '訪問日は必須です',
            'visited_at.before_or_equal' => '訪問日は今日以前の日付である必要があります',
            'images.max' => '画像は最大5枚までです',
            'images.*.image' => 'アップロードされたファイルは画像形式である必要があります',
            'images.*.mimes' => '画像形式はjpeg、png、jpg、gif、webpのいずれかである必要があります',
            'images.*.max' => '画像サイズは10MB以下である必要があります',
        ];
    }
}
