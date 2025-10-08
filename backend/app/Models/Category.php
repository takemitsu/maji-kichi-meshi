<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
    ];

    /**
     * Shops relationship (many-to-many)
     */
    public function shops()
    {
        return $this->belongsToMany(Shop::class, 'shop_categories');
    }

    /**
     * Rankings relationship
     */
    public function rankings()
    {
        return $this->hasMany(Ranking::class);
    }

    /**
     * Get category by slug
     */
    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }
}
