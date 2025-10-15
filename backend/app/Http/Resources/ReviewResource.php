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
                    'wishlist_status' => $this->getShopWishlistStatus(),
                ];
            }),
            'likes_count' => $this->likes_count ?? 0,
            'is_liked' => $this->when($this->relationLoaded('likes') && !$this->likes->isEmpty(), function () {
                return true; // Controller側で既に現在のユーザーでフィルタ済み
            }, false),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get wishlist status for the shop.
     *
     * @return array<string, mixed>
     */
    protected function getShopWishlistStatus(): array
    {
        // wishlists が load されていない場合
        if (!$this->shop->relationLoaded('wishlists')) {
            return ['in_wishlist' => false];
        }

        // wishlists が空 = ログインしていないか、このユーザーの wishlist がない
        if ($this->shop->wishlists->isEmpty()) {
            return ['in_wishlist' => false];
        }

        // Controller 側で既に現在のユーザーでフィルタ済み
        $wishlist = $this->shop->wishlists->first();

        return [
            'in_wishlist' => true,
            'priority' => $wishlist->priority,
            'priority_label' => $wishlist->priority_label,
            'status' => $wishlist->status,
        ];
    }
}
