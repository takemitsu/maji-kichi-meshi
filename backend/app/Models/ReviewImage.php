<?php

namespace App\Models;

use App\Services\ImageService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReviewImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'uuid',
        'filename',
        'original_name',
        'thumbnail_path',
        'small_path',
        'medium_path',
        'large_path',
        'original_path',
        'file_size',
        'mime_type',
        'moderation_status',
        'moderation_notes',
        'moderated_by',
        'moderated_at',
        'sizes_generated',
    ];

    protected $casts = [
        'sizes_generated' => 'array',
        'moderated_at' => 'datetime',
    ];

    protected $hidden = [
        'moderated_by',
        'moderated_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Review relationship
     */
    public function review()
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * Moderator relationship
     */
    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * Get all image URLs as array
     */
    protected function urls(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'thumbnail' => config('app.url') . "/api/images/reviews/thumbnail/{$this->filename}",
                'small' => config('app.url') . "/api/images/reviews/small/{$this->filename}",
                'medium' => config('app.url') . "/api/images/reviews/medium/{$this->filename}",
                'original' => config('app.url') . "/api/images/reviews/original/{$this->filename}",
                // 後方互換性のためlargeも提供（originalと同じ）
                'large' => config('app.url') . "/api/images/reviews/original/{$this->filename}",
            ],
        );
    }

    /**
     * Create ReviewImage from uploaded file
     */
    public static function createFromUpload(int $reviewId, UploadedFile $file): self
    {
        $imageService = app(ImageService::class);

        // 画像ファイルのバリデーション
        if (!$imageService->isSupportedImageType($file->getMimeType())) {
            throw new \InvalidArgumentException('Unsupported image type: ' . $file->getMimeType());
        }

        if (!$imageService->isValidSize($file->getSize())) {
            throw new \InvalidArgumentException('Image file size too large');
        }

        // UUIDを生成
        $uuid = Str::uuid()->toString();

        // 画像をアップロード・リサイズ（UUIDを渡してファイル名と統一）
        $uploadResult = $imageService->uploadAndResize($file, 'reviews', $uuid);

        // ReviewImageを作成
        return self::create([
            'review_id' => $reviewId,
            'uuid' => $uuid,
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

        $imageService = app(ImageService::class);

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

    // =============================================================================
    // Moderation Methods
    // =============================================================================

    /**
     * Approve the image
     */
    public function approve($moderatorId = null): bool
    {
        return $this->updateModerationStatus('published', $moderatorId);
    }

    /**
     * Reject the image
     */
    public function reject($moderatorId = null): bool
    {
        return $this->updateModerationStatus('rejected', $moderatorId);
    }

    /**
     * Mark for review
     */
    public function requireReview($moderatorId = null): bool
    {
        return $this->updateModerationStatus('under_review', $moderatorId);
    }

    /**
     * Update moderation status
     */
    private function updateModerationStatus(string $status, $moderatorId = null): bool
    {
        $this->moderation_status = $status;
        $this->moderated_by = $moderatorId;
        $this->moderated_at = now();

        return $this->save();
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
