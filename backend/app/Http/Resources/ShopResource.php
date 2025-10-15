<?php

namespace App\Http\Resources;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Shop $resource
 */
class ShopResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'phone' => $this->phone,
            'website' => $this->website,
            'google_place_id' => $this->google_place_id,
            'is_closed' => $this->is_closed,
            'average_rating' => round($this->average_rating, 1),
            'review_count' => $this->review_count,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'images' => $this->when(
                $this->relationLoaded('publishedImages'),
                function () {
                    return $this->publishedImages->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'urls' => $image->urls,
                            'sort_order' => $image->sort_order,
                        ];
                    });
                }
            ),
            'distance' => $this->when(isset($this->distance) && $this->distance !== null, round($this->distance, 2)),
            'wishlist_status' => $this->getWishlistStatus(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get wishlist status for the shop.
     *
     * @return array<string, mixed>
     */
    protected function getWishlistStatus(): array
    {
        // wishlists が load されていない場合
        if (!$this->relationLoaded('wishlists')) {
            return ['in_wishlist' => false];
        }

        // wishlists が空 = ログインしていないか、このユーザーの wishlist がない
        if ($this->wishlists->isEmpty()) {
            return ['in_wishlist' => false];
        }

        // Controller 側で既に現在のユーザーでフィルタ済み
        $wishlist = $this->wishlists->first();

        return [
            'in_wishlist' => true,
            'priority' => $wishlist->priority,
            'priority_label' => $wishlist->priority_label,
            'status' => $wishlist->status,
        ];
    }
}
