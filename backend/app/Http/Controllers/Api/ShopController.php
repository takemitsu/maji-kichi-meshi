<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShopResource;
use App\Models\Shop;
use App\Models\ShopImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
                $q->where('slug', $request->category);
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

        // Pagination
        $perPage = min($request->get('per_page', 15), 50); // Max 50 items per page
        $shops = $query->paginate($perPage);

        return ShopResource::collection($shops);
    }

    /**
     * Store a newly created shop.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'address' => 'nullable|string|max:500',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'phone' => 'nullable|string|max:20',
                'website' => 'nullable|url|max:500',
                'google_place_id' => 'nullable|string|unique:shops,google_place_id',
                'category_ids' => 'nullable|array',
                'category_ids.*' => 'exists:categories,id',
            ]);

            if ($validator->fails()) {
                \Log::warning('Shop creation validation failed', [
                    'errors' => $validator->errors(),
                    'request_data' => $request->all(),
                    'user_id' => auth('api')->id(),
                ]);

                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors(),
                ], 422);
            }
        } catch (\Exception $e) {
            \Log::error('Shop creation validation error', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'user_id' => auth('api')->id(),
            ]);
            throw $e;
        }

        $shop = Shop::create($validator->validated());

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
    public function update(Request $request, Shop $shop)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'address' => 'nullable|string|max:500',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'phone' => 'nullable|string|max:20',
                'website' => 'nullable|url|max:500',
                'google_place_id' => 'nullable|string|unique:shops,google_place_id,' . $shop->id,
                'is_closed' => 'sometimes|boolean',
                'category_ids' => 'nullable|array',
                'category_ids.*' => 'exists:categories,id',
            ]);

            if ($validator->fails()) {
                \Log::warning('Shop update validation failed', [
                    'errors' => $validator->errors(),
                    'request_data' => $request->all(),
                    'shop_id' => $shop->id,
                    'user_id' => auth('api')->id(),
                ]);

                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors(),
                ], 422);
            }
        } catch (\Exception $e) {
            \Log::error('Shop update validation error', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'shop_id' => $shop->id,
                'user_id' => auth('api')->id(),
            ]);
            throw $e;
        }

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $shop->update($validator->validated());

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
    public function uploadImages(Request $request, Shop $shop)
    {
        try {
            $validator = Validator::make($request->all(), [
                'images' => 'required|array|min:1|max:10',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB
            ]);

            if ($validator->fails()) {
                \Log::warning('Shop image upload validation failed', [
                    'errors' => $validator->errors(),
                    'shop_id' => $shop->id,
                    'user_id' => auth('api')->id(),
                ]);

                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors(),
                ], 422);
            }
        } catch (\Exception $e) {
            \Log::error('Shop image upload error', [
                'error' => $e->getMessage(),
                'shop_id' => $shop->id,
                'user_id' => auth('api')->id(),
            ]);
            throw $e;
        }

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
        $validator = Validator::make($request->all(), [
            'image_ids' => 'required|array',
            'image_ids.*' => 'exists:shop_images,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

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
