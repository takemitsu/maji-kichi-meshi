<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shop_id',
        'rating',
        'repeat_intention',
        'memo',
        'visited_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'visited_at' => 'date',
    ];

    /**
     * User relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Shop relationship
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Review images relationship
     */
    public function images()
    {
        return $this->hasMany(ReviewImage::class);
    }

    /**
     * Published review images relationship (only approved images)
     */
    public function publishedImages()
    {
        return $this->hasMany(ReviewImage::class)->published();
    }

    /**
     * Scope for filtering by rating
     */
    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope for filtering by repeat intention
     */
    public function scopeByRepeatIntention($query, $intention)
    {
        return $query->where('repeat_intention', $intention);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        $query->where('visited_at', '>=', $startDate);

        if ($endDate) {
            $query->where('visited_at', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Scope for recent reviews
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('visited_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Scope for reviews by specific user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for reviews of specific shop
     */
    public function scopeByShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Get formatted repeat intention text
     */
    public function getRepeatIntentionTextAttribute()
    {
        $intentions = [
            'yes' => 'また行く',
            'maybe' => 'わからん',
            'no' => '行かない',
        ];

        return $intentions[$this->repeat_intention];
    }

    /**
     * Check if review has images
     */
    public function hasImages()
    {
        return $this->images()->exists();
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Delete associated images when review is deleted
        static::deleting(function ($review) {
            $review->images()->each(function ($image) {
                $image->delete();
            });
        });
    }
}
