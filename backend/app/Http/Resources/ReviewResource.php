<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'repeat_intention' => $this->repeat_intention,
            'repeat_intention_text' => $this->repeat_intention_text,
            'memo' => $this->memo,
            'visited_at' => $this->visited_at?->format('Y-m-d'),
            'has_images' => $this->hasImages(),
            'images' => ReviewImageResource::collection($this->whenLoaded('images')),
            'user' => [
                'id' => $this->user_id,
                'name' => $this->whenLoaded('user', fn () => $this->user->name),
            ],
            'shop' => [
                'id' => $this->shop_id,
                'name' => $this->whenLoaded('shop', fn () => $this->shop->name),
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
