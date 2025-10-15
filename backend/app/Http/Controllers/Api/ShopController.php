<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderShopImagesRequest;
use App\Http\Requests\ShopIndexRequest;
use App\Http\Requests\ShopStoreRequest;
use App\Http\Requests\ShopUpdateRequest;
use App\Http\Requests\ShopUploadImagesRequest;
use App\Http\Resources\ShopResource;
use App\Models\Shop;
use App\Models\ShopImage;
use App\Models\Wishlist;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class ShopController extends Controller
{
    public function __construct(
        protected ImageUploadService $imageUploadService
    ) {}

    /**
     * Display a listing of shops.
     */
    public function index(ShopIndexRequest $request)
    {
        // Optional auth: JWT トークンがあれば認証、なければゲスト
        try {
            JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            // トークンがない、または無効 → ゲストとして続行
        }

        $query = Shop::with(['categories', 'publishedImages']);

        if (Auth::check()) {
            $query->with([
                'wishlists' => fn ($q) => $q->where('user_id', Auth::id()),
            ]);
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by category
        if ($request->has('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category);
            });
        }

        // Filter open shops only
        if ($request->boolean('open_only', false)) {
            $query->open();
        }

        // Location-based search
        if ($request->has(['latitude', 'longitude'])) {
            $latitude = $request->latitude;
            $longitude = $request->longitude;
            $radius = $request->get('radius', 5); // Default 5km radius

            $query->near($latitude, $longitude, $radius);
        }

        // Sort by created_at descending (newest first)
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = min($request->get('per_page', 15), 50); // Max 50 items per page
        $shops = $query->paginate($perPage);

        return ShopResource::collection($shops);
    }

    /**
     * Store a newly created shop.
     */
    public function store(ShopStoreRequest $request)
    {
        $shop = Shop::create($request->validated());

        // Attach categories if provided
        if ($request->has('category_ids')) {
            $shop->categories()->attach($request->category_ids);
        }

        $shop->load('categories');

        return (new ShopResource($shop))->response()->setStatusCode(201);
    }

    /**
     * Display the specified shop.
     */
    public function show(Shop $shop)
    {
        // Optional auth: JWT トークンがあれば認証、なければゲスト
        try {
            JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            // トークンがない、または無効 → ゲストとして続行
        }

        $shop->load(['categories', 'publishedImages']);

        if (Auth::check()) {
            $shop->load([
                'wishlists' => fn ($q) => $q->where('user_id', Auth::id()),
            ]);
        }

        return new ShopResource($shop);
    }

    /**
     * Update the specified shop.
     */
    public function update(ShopUpdateRequest $request, Shop $shop)
    {
        $shop->update($request->validated());

        // Update categories if provided
        if ($request->has('category_ids')) {
            $shop->categories()->sync($request->category_ids);
        }

        $shop->load('categories');

        return new ShopResource($shop);
    }

    /**
     * Remove the specified shop.
     */
    public function destroy(Shop $shop)
    {
        $shop->delete();

        return response()->json(['message' => 'Shop deleted successfully']);
    }

    /**
     * Upload images to a shop
     */
    public function uploadImages(ShopUploadImagesRequest $request, Shop $shop)
    {
        try {
            $uploadedImages = $this->imageUploadService->uploadImages(
                $shop,
                $request->file('images'),
                maxImages: 10
            );

            return response()->json([
                'message' => 'Images uploaded successfully',
                'data' => [
                    'uploaded_count' => count($uploadedImages),
                    'images' => collect($uploadedImages)->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'urls' => $image->urls,
                            'sort_order' => $image->sort_order,
                        ];
                    }),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a shop image
     */
    public function deleteImage(Request $request, Shop $shop, ShopImage $image)
    {
        // Check if image belongs to the shop
        if ($image->shop_id !== $shop->id) {
            return response()->json(['error' => 'Image does not belong to this shop'], 403);
        }

        $this->imageUploadService->deleteImage($image);

        return response()->json(['message' => 'Image deleted successfully']);
    }

    /**
     * Reorder shop images
     */
    public function reorderImages(ReorderShopImagesRequest $request, Shop $shop)
    {
        try {
            $this->imageUploadService->reorderImages($shop, $request->image_ids);

            return response()->json(['message' => 'Images reordered successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to reorder images',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get wishlist status for a shop
     */
    public function wishlistStatus(Shop $shop)
    {
        if (!Auth::check()) {
            return response()->json([
                'in_wishlist' => false,
            ]);
        }

        $wishlist = Wishlist::where('user_id', Auth::id())
            ->where('shop_id', $shop->id)
            ->first();

        if (!$wishlist) {
            return response()->json([
                'in_wishlist' => false,
            ]);
        }

        return response()->json([
            'in_wishlist' => true,
            'priority' => $wishlist->priority,
            'priority_label' => $wishlist->priority_label,
            'status' => $wishlist->status,
        ]);
    }
}
