<?php

namespace App\Models;

use App\Services\ImageService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShopImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
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
        'image_sizes',
        'sort_order',
    ];

    protected $casts = [
        'sizes_generated' => 'array',
        'moderated_at' => 'datetime',
    ];

    protected $hidden = [
        'filename',
        'moderated_by',
        'moderated_at',
    ];

    // =============================================================================
    // Relationships
    // =============================================================================

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopePublished($query)
    {
        return $query->where('moderation_status', 'published');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('moderation_status', 'under_review');
    }

    public function scopeRejected($query)
    {
        return $query->where('moderation_status', 'rejected');
    }

    public function scopeByShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    // =============================================================================
    // Helper Methods
    // =============================================================================

    public function isPublished(): bool
    {
        return $this->moderation_status === 'published';
    }

    public function isUnderReview(): bool
    {
        return $this->moderation_status === 'under_review';
    }

    public function isRejected(): bool
    {
        return $this->moderation_status === 'rejected';
    }

    protected function urls(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'thumbnail' => config('app.url') . "/api/images/shops/thumbnail/{$this->filename}",
                'small' => config('app.url') . "/api/images/shops/small/{$this->filename}",
                'medium' => config('app.url') . "/api/images/shops/medium/{$this->filename}",
                'original' => config('app.url') . "/api/images/shops/original/{$this->filename}",
                // 後方互換性のためlargeも提供（originalと同じ）
                'large' => config('app.url') . "/api/images/shops/original/{$this->filename}",
            ],
        );
    }

    // =============================================================================
    // Moderation Methods
    // =============================================================================

    public function approve($moderatorId = null): bool
    {
        return $this->updateModerationStatus('published', $moderatorId);
    }

    public function reject($moderatorId = null): bool
    {
        return $this->updateModerationStatus('rejected', $moderatorId);
    }

    public function requireReview($moderatorId = null): bool
    {
        return $this->updateModerationStatus('under_review', $moderatorId);
    }

    private function updateModerationStatus(string $status, $moderatorId = null): bool
    {
        $this->moderation_status = $status;
        $this->moderated_by = $moderatorId;
        $this->moderated_at = now();

        return $this->save();
    }

    // =============================================================================
    // File Upload Methods
    // =============================================================================

    public static function createFromUpload(int $shopId, UploadedFile $file, int $sortOrder = 0): self
    {
        DB::beginTransaction();
        try {
            $uuid = Str::uuid()->toString();
            $imageService = app(ImageService::class);

            // Process and save images using existing uploadAndResize method
            // UUIDを渡してファイル名と統一
            $imageData = $imageService->uploadAndResize($file, 'shops', $uuid);

            $shopImage = self::create([
                'shop_id' => $shopId,
                'uuid' => $uuid,
                'filename' => $imageData['filename'],
                'original_name' => $imageData['original_name'],
                'thumbnail_path' => $imageData['paths']['thumbnail'],
                'small_path' => $imageData['paths']['small'],
                'medium_path' => $imageData['paths']['medium'],
                'large_path' => null, // largeサイズは廃止
                'original_path' => $imageData['original_path'],
                'file_size' => $imageData['size'],
                'mime_type' => $imageData['mime_type'],
                'moderation_status' => 'published', // Auto-approve for now
                'sizes_generated' => $imageData['sizes_generated'],
                'image_sizes' => json_encode([
                    'thumbnail' => "/storage/images/shops/thumbnail/{$imageData['filename']}",
                    'small' => "/storage/images/shops/small/{$imageData['filename']}",
                    'medium' => "/storage/images/shops/medium/{$imageData['filename']}",
                    'large' => "/storage/images/shops/large/{$imageData['filename']}",
                ]),
                'sort_order' => $sortOrder,
            ]);

            DB::commit();

            return $shopImage;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // =============================================================================
    // Cleanup Methods
    // =============================================================================

    public function deleteFiles(): bool
    {
        try {
            $paths = array_filter([
                $this->thumbnail_path,
                $this->small_path,
                $this->medium_path,
                $this->large_path,
                $this->original_path,
            ]);

            $imageService = app(ImageService::class);

            return $imageService->deleteImages($paths);
        } catch (\Exception $e) {
            \Log::error('Failed to delete shop image files: ' . $e->getMessage(), [
                'shop_image_id' => $this->id,
                'uuid' => $this->uuid,
            ]);

            return false;
        }
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

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($shopImage) {
            $shopImage->deleteFiles();
        });
    }
}
