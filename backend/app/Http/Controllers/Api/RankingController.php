<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RankingResource;
use App\Models\Ranking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RankingController extends Controller
{
    public function index(Request $request)
    {
        $query = Ranking::with(['user', 'category']);

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

        return RankingResource::collection($this->getGroupedRankings($query));
    }

    public function show(Ranking $ranking)
    {
        $ranking->load(['user', 'category']);

        // Check if the ranking is public or the user is authenticated and owns it
        if (!$ranking->is_public) {
            if (!Auth::guard('api')->check() || Auth::guard('api')->id() !== $ranking->user_id) {
                return response()->json(['error' => 'Ranking not found'], 404);
            }
        }

        return new RankingResource($ranking);
    }

    public function store(Request $request)
    {
        try {
            $this->validateShopsRequest($request);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Ranking creation validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
                'user_id' => auth('api')->id(),
            ]);
            throw $e;
        }

        DB::beginTransaction();

        try {
            $userId = Auth::id();
            $categoryId = $request->category_id;

            // Clear existing rankings for this user and category (if category is specified)
            if ($categoryId) {
                Ranking::where('user_id', $userId)
                    ->where('category_id', $categoryId)
                    ->delete();
            }

            $createdRankings = $this->createRankings($request, $userId, $categoryId);

            DB::commit();

            return response()->json([
                'data' => RankingResource::collection($createdRankings),
                'message' => 'Ranking created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['error' => 'Failed to create ranking'], 500);
        }
    }

    public function update(Request $request, Ranking $ranking)
    {
        $this->validateOwnership($ranking);
        $this->validateShopsRequest($request);

        return $this->updateRankings($request, $ranking);
    }

    public function destroy(Ranking $ranking)
    {
        $this->validateOwnership($ranking);

        DB::beginTransaction();

        try {
            $deletedPosition = $ranking->rank_position;
            $ranking->delete();

            Ranking::where('user_id', $ranking->user_id)
                ->where('category_id', $ranking->category_id)
                ->where('rank_position', '>', $deletedPosition)
                ->decrement('rank_position');

            DB::commit();

            return response()->json(['message' => 'Ranking deleted successfully']);
        } catch (\Exception $e) {
            DB::rollback();
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
        $query = Ranking::with(['user', 'category'])
            ->where('user_id', Auth::id());

        if ($request->has('category_id')) {
            $query->byCategory($request->category_id);
        }

        return RankingResource::collection($this->getGroupedRankings($query));
    }

    public function publicRankings(Request $request)
    {
        $query = Ranking::with(['user', 'category'])
            ->public();

        if ($request->has('category_id')) {
            $query->byCategory($request->category_id);
        }

        if ($request->has('user_id')) {
            $query->byUser($request->user_id);
        }

        return RankingResource::collection($this->getGroupedRankings($query));
    }

    /**
     * グループ化されたランキングを取得
     */
    private function getGroupedRankings($query)
    {
        // N+1問題を回避するため、一度のクエリで必要なデータを取得
        $groupedIds = $query->selectRaw('MIN(id) as id, user_id, title, category_id')
            ->groupBy('user_id', 'title', 'category_id')
            ->pluck('id');

        return Ranking::with(['user', 'category'])
            ->whereIn('id', $groupedIds)
            ->get();
    }

    /**
     * ランキングのバリデーション
     */
    private function validateShopsRequest(Request $request)
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'is_public' => 'boolean',
            'shops' => 'required|array|min:1|max:10',
            'shops.*.shop_id' => 'required|exists:shops,id',
            'shops.*.position' => 'required|integer|min:1',
        ]);
    }

    /**
     * ランキングの作成
     */
    private function createRankings(Request $request, $userId, $categoryId)
    {
        $createdRankings = [];

        foreach ($request->shops as $shopData) {
            $ranking = Ranking::create([
                'user_id' => $userId,
                'shop_id' => $shopData['shop_id'],
                'category_id' => $categoryId,
                'rank_position' => $shopData['position'],
                'is_public' => $request->boolean('is_public', false),
                'title' => $request->title,
                'description' => $request->description,
            ]);

            $ranking->load(['user', 'category']);
            $createdRankings[] = $ranking;
        }

        return $createdRankings;
    }

    /**
     * 所有者チェック
     */
    private function validateOwnership(Ranking $ranking)
    {
        if (Auth::id() !== $ranking->user_id) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json(['error' => 'Unauthorized'], 403)
            );
        }
    }

    /**
     * ランキングの更新
     */
    private function updateRankings(Request $request, Ranking $ranking)
    {
        $this->validateShopsRequest($request);

        DB::beginTransaction();

        try {
            $userId = Auth::id();
            $categoryId = $request->category_id ?? $ranking->category_id;
            $oldTitle = $ranking->title;
            $oldCategoryId = $ranking->category_id;

            // Delete existing rankings with the same title and category
            Ranking::where('user_id', $userId)
                ->where('title', $oldTitle)
                ->where('category_id', $oldCategoryId)
                ->delete();

            $updatedRankings = $this->createRankings($request, $userId, $categoryId);

            DB::commit();

            return response()->json([
                'data' => RankingResource::collection($updatedRankings),
                'message' => 'Ranking updated successfully',
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Multiple rankings update failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'ranking_id' => $ranking->id,
            ]);

            return response()->json(['error' => 'Failed to update ranking'], 500);
        }
    }
}
