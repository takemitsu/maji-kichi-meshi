<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryStoreRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

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
    public function store(CategoryStoreRequest $request)
    {
        $category = Category::create($request->validated());

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category)
    {
        return new CategoryResource($category);
    }

    /**
     * Update the specified category.
     */
    public function update(CategoryUpdateRequest $request, Category $category)
    {
        $category->update($request->validated());

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
