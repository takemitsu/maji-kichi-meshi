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
            'title' => $this->title,
            'description' => $this->description,
            'is_public' => $this->is_public,
            'user' => new UserResource($this->whenLoaded('user')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'shops' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return array_merge(
                        (new ShopResource($item->shop))->resolve(),
                        [
                            'rank_position' => $item->rank_position,
                            'comment' => $item->comment,
                        ]
                    );
                });
            }),
            'shops_count' => $this->whenLoaded('items', function () {
                return $this->items->count();
            }) ?? 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
