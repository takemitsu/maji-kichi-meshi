<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string $priority_label
 */
class Wishlist extends Model
{
    /** @use HasFactory<\Database\Factories\WishlistFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shop_id',
        'status',
        'priority',
        'source_type',
        'source_user_id',
        'source_review_id',
        'visited_at',
        'memo',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'priority' => 'integer',
        ];
    }

    /**
     * User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Shop relationship
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Source user relationship (who's review inspired this wishlist)
     */
    public function sourceUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'source_user_id');
    }

    /**
     * Source review relationship (which review inspired this wishlist)
     */
    public function sourceReview(): BelongsTo
    {
        return $this->belongsTo(Review::class, 'source_review_id');
    }

    /**
     * Get priority label (いつか/そのうち/絶対)
     */
    protected function priorityLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->priority) {
                1 => 'いつか',
                2 => 'そのうち',
                3 => '絶対',
                default => 'そのうち',
            }
        );
    }
}
