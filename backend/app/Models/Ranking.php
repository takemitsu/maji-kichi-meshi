<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ranking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RankingItem::class)->orderBy('rank_position');
    }

    public function shops()
    {
        return $this->belongsToMany(Shop::class, 'ranking_items')
            ->withPivot(['rank_position'])
            ->orderBy('rank_position');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // 店舗リストを取得（改善されたN+1対策）
    public function getShopsWithDetails()
    {
        return $this->items()
            ->with(['shop.publishedImages', 'shop.categories'])
            ->get()
            ->map(function ($item) {
                $shopData = $item->shop;
                $shopData->rank_position = $item->rank_position;

                return $shopData;
            });
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('rank_position', 'asc');
    }
}
