<?php

namespace App\Models;

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
    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?: 0;
    }

    /**
     * Get review count
     */
    public function getReviewCountAttribute()
    {
        return $this->reviews()->count();
    }

    /**
     * Scope for searching near coordinates
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
        ->having('distance', '<', $radiusKm)
        ->orderBy('distance');
    }

    /**
     * Scope for open shops only
     */
    public function scopeOpen($query)
    {
        return $query->where('is_closed', false);
    }
}
