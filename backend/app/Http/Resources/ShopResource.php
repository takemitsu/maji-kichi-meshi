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
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'address' => $this->resource->address,
            'latitude' => $this->resource->latitude,
            'longitude' => $this->resource->longitude,
            'phone' => $this->resource->phone,
            'website' => $this->resource->website,
            'google_place_id' => $this->resource->google_place_id,
            'is_closed' => $this->resource->is_closed,
            'average_rating' => round($this->resource->average_rating, 1),
            'review_count' => $this->resource->review_count,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'images' => $this->when(
                $this->resource->relationLoaded('publishedImages'),
                function () {
                    return $this->resource->publishedImages->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'urls' => $image->urls,
                            'sort_order' => $image->sort_order,
                        ];
                    });
                }
            ),
            'distance' => $this->when(isset($this->resource->distance), round($this->resource->distance, 2)),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
