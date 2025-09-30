<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RankingStoreRequest;
use App\Http\Requests\RankingUpdateRequest;
use App\Http\Resources\RankingResource;
use App\Models\Ranking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RankingController extends Controller
{
    public function index(Request $request)
    {
        // バリデーション
        $request->validate([
            'search' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:categories,id',
            'user_id' => 'sometimes|exists:users,id',
            'is_public' => 'sometimes|boolean',
            'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        $query = Ranking::with(['user', 'category', 'items.shop.publishedImages', 'items.shop.categories']);

        // Search by title
        if ($request->has('search')) {
            $search = addcslashes($request->search, '%_\\');
            $query->where('title', 'like', '%' . $search . '%');
        }

        if ($request->has('category_id')) {
            $query->byCategory($request->category_id);
        }

        if ($request->has('user_id')) {
            $query->byUser($request->user_id);
        }

        if ($request->has('is_public')) {
            $query->where('is_public', $request->boolean('is_public'));
        } else {
            $query->public();
        }

        // Pagination
        $perPage = min($request->get('per_page', 15), 50); // Max 50 items per page
        $rankings = $query->paginate($perPage);

        return RankingResource::collection($rankings);
    }

    public function show(Ranking $ranking)
    {
        $ranking->load(['user', 'category', 'items.shop.publishedImages', 'items.shop.categories']);

        // Check if the ranking is public or the user is authenticated and owns it
        if (!$ranking->is_public) {
            if (!Auth::guard('api')->check() || Auth::guard('api')->id() !== $ranking->user_id) {
                return response()->json(['error' => 'Ranking not found'], 404);
            }
        }

        return new RankingResource($ranking);
    }

    public function store(RankingStoreRequest $request)
    {
        DB::beginTransaction();

        try {
            $userId = Auth::id();

            // Create ranking record
            $ranking = Ranking::create([
                'user_id' => $userId,
                'category_id' => $request->category_id,
                'title' => $request->title,
                'description' => $request->description,
                'is_public' => $request->boolean('is_public', false),
            ]);

            // Create ranking items
            foreach ($request->shops as $shopData) {
                $ranking->items()->create([
                    'shop_id' => $shopData['shop_id'],
                    'rank_position' => $shopData['position'],
                ]);
            }

            DB::commit();

            // Load relationships for response
            $ranking->load(['user', 'category', 'items.shop.publishedImages', 'items.shop.categories']);

            return response()->json([
                'data' => new RankingResource($ranking),
                'message' => 'Ranking created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['error' => 'Failed to create ranking'], 500);
        }
    }

    public function update(RankingUpdateRequest $request, Ranking $ranking)
    {
        DB::beginTransaction();

        try {
            // Update ranking basic info
            $ranking->update([
                'category_id' => $request->category_id ?? $ranking->category_id,
                'title' => $request->title,
                'description' => $request->description,
                'is_public' => $request->boolean('is_public', $ranking->is_public),
            ]);

            // Delete existing items and create new ones
            $ranking->items()->delete();

            foreach ($request->shops as $shopData) {
                $ranking->items()->create([
                    'shop_id' => $shopData['shop_id'],
                    'rank_position' => $shopData['position'],
                ]);
            }

            DB::commit();

            // Load relationships for response
            $ranking->load(['user', 'category', 'items.shop.publishedImages', 'items.shop.categories']);

            return response()->json([
                'data' => new RankingResource($ranking),
                'message' => 'Ranking updated successfully',
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Ranking update failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'ranking_id' => $ranking->id,
            ]);

            return response()->json(['error' => 'Failed to update ranking'], 500);
        }
    }

    public function destroy(Ranking $ranking)
    {
        // Check ownership
        if ($ranking->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $ranking->delete(); // Cascade delete handles ranking_items

            return response()->json(['message' => 'Ranking deleted successfully']);
        } catch (\Exception $e) {
            \Log::error('Ranking deletion failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'ranking_id' => $ranking->id,
            ]);

            return response()->json(['error' => 'Failed to delete ranking'], 500);
        }
    }

    public function myRankings(Request $request)
    {
        $query = Ranking::with(['user', 'category', 'items.shop.publishedImages', 'items.shop.categories'])
            ->where('user_id', Auth::id());

        // Search by title
        if ($request->has('search')) {
            $search = addcslashes($request->search, '%_\\');
            $query->where('title', 'like', '%' . $search . '%');
        }

        if ($request->has('category_id')) {
            $query->byCategory($request->category_id);
        }

        // Order by updated_at desc (most recently updated first)
        $query->orderBy('updated_at', 'desc');

        // Pagination
        $perPage = min($request->get('per_page', 15), 50); // Max 50 items per page
        $rankings = $query->paginate($perPage);

        return RankingResource::collection($rankings);
    }

    public function publicRankings(Request $request)
    {
        // バリデーション
        $request->validate([
            'search' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:categories,id',
            'user_id' => 'sometimes|exists:users,id',
            'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        $query = Ranking::with(['user', 'category', 'items.shop.publishedImages', 'items.shop.categories'])
            ->public();

        // Search by title
        if ($request->has('search')) {
            $search = addcslashes($request->search, '%_\\');
            $query->where('title', 'like', '%' . $search . '%');
        }

        if ($request->has('category_id')) {
            $query->byCategory($request->category_id);
        }

        if ($request->has('user_id')) {
            $query->byUser($request->user_id);
        }

        // Order by updated_at desc (most recently updated first)
        $query->orderBy('updated_at', 'desc');

        // Pagination
        $perPage = min($request->get('per_page', 15), 50); // Max 50 items per page
        $rankings = $query->paginate($perPage);

        return RankingResource::collection($rankings);
    }
}
