<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShopStoreRequest;
use App\Http\Requests\ShopUpdateRequest;
use App\Http\Requests\ShopUploadImagesRequest;
use App\Http\Resources\ShopResource;
use App\Models\Shop;
use App\Models\ShopImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopController extends Controller
{
    /**
     * Display a listing of shops.
     */
    public function index(Request $request)
    {
        $query = Shop::with(['categories', 'publishedImages']);

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
        $shop->load(['categories', 'publishedImages']);

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
        // Check current image count
        $currentImageCount = $shop->images()->count();
        $newImageCount = count($request->file('images'));

        if ($currentImageCount + $newImageCount > 10) {
            return response()->json([
                'error' => 'Maximum 10 images allowed per shop',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $uploadedImages = [];
            $sortOrder = $currentImageCount;

            foreach ($request->file('images') as $imageFile) {
                $shopImage = ShopImage::createFromUpload($shop->id, $imageFile, $sortOrder);
                $uploadedImages[] = $shopImage;
                $sortOrder++;
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
                            'sort_order' => $image->sort_order,
                        ];
                    }),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to upload images',
                'message' => $e->getMessage(),
            ], 500);
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

        $image->delete();

        return response()->json(['message' => 'Image deleted successfully']);
    }

    /**
     * Reorder shop images
     */
    public function reorderImages(Request $request, Shop $shop)
    {
        $request->validate([
            'image_ids' => 'required|array',
            'image_ids.*' => 'exists:shop_images,id',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->image_ids as $index => $imageId) {
                ShopImage::where('id', $imageId)
                    ->where('shop_id', $shop->id)
                    ->update(['sort_order' => $index]);
            }

            DB::commit();

            return response()->json(['message' => 'Images reordered successfully']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to reorder images',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
