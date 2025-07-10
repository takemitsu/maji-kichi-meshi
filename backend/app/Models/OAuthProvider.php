<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OAuthProvider extends Model
{
    use HasFactory;

    protected $table = 'oauth_providers';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'provider_token',
    ];

    /**
     * User relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
