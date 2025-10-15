<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewIndexRequest;
use App\Http\Requests\ReviewStoreRequest;
use App\Http\Requests\ReviewUpdateRequest;
use App\Http\Requests\ReviewUploadImagesRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Models\ReviewImage;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class ReviewController extends Controller
{
    public function __construct(
        protected ImageUploadService $imageUploadService
    ) {}

    /**
     * Display a listing of reviews.
     */
    public function index(ReviewIndexRequest $request)
    {
        // Optional auth: JWT トークンがあれば認証、なければゲスト
        try {
            JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            // トークンがない、または無効 → ゲストとして続行
        }

        $query = Review::with(['user', 'shop.publishedImages', 'publishedImages'])
            ->withCount('likes'); // いいね数は全ユーザーに表示

        if (Auth::check()) {
            $query->with([
                'shop.wishlists' => fn ($q) => $q->where('user_id', Auth::id()),
                'likes' => fn ($q) => $q->where('user_id', Auth::id()),
            ]);
        }

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
        // Optional auth: JWT トークンがあれば認証、なければゲスト
        try {
            JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            // トークンがない、または無効 → ゲストとして続行
        }

        $review->load(['user', 'shop.publishedImages', 'publishedImages'])
            ->loadCount('likes'); // いいね数は全ユーザーに表示

        if (Auth::check()) {
            $review->load([
                'shop.wishlists' => fn ($q) => $q->where('user_id', Auth::id()),
                'likes' => fn ($q) => $q->where('user_id', Auth::id()),
            ]);
        }

        return new ReviewResource($review);
    }

    /**
     * Update the specified review.
     */
    public function update(ReviewUpdateRequest $request, Review $review)
    {
        $this->authorize('update', $review);

        $review->update($request->validated());
        $review->load(['user', 'shop.publishedImages', 'publishedImages']);

        return new ReviewResource($review);
    }

    /**
     * Remove the specified review.
     */
    public function destroy(Review $review)
    {
        $this->authorize('delete', $review);

        $review->delete();

        return response()->json(['message' => 'Review deleted successfully']);
    }

    /**
     * Get reviews for the authenticated user
     */
    public function myReviews(Request $request)
    {
        $query = Review::with(['shop', 'publishedImages', 'likes'])
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
        $this->authorize('uploadImages', $review);

        try {
            $uploadedImages = $this->imageUploadService->uploadImages(
                $review,
                $request->file('images'),
                maxImages: 5
            );

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
            Log::error('Image upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'review_id' => $review->id,
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a specific image from a review
     */
    public function deleteImage(Review $review, ReviewImage $image)
    {
        $this->authorize('deleteImage', $review);

        // Check if image belongs to the review
        if ($image->review_id !== $review->id) {
            return response()->json(['error' => 'Image does not belong to this review'], 404);
        }

        $this->imageUploadService->deleteImage($image);

        return response()->json(['message' => 'Image deleted successfully'], 200);
    }
}
