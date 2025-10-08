<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewIndexRequest extends FormRequest
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
        return [
            'user_id' => 'sometimes|exists:users,id',
            'shop_id' => 'sometimes|exists:shops,id',
            'rating' => 'sometimes|integer|min:1|max:5',
            'repeat_intention' => 'sometimes|in:yes,maybe,no',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'recent_only' => 'sometimes|in:true,false,1,0',
            'recent_days' => 'sometimes|integer|min:1|max:365',
            'per_page' => 'sometimes|integer|min:1|max:50',
        ];
    }
}
