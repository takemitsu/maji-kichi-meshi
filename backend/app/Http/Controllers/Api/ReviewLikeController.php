<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Models\ReviewLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReviewLikeController extends Controller
{
    /**
     * Toggle like on a review (add or remove)
     */
    public function toggle(Review $review)
    {
        $userId = Auth::id();

        try {
            // Get current like count before toggle
            $currentCount = ReviewLike::where('review_id', $review->id)->count();

            $like = ReviewLike::where('user_id', $userId)
                ->where('review_id', $review->id)
                ->first();

            if ($like) {
                // Unlike
                $like->delete();
                $message = 'いいねを取り消しました';
                $isLiked = false;
                $likesCount = $currentCount - 1;
            } else {
                // Like
                ReviewLike::create([
                    'user_id' => $userId,
                    'review_id' => $review->id,
                ]);
                $message = 'いいねしました';
                $isLiked = true;
                $likesCount = $currentCount + 1;
            }

            return response()->json([
                'message' => $message,
                'is_liked' => $isLiked,
                'likes_count' => $likesCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to toggle like', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'review_id' => $review->id,
            ]);

            return response()->json([
                'error' => 'いいねの処理に失敗しました',
            ], 500);
        }
    }

    /**
     * Get likes count and user's like status for a review
     */
    public function show(Review $review, Request $request)
    {
        $likesCount = ReviewLike::where('review_id', $review->id)->count();

        $response = [
            'likes_count' => $likesCount,
        ];

        // Add is_liked status if user is authenticated
        if (Auth::check()) {
            $isLiked = ReviewLike::where('user_id', Auth::id())
                ->where('review_id', $review->id)
                ->exists();
            $response['is_liked'] = $isLiked;
        }

        return response()->json($response);
    }

    /**
     * Get authenticated user's liked reviews
     */
    public function myLikes(Request $request)
    {
        $query = ReviewLike::with(['review.user', 'review.shop.publishedImages', 'review.publishedImages', 'review.likes'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc');

        // Pagination
        $perPage = min($request->get('per_page', 15), 50);
        $likes = $query->paginate($perPage);

        // Transform to review resources while preserving order
        $reviews = $likes->getCollection()->map(fn ($like) => $like->review);

        return ReviewResource::collection($reviews)->additional([
            'meta' => [
                'current_page' => $likes->currentPage(),
                'last_page' => $likes->lastPage(),
                'per_page' => $likes->perPage(),
                'total' => $likes->total(),
            ],
        ]);
    }
}
