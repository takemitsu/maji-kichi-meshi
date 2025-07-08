<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShopResource;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class ShopController extends Controller
{
    /**
     * Display a listing of shops.
     */
    public function index(Request $request)
    {
        $query = Shop::with('categories');

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by category
        if ($request->has('category')) {
            $query->whereHas('categories', function($q) use ($request) {
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
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'required|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:500',
            'google_place_id' => 'nullable|string|unique:shops,google_place_id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
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
        $shop->load('categories');
        return new ShopResource($shop);
    }

    /**
     * Update the specified shop.
     */
    public function update(Request $request, Shop $shop)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'sometimes|required|string|max:500',
            'latitude' => 'sometimes|required|numeric|between:-90,90',
            'longitude' => 'sometimes|required|numeric|between:-180,180',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:500',
            'google_place_id' => 'nullable|string|unique:shops,google_place_id,' . $shop->id,
            'is_closed' => 'sometimes|boolean',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
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
}
