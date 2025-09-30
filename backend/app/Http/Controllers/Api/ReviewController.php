<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewStoreRequest;
use App\Http\Requests\ReviewUpdateRequest;
use App\Http\Requests\ReviewUploadImagesRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Models\ReviewImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * Display a listing of reviews.
     */
    public function index(Request $request)
    {
        // バリデーション
        $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'shop_id' => 'sometimes|exists:shops,id',
            'rating' => 'sometimes|integer|min:1|max:5',
            'repeat_intention' => 'sometimes|in:yes,maybe,no',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'recent_only' => 'sometimes|boolean',
            'recent_days' => 'sometimes|integer|min:1|max:365',
            'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        $query = Review::with(['user', 'shop.publishedImages', 'publishedImages']);

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
    public function store(ReviewStoreRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();

        DB::beginTransaction();
        try {
            // Create review
            $review = Review::create($data);

            // Upload images if provided
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $imageFile) {
                    ReviewImage::createFromUpload($review->id, $imageFile);
                }
            }

            $review->load(['user', 'shop.publishedImages', 'publishedImages']);

            DB::commit();

            return (new ReviewResource($review))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to create review',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified review.
     */
    public function show(Review $review)
    {
        $review->load(['user', 'shop.publishedImages', 'publishedImages']);

        return new ReviewResource($review);
    }

    /**
     * Update the specified review.
     */
    public function update(ReviewUpdateRequest $request, Review $review)
    {
        $review->update($request->validated());
        $review->load(['user', 'shop.publishedImages', 'publishedImages']);

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
        $query = Review::with(['shop', 'publishedImages'])
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

    /**
     * Upload additional images to an existing review
     */
    public function uploadImages(ReviewUploadImagesRequest $request, Review $review)
    {
        // Check current image count
        $currentImageCount = $review->images()->count();
        $newImageCount = count($request->file('images'));

        if ($currentImageCount + $newImageCount > 5) {
            return response()->json([
                'error' => 'Maximum 5 images allowed per review',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $uploadedImages = [];

            foreach ($request->file('images') as $imageFile) {
                $reviewImage = ReviewImage::createFromUpload($review->id, $imageFile);
                $uploadedImages[] = $reviewImage;
            }

            DB::commit();

            return response()->json([
                'message' => 'Images uploaded successfully',
                'data' => [
                    'uploaded_count' => count($uploadedImages),
                    'images' => collect($uploadedImages)->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'urls' => $image->urls,
                        ];
                    }),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Image upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'review_id' => $review->id,
            ]);

            return response()->json([
                'error' => 'Failed to upload images',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a specific image from a review
     */
    public function deleteImage(Review $review, ReviewImage $image)
    {
        // Check if user owns the review
        if ($review->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if image belongs to the review
        if ($image->review_id !== $review->id) {
            return response()->json(['error' => 'Image does not belong to this review'], 404);
        }

        $image->delete();

        return response()->json(['message' => 'Image deleted successfully'], 200);
    }
}
