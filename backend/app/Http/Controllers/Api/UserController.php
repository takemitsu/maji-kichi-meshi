<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Get basic user info for public display
     */
    public function info(User $user)
    {
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'created_at' => $user->created_at,
            'profile_image' => $user->hasProfileImage() ? [
                'urls' => $user->getProfileImageUrls(),
            ] : null,
        ]);
    }
}
