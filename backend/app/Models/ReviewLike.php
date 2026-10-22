<?php

namespace App\Models;

use Database\Factories\ReviewLikeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewLike extends Model
{
    /** @use HasFactory<ReviewLikeFactory> */
    use HasFactory;

    /**
     * Only created_at is used (no updated_at)
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'review_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Review relationship
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
