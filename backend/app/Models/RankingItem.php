<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'ranking_id',
        'shop_id',
        'rank_position',
    ];

    public function ranking(): BelongsTo
    {
        return $this->belongsTo(Ranking::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
