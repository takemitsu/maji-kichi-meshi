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
        return $this->thumbnail_path ? Storage::disk('public')->url($this->thumbnail_path) : null;
    }

    /**
     * Get full URL for small image
     */
    public function getSmallUrlAttribute()
    {
        return $this->small_path ? Storage::disk('public')->url($this->small_path) : null;
    }

    /**
     * Get full URL for medium image
     */
    public function getMediumUrlAttribute()
    {
        return $this->medium_path ? Storage::disk('public')->url($this->medium_path) : null;
    }

    /**
     * Get full URL for large image
     */
    public function getLargeUrlAttribute()
    {
        return $this->large_path ? Storage::disk('public')->url($this->large_path) : null;
    }

    /**
     * Get all image URLs as array
     */
    public function getUrlsAttribute()
    {
        return [
            'thumbnail' => $this->thumbnail_url,
            'small' => $this->small_url,
            'medium' => $this->medium_url,
            'large' => $this->large_url,
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
            'large_path' => $uploadResult['paths']['large'],
            'file_size' => $uploadResult['size'],
            'mime_type' => $uploadResult['mime_type'],
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
        ]);

        $imageService = new ImageService;

        return $imageService->deleteImages($paths);
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
