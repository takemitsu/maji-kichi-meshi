<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ranking;
use App\Models\Review;
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
        ];

        return response()->json([
            'data' => $stats,
        ]);
    }
}
