<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ranking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shop_id',
        'category_id',
        'rank_position',
        'is_public',
        'title',
        'description',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // 同じランキング（user_id, title, category_id）の全店舗
    public function rankingShops()
    {
        if (!$this->title || !$this->user_id || !$this->category_id) {
            return collect();
        }

        return self::with('shop.publishedImages', 'shop.categories')
            ->where('user_id', $this->user_id)
            ->where('title', $this->title)
            ->where('category_id', $this->category_id)
            ->orderBy('rank_position')
            ->get();
    }

    // キャッシュされたrankingShops（N+1問題回避用）
    protected $cachedRankingShops = null;

    public function getCachedRankingShops()
    {
        if ($this->cachedRankingShops === null) {
            $this->cachedRankingShops = $this->rankingShops();
        }

        return $this->cachedRankingShops;
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
