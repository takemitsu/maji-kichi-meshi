<?php

namespace App\Models;

use App\Services\ImageService;
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
        'mime_type',
        'file_size',
        'image_sizes',
        'status',
        'moderated_by',
        'moderated_at',
        'sort_order',
    ];

    protected $casts = [
        'image_sizes' => 'array',
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
        return $query->where('status', 'published');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
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
        return $this->status === 'published';
    }

    public function isUnderReview(): bool
    {
        return $this->status === 'under_review';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function getUrlsAttribute(): array
    {
        return $this->image_sizes ?? [];
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->image_sizes['thumbnail'] ?? null;
    }

    public function getSmallUrlAttribute(): ?string
    {
        return $this->image_sizes['small'] ?? null;
    }

    public function getMediumUrlAttribute(): ?string
    {
        return $this->image_sizes['medium'] ?? null;
    }

    public function getLargeUrlAttribute(): ?string
    {
        return $this->image_sizes['large'] ?? null;
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
        $this->status = $status;
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
            $uuid = Str::uuid();
            $imageService = new ImageService;

            // Process and save images using existing uploadAndResize method
            $imageData = $imageService->uploadAndResize($file, 'shops');

            // Transform paths to URLs for frontend
            $imageUrls = [];
            foreach ($imageData['paths'] as $size => $path) {
                $imageUrls[$size] = $imageService->getImageUrl($path);
            }

            $shopImage = self::create([
                'shop_id' => $shopId,
                'uuid' => $uuid,
                'filename' => $imageData['filename'],
                'original_name' => $imageData['original_name'],
                'mime_type' => $imageData['mime_type'],
                'file_size' => $imageData['size'],
                'image_sizes' => $imageUrls,
                'status' => 'published', // Auto-approve for now
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
            $imageService = new ImageService;

            // Get all image paths from the stored URLs
            $paths = [];
            foreach ($this->image_sizes as $size => $url) {
                // Convert URL back to path
                $path = str_replace('/storage/', '', parse_url($url, PHP_URL_PATH));
                $paths[] = $path;
            }

            $imageService->deleteImages($paths);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to delete shop image files: ' . $e->getMessage(), [
                'shop_image_id' => $this->id,
                'uuid' => $this->uuid,
            ]);

            return false;
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($shopImage) {
            $shopImage->deleteFiles();
        });
    }
}
