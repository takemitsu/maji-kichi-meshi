<?php

namespace App\Models;

use App\Services\ImageService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ReviewImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'filename',
        'original_name',
        'thumbnail_path',
        'small_path',
        'medium_path',
        'large_path',
        'file_size',
        'mime_type',
        'moderation_status',
        'moderation_notes',
        'sizes_generated',
        'original_path',
    ];

    protected $casts = [
        'sizes_generated' => 'array',
    ];

    /**
     * Review relationship
     */
    public function review()
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * Get full URL for thumbnail image
     */
    public function getThumbnailUrlAttribute()
    {
        if (!$this->thumbnail_path) {
            return;
        }
        $imageService = new ImageService;

        return $imageService->getImageUrl($this->thumbnail_path);
    }

    /**
     * Get full URL for small image
     */
    public function getSmallUrlAttribute()
    {
        if (!$this->small_path) {
            return;
        }
        $imageService = new ImageService;

        return $imageService->getImageUrl($this->small_path);
    }

    /**
     * Get full URL for medium image
     */
    public function getMediumUrlAttribute()
    {
        if (!$this->medium_path) {
            return;
        }
        $imageService = new ImageService;

        return $imageService->getImageUrl($this->medium_path);
    }

    /**
     * Get full URL for large image (legacy)
     */
    public function getLargeUrlAttribute()
    {
        if (!$this->large_path) {
            return;
        }
        $imageService = new ImageService;

        return $imageService->getImageUrl($this->large_path);
    }

    /**
     * Get full URL for original image
     */
    public function getOriginalUrlAttribute()
    {
        $appUrl = config('app.url');

        return "{$appUrl}/api/images/reviews/{$this->id}/original";
    }

    /**
     * Get all image URLs as array
     */
    public function getUrlsAttribute()
    {
        $appUrl = config('app.url');

        return [
            'thumbnail' => "{$appUrl}/api/images/reviews/{$this->id}/thumbnail",
            'small' => "{$appUrl}/api/images/reviews/{$this->id}/small",
            'medium' => "{$appUrl}/api/images/reviews/{$this->id}/medium",
            'original' => "{$appUrl}/api/images/reviews/{$this->id}/original",
            // 後方互換性のためlargeも提供（originalと同じ）
            'large' => "{$appUrl}/api/images/reviews/{$this->id}/original",
        ];
    }

    /**
     * Create ReviewImage from uploaded file
     */
    public static function createFromUpload(int $reviewId, UploadedFile $file): self
    {
        $imageService = new ImageService;

        // 画像ファイルのバリデーション
        if (!$imageService->isSupportedImageType($file->getMimeType())) {
            throw new \InvalidArgumentException('Unsupported image type: ' . $file->getMimeType());
        }

        if (!$imageService->isValidSize($file->getSize())) {
            throw new \InvalidArgumentException('Image file size too large');
        }

        // 画像をアップロード・リサイズ
        $uploadResult = $imageService->uploadAndResize($file, 'reviews');

        // ReviewImageを作成
        return self::create([
            'review_id' => $reviewId,
            'filename' => $uploadResult['filename'],
            'original_name' => $uploadResult['original_name'],
            'thumbnail_path' => $uploadResult['paths']['thumbnail'],
            'small_path' => $uploadResult['paths']['small'],
            'medium_path' => $uploadResult['paths']['medium'],
            'large_path' => null, // largeサイズは廃止
            'file_size' => $uploadResult['size'],
            'mime_type' => $uploadResult['mime_type'],
            'original_path' => $uploadResult['original_path'],
            'sizes_generated' => $uploadResult['sizes_generated'],
        ]);
    }

    /**
     * Delete all image files from storage
     */
    public function deleteFiles()
    {
        $paths = array_filter([
            $this->thumbnail_path,
            $this->small_path,
            $this->medium_path,
            $this->large_path,
            $this->original_path,
        ]);

        $imageService = new ImageService;

        return $imageService->deleteImages($paths);
    }

    /**
     * Check if specific size is generated
     */
    public function isSizeGenerated(string $size): bool
    {
        $sizesGenerated = $this->sizes_generated ?? [];

        return isset($sizesGenerated[$size]) && $sizesGenerated[$size] === true;
    }

    /**
     * Mark specific size as generated
     */
    public function markSizeAsGenerated(string $size): void
    {
        $sizesGenerated = $this->sizes_generated ?? [];
        $sizesGenerated[$size] = true;
        $this->update(['sizes_generated' => $sizesGenerated]);
    }

    /**
     * Relationship with moderator
     */
    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * Check if image is published
     */
    public function isPublished(): bool
    {
        return $this->moderation_status === 'published';
    }

    /**
     * Check if image is under review
     */
    public function isUnderReview(): bool
    {
        return $this->moderation_status === 'under_review';
    }

    /**
     * Check if image is rejected
     */
    public function isRejected(): bool
    {
        return $this->moderation_status === 'rejected';
    }

    /**
     * Scope for published images only
     */
    public function scopePublished($query)
    {
        return $query->where('moderation_status', 'published');
    }

    /**
     * Scope for images under review
     */
    public function scopeUnderReview($query)
    {
        return $query->where('moderation_status', 'under_review');
    }

    /**
     * Scope for rejected images
     */
    public function scopeRejected($query)
    {
        return $query->where('moderation_status', 'rejected');
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Delete files when model is deleted
        static::deleting(function ($reviewImage) {
            $reviewImage->deleteFiles();
        });
    }
}
