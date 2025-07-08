<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    /**
     * Display a listing of reviews.
     */
    public function index(Request $request)
    {
        $query = Review::with(['user', 'shop', 'images']);

        // Filter by user
        if ($request->has('user_id')) {
            $query->byUser($request->user_id);
        }

        // Filter by shop
        if ($request->has('shop_id')) {
            $query->byShop($request->shop_id);
        }

        // Filter by rating
        if ($request->has('rating')) {
            $query->byRating($request->rating);
        }

        // Filter by repeat intention
        if ($request->has('repeat_intention')) {
            $query->byRepeatIntention($request->repeat_intention);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $endDate = $request->get('end_date');
            $query->byDateRange($request->start_date, $endDate);
        }

        // Filter recent reviews
        if ($request->boolean('recent_only', false)) {
            $days = $request->get('recent_days', 30);
            $query->recent($days);
        }

        // Order by most recent visits by default
        $query->orderBy('visited_at', 'desc');

        // Pagination
        $perPage = min($request->get('per_page', 15), 50);
        $reviews = $query->paginate($perPage);

        return ReviewResource::collection($reviews);
    }

    /**
     * Store a newly created review.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_id' => 'required|exists:shops,id',
            'rating' => 'required|integer|min:1|max:5',
            'repeat_intention' => 'required|in:また行く,わからん,行かない',
            'memo' => 'nullable|string|max:1000',
            'visited_at' => 'required|date|before_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        // Check if user already reviewed this shop
        $existingReview = Review::where('user_id', Auth::id())
                               ->where('shop_id', $request->shop_id)
                               ->first();

        if ($existingReview) {
            return response()->json([
                'error' => 'You have already reviewed this shop. Please update your existing review instead.'
            ], 422);
        }

        $data = $validator->validated();
        $data['user_id'] = Auth::id();

        $review = Review::create($data);
        $review->load(['user', 'shop', 'images']);

        return (new ReviewResource($review))->response()->setStatusCode(201);
    }

    /**
     * Display the specified review.
     */
    public function show(Review $review)
    {
        $review->load(['user', 'shop', 'images']);
        return new ReviewResource($review);
    }

    /**
     * Update the specified review.
     */
    public function update(Request $request, Review $review)
    {
        // Check if user owns the review
        if ($review->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'sometimes|required|integer|min:1|max:5',
            'repeat_intention' => 'sometimes|required|in:また行く,わからん,行かない',
            'memo' => 'nullable|string|max:1000',
            'visited_at' => 'sometimes|required|date|before_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        $review->update($validator->validated());
        $review->load(['user', 'shop', 'images']);

        return new ReviewResource($review);
    }

    /**
     * Remove the specified review.
     */
    public function destroy(Review $review)
    {
        // Check if user owns the review
        if ($review->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Review deleted successfully']);
    }

    /**
     * Get reviews for the authenticated user
     */
    public function myReviews(Request $request)
    {
        $query = Review::with(['shop', 'images'])
                      ->byUser(Auth::id());

        // Filter by shop
        if ($request->has('shop_id')) {
            $query->byShop($request->shop_id);
        }

        // Filter by rating
        if ($request->has('rating')) {
            $query->byRating($request->rating);
        }

        // Order by most recent visits
        $query->orderBy('visited_at', 'desc');

        // Pagination
        $perPage = min($request->get('per_page', 15), 50);
        $reviews = $query->paginate($perPage);

        return ReviewResource::collection($reviews);
    }
}
