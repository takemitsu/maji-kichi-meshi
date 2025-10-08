<?php

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Category $resource
 */
class CategoryResource extends JsonResource
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
            'slug' => $this->slug,
            'type' => $this->type,
            'shops_count' => $this->when($this->relationLoaded('shops'), $this->shops->count()),
            'shops' => ShopResource::collection($this->whenLoaded('shops')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
