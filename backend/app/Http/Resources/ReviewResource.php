<?php

namespace App\Http\Resources;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Review $resource
 */
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
            'id' => $this->resource->id,
            'rating' => $this->resource->rating,
            'repeat_intention' => $this->resource->repeat_intention,
            'repeat_intention_text' => $this->resource->repeat_intention_text,
            'memo' => $this->resource->memo,
            'visited_at' => $this->resource->visited_at->format('Y-m-d'),
            'has_images' => $this->resource->hasImages(),
            'images' => ReviewImageResource::collection($this->whenLoaded('images')),
            'user' => [
                'id' => $this->resource->user_id,
                'name' => $this->whenLoaded('user', fn () => $this->resource->user->name),
            ],
            'shop' => [
                'id' => $this->resource->shop_id,
                'name' => $this->whenLoaded('shop', fn () => $this->resource->shop->name),
            ],
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
