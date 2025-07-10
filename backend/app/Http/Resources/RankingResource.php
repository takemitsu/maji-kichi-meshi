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
            'id' => $this->id,
            'rank_position' => $this->rank_position,
            'title' => $this->title,
            'description' => $this->description,
            'is_public' => $this->is_public,
            'user' => new UserResource($this->whenLoaded('user')),
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
