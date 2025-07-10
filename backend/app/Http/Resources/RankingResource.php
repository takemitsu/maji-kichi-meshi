<?php

namespace App\Http\Resources;

use App\Models\Ranking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Ranking $resource
 */
class RankingResource extends JsonResource
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
            'rank_position' => $this->resource->rank_position,
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'is_public' => $this->resource->is_public,
            'user' => new UserResource($this->whenLoaded('user')),
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
