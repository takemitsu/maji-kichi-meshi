<?php

namespace App\Services;

use App\Models\Review;
use App\Models\Shop;
use Illuminate\Support\Facades\DB;

class ImageUploadService
{
    /**
     * Upload multiple images for a model (Shop or Review).
     *
     * @param  Shop|Review  $model  The model to attach images to (Shop or Review)
     * @param  array  $imageFiles  Array of UploadedFile instances
     * @param  int  $maxImages  Maximum allowed images for this model type
     * @return array Array of created image models
     *
     * @throws \Exception
     */
    public function uploadImages(Shop|Review $model, array $imageFiles, int $maxImages = 10): array
    {
        // Check current image count
        $currentImageCount = $model->images()->count();
        $newImageCount = count($imageFiles);

        if ($currentImageCount + $newImageCount > $maxImages) {
            throw new \Exception("Maximum {$maxImages} images allowed");
        }

        $imageModelClass = $this->getImageModelClass($model);
        $uploadedImages = [];

        DB::transaction(function () use ($model, $imageFiles, &$uploadedImages, $currentImageCount, $imageModelClass) {
            $sortOrder = $currentImageCount;

            foreach ($imageFiles as $imageFile) {
                $image = $imageModelClass::createFromUpload($model->id, $imageFile, $sortOrder);
                $uploadedImages[] = $image;
                $sortOrder++;
            }
        });

        return $uploadedImages;
    }

    /**
     * Delete an image.
     *
     * @param  \App\Models\ShopImage|\App\Models\ReviewImage  $image
     */
    public function deleteImage($image): bool
    {
        return $image->delete();
    }

    /**
     * Reorder images for a model.
     *
     * @param  Shop|Review  $model  The parent model (Shop or Review)
     * @param  array  $imageIds  Array of image IDs in desired order
     */
    public function reorderImages(Shop|Review $model, array $imageIds): void
    {
        $imageModelClass = $this->getImageModelClass($model);
        $foreignKeyName = $this->getForeignKeyName($model);

        DB::transaction(function () use ($model, $imageIds, $imageModelClass, $foreignKeyName) {
            foreach ($imageIds as $index => $imageId) {
                $imageModelClass::where('id', $imageId)
                    ->where($foreignKeyName, $model->id)
                    ->update(['sort_order' => $index]);
            }
        });
    }

    /**
     * Get the image model class based on parent model type.
     */
    protected function getImageModelClass(Shop|Review $model): string
    {
        $modelClass = get_class($model);

        return match ($modelClass) {
            \App\Models\Shop::class => \App\Models\ShopImage::class,
            \App\Models\Review::class => \App\Models\ReviewImage::class,
            default => throw new \InvalidArgumentException("Unsupported model type: {$modelClass}"),
        };
    }

    /**
     * Get the foreign key name based on parent model type.
     */
    protected function getForeignKeyName(Shop|Review $model): string
    {
        $modelClass = get_class($model);

        return match ($modelClass) {
            \App\Models\Shop::class => 'shop_id',
            \App\Models\Review::class => 'review_id',
            default => throw new \InvalidArgumentException("Unsupported model type: {$modelClass}"),
        };
    }
}
