<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ReviewImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'original_path',
        'large_path',
        'medium_path',
        'thumbnail_path',
    ];

    /**
     * Review relationship
     */
    public function review()
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * Get full URL for original image
     */
    public function getOriginalUrlAttribute()
    {
        return $this->original_path ? Storage::url($this->original_path) : null;
    }

    /**
     * Get full URL for large image
     */
    public function getLargeUrlAttribute()
    {
        return $this->large_path ? Storage::url($this->large_path) : null;
    }

    /**
     * Get full URL for medium image
     */
    public function getMediumUrlAttribute()
    {
        return $this->medium_path ? Storage::url($this->medium_path) : null;
    }

    /**
     * Get full URL for thumbnail image
     */
    public function getThumbnailUrlAttribute()
    {
        return $this->thumbnail_path ? Storage::url($this->thumbnail_path) : null;
    }

    /**
     * Get all image URLs as array
     */
    public function getUrlsAttribute()
    {
        return [
            'original' => $this->original_url,
            'large' => $this->large_url,
            'medium' => $this->medium_url,
            'thumbnail' => $this->thumbnail_url,
        ];
    }

    /**
     * Delete all image files from storage
     */
    public function deleteFiles()
    {
        $paths = array_filter([
            $this->original_path,
            $this->large_path,
            $this->medium_path,
            $this->thumbnail_path,
        ]);

        foreach ($paths as $path) {
            if (Storage::exists($path)) {
                Storage::delete($path);
            }
        }
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Delete files when model is deleted
        static::deleting(function ($reviewImage) {
            $reviewImage->deleteFiles();
        });
    }
}
