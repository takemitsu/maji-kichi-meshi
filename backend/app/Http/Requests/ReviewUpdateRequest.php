<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewUpdateRequest extends FormRequest
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
            'rating' => 'sometimes|required|integer|min:1|max:5',
            'repeat_intention' => 'sometimes|required|in:yes,maybe,no',
            'memo' => 'nullable|string|max:1000',
            'visited_at' => 'sometimes|required|date|before_or_equal:today',
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
            'rating.required' => '評価は必須です',
            'rating.min' => '評価は1以上である必要があります',
            'rating.max' => '評価は5以下である必要があります',
            'repeat_intention.required' => 'リピート意向は必須です',
            'repeat_intention.in' => 'リピート意向はyes、maybe、noのいずれかである必要があります',
            'memo.max' => 'メモは1000文字以内で入力してください',
            'visited_at.required' => '訪問日は必須です',
            'visited_at.before_or_equal' => '訪問日は今日以前の日付である必要があります',
        ];
    }
}
