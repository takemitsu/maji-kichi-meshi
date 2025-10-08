<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'address',
        'latitude',
        'longitude',
        'phone',
        'website',
        'google_place_id',
        'is_closed',
        'status',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_closed' => 'boolean',
    ];

    /**
     * Categories relationship (many-to-many)
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'shop_categories');
    }

    /**
     * Reviews relationship
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Rankings relationship
     */
    public function rankings()
    {
        return $this->hasMany(Ranking::class);
    }

    /**
     * Get average rating from reviews
     */
    protected function averageRating(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->reviews()->avg('rating') ?: 0,
        );
    }

    /**
     * Get review count
     */
    protected function reviewCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->reviews()->count(),
        );
    }

    /**
     * Scope for searching near coordinates
     * SQLite-compatible version using whereRaw instead of having()
     */
    public function scopeNear($query, $latitude, $longitude, $radiusKm = 5)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        return $query->selectRaw("
            *,
            (
                $earthRadius * acos(
                    cos(radians(?))
                    * cos(radians(latitude))
                    * cos(radians(longitude) - radians(?))
                    + sin(radians(?))
                    * sin(radians(latitude))
                )
            ) AS distance
        ", [$latitude, $longitude, $latitude])
            ->whereRaw("
                (
                    $earthRadius * acos(
                        cos(radians(?))
                        * cos(radians(latitude))
                        * cos(radians(longitude) - radians(?))
                        + sin(radians(?))
                        * sin(radians(latitude))
                    )
                ) < ?
            ", [$latitude, $longitude, $latitude, $radiusKm])
            ->orderBy('distance');
    }

    /**
     * Scope for open shops only
     */
    public function scopeOpen($query)
    {
        return $query->where('is_closed', false);
    }

    /**
     * Scope for active shops only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if shop is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if shop is hidden
     */
    public function isHidden(): bool
    {
        return $this->status === 'hidden';
    }

    /**
     * Relationship with moderator
     */
    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * Relationship with images
     */
    public function images()
    {
        return $this->hasMany(ShopImage::class)->ordered();
    }

    /**
     * Relationship with published images only
     */
    public function publishedImages()
    {
        return $this->hasMany(ShopImage::class)->published()->ordered();
    }
}
