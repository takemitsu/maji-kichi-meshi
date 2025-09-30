<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewUploadImagesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user owns the review
        $review = $this->route('review');

        return $review && $review->user_id === auth('api')->id();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'images' => 'required|array|min:1|max:5',
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
            'images.required' => '画像は必須です',
            'images.min' => '最低1枚の画像をアップロードしてください',
            'images.max' => '画像は最大5枚までです',
            'images.*.image' => 'アップロードされたファイルは画像形式である必要があります',
            'images.*.mimes' => '画像形式はjpeg、png、jpg、gif、webpのいずれかである必要があります',
            'images.*.max' => '画像サイズは10MB以下である必要があります',
        ];
    }
}
