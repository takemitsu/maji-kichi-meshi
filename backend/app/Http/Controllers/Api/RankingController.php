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
        $query = Ranking::with(['user', 'shop', 'category']);

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

        $rankings = $query->ordered()->paginate(20);

        return RankingResource::collection($rankings);
    }

    public function show(Ranking $ranking)
    {
        $ranking->load(['user', 'shop', 'category']);

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
        $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'category_id' => 'required|exists:categories,id',
            'rank_position' => 'required|integer|min:1',
            'is_public' => 'boolean',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $userId = Auth::id();
            $categoryId = $request->category_id;
            $newPosition = $request->rank_position;

            $existingRankingAtPosition = Ranking::where('user_id', $userId)
                ->where('category_id', $categoryId)
                ->where('rank_position', $newPosition)
                ->first();

            if ($existingRankingAtPosition) {
                Ranking::where('user_id', $userId)
                    ->where('category_id', $categoryId)
                    ->where('rank_position', '>=', $newPosition)
                    ->increment('rank_position');
            }

            $ranking = Ranking::create([
                'user_id' => $userId,
                'shop_id' => $request->shop_id,
                'category_id' => $categoryId,
                'rank_position' => $newPosition,
                'is_public' => $request->boolean('is_public', false),
                'title' => $request->title,
                'description' => $request->description,
            ]);

            DB::commit();

            $ranking->load(['user', 'shop', 'category']);

            return (new RankingResource($ranking))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to create ranking'], 500);
        }
    }

    public function update(Request $request, Ranking $ranking)
    {
        if (Auth::id() !== $ranking->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'rank_position' => 'integer|min:1',
            'is_public' => 'boolean',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $oldPosition = $ranking->rank_position;
            $newPosition = $request->rank_position ?? $oldPosition;

            if ($newPosition !== $oldPosition) {
                if ($newPosition < $oldPosition) {
                    Ranking::where('user_id', $ranking->user_id)
                        ->where('category_id', $ranking->category_id)
                        ->where('rank_position', '>=', $newPosition)
                        ->where('rank_position', '<', $oldPosition)
                        ->increment('rank_position');
                } else {
                    Ranking::where('user_id', $ranking->user_id)
                        ->where('category_id', $ranking->category_id)
                        ->where('rank_position', '>', $oldPosition)
                        ->where('rank_position', '<=', $newPosition)
                        ->decrement('rank_position');
                }
            }

            $ranking->update([
                'rank_position' => $newPosition,
                'is_public' => $request->boolean('is_public', $ranking->is_public),
                'title' => $request->title ?? $ranking->title,
                'description' => $request->description ?? $ranking->description,
            ]);

            DB::commit();

            $ranking->load(['user', 'shop', 'category']);

            return new RankingResource($ranking);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to update ranking'], 500);
        }
    }

    public function destroy(Ranking $ranking)
    {
        if (Auth::id() !== $ranking->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

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
            return response()->json(['error' => 'Failed to delete ranking'], 500);
        }
    }

    public function myRankings(Request $request)
    {
        $query = Ranking::with(['user', 'shop', 'category'])
            ->where('user_id', Auth::id());

        if ($request->has('category_id')) {
            $query->byCategory($request->category_id);
        }

        $rankings = $query->ordered()->paginate(20);

        return RankingResource::collection($rankings);
    }

    public function publicRankings(Request $request)
    {
        $query = Ranking::with(['user', 'shop', 'category'])
            ->public();

        if ($request->has('category_id')) {
            $query->byCategory($request->category_id);
        }

        if ($request->has('user_id')) {
            $query->byUser($request->user_id);
        }

        $rankings = $query->ordered()->paginate(20);

        return RankingResource::collection($rankings);
    }
}