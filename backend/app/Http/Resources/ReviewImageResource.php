<?php

namespace App\Http\Resources;

use App\Models\ReviewImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ReviewImage $resource
 */
class ReviewImageResource extends JsonResource
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
            'filename' => $this->resource->filename,
            'original_name' => $this->resource->original_name,
            'urls' => $this->resource->urls,
            'file_size' => $this->resource->file_size,
            'mime_type' => $this->resource->mime_type,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
