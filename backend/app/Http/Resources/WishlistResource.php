<?php

namespace App\Http\Resources;

use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Wishlist $resource
 */
class WishlistResource extends JsonResource
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
            'user_id' => $this->user_id,
            'shop_id' => $this->shop_id,
            'status' => $this->status,
            'priority' => $this->priority,
            'priority_label' => $this->priority_label,
            'source_type' => $this->source_type,
            'visited_at' => $this->visited_at?->format('Y-m-d'),
            'memo' => $this->memo,
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'source_user' => new UserResource($this->whenLoaded('sourceUser')),
            'source_review' => $this->whenLoaded('sourceReview', function () {
                return [
                    'id' => $this->sourceReview->id,
                    'rating' => $this->sourceReview->rating,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
