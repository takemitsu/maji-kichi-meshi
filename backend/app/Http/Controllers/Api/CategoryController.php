<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(Request $request)
    {
        $query = Category::query();

        // Filter by type
        if ($request->has('type')) {
            switch ($request->type) {
                case 'basic':
                    $query->basic();
                    break;
                case 'time':
                    $query->time();
                    break;
                case 'ranking':
                    $query->ranking();
                    break;
            }
        }

        // Include shops count if requested
        if ($request->boolean('with_shops_count', false)) {
            $query->withCount('shops');
        }

        // Order by name by default
        $query->orderBy('name');

        return CategoryResource::collection($query->get());
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'type' => 'required|in:basic,time,ranking',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Auto-generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category = Category::create($data);

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category, Request $request)
    {
        // Load shops if requested
        if ($request->boolean('with_shops', false)) {
            $category->load('shops');
        }

        return new CategoryResource($category);
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:categories,name,' . $category->id,
            'slug' => 'sometimes|required|string|max:255|unique:categories,slug,' . $category->id,
            'type' => 'sometimes|required|in:basic,time,ranking',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Update slug if name changed but slug not provided
        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);

        return new CategoryResource($category);
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Category $category)
    {
        // Check if category is in use
        if ($category->shops()->exists() || $category->rankings()->exists()) {
            return response()->json([
                'error' => 'Cannot delete category that is in use',
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
