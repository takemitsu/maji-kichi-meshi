<?php

namespace App\Http\Resources;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

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
            'id' => $this->id,
            'rating' => $this->rating,
            'repeat_intention' => $this->repeat_intention,
            'repeat_intention_text' => $this->repeat_intention_text,
            'memo' => $this->memo,
            'visited_at' => $this->visited_at->format('Y-m-d'),
            'has_images' => $this->hasImages(),
            'images' => ReviewImageResource::collection($this->whenLoaded('publishedImages')),
            'user' => new UserResource($this->whenLoaded('user')),
            'shop' => $this->whenLoaded('shop', function () {
                return [
                    'id' => $this->shop->id,
                    'name' => $this->shop->name,
                    'address' => $this->shop->address,
                    'images' => $this->shop->publishedImages->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'urls' => $image->urls,
                            'sort_order' => $image->sort_order,
                        ];
                    }),
                ];
            }),
            'likes_count' => $this->whenLoaded('likes', function () {
                return $this->likes->count();
            }, 0),
            'is_liked' => $this->when(Auth::check() && $this->relationLoaded('likes'), function () {
                return $this->likes->contains('user_id', Auth::id());
            }, false),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
