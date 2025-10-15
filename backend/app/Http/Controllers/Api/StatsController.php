<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ranking;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    /**
     * ダッシュボード統計情報を取得
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();

        $stats = [
            'reviews_count' => Review::where('user_id', $user->id)->count(),
            'rankings_count' => Ranking::where('user_id', $user->id)->count(),
            'liked_reviews_count' => ReviewLike::where('user_id', $user->id)->count(),
            'wishlists_count' => Wishlist::where('user_id', $user->id)
                ->where('status', 'want_to_go')
                ->count(),
        ];

        return response()->json([
            'data' => $stats,
        ]);
    }
}
