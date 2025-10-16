<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShopIndexRequest extends FormRequest
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
            'search' => 'sometimes|string|max:255',
            'category' => 'sometimes|exists:categories,id',
            'open_only' => 'sometimes|in:true,false,1,0',
            'latitude' => 'sometimes|numeric|min:-90|max:90',
            'longitude' => 'sometimes|numeric|min:-180|max:180',
            'radius' => 'sometimes|numeric|min:0.1|max:100',
            'per_page' => 'sometimes|integer|min:1|max:50',
            'sort' => 'sometimes|in:created_at_asc,created_at_desc,review_latest,reviews_count_desc,rating_desc',
        ];
    }
}
