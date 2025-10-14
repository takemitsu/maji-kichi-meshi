<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WishlistResource;
use App\Models\Shop;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class WishlistController extends Controller
{
    /**
     * Add shop to wishlist
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'priority' => 'sometimes|integer|between:1,3',
            'source_type' => ['required', Rule::in(['review', 'shop_detail'])],
            'source_user_id' => 'nullable|exists:users,id',
            'source_review_id' => 'nullable|exists:reviews,id',
        ]);

        $userId = Auth::id();

        try {
            // Check if already exists
            $existing = Wishlist::where('user_id', $userId)
                ->where('shop_id', $validated['shop_id'])
                ->first();

            if ($existing) {
                return response()->json([
                    'message' => 'すでに行きたいリストに追加されています',
                    'data' => $existing,
                ], 409);
            }

            $wishlist = Wishlist::create([
                'user_id' => $userId,
                'shop_id' => $validated['shop_id'],
                'priority' => $validated['priority'] ?? 2,
                'source_type' => $validated['source_type'],
                'source_user_id' => $validated['source_user_id'] ?? null,
                'source_review_id' => $validated['source_review_id'] ?? null,
            ]);

            return response()->json([
                'message' => '行きたいリストに追加しました',
                'data' => $wishlist->load('shop'),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to add to wishlist', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'shop_id' => $validated['shop_id'],
            ]);

            return response()->json([
                'error' => '行きたいリストへの追加に失敗しました',
            ], 500);
        }
    }

    /**
     * Remove shop from wishlist
     */
    public function destroy(Shop $shop)
    {
        $userId = Auth::id();

        try {
            $wishlist = Wishlist::where('user_id', $userId)
                ->where('shop_id', $shop->id)
                ->first();

            if (!$wishlist) {
                return response()->json([
                    'error' => '行きたいリストに登録されていません',
                ], 404);
            }

            $wishlist->delete();

            return response()->json([
                'message' => '行きたいリストから削除しました',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to remove from wishlist', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'shop_id' => $shop->id,
            ]);

            return response()->json([
                'error' => '行きたいリストからの削除に失敗しました',
            ], 500);
        }
    }

    /**
     * Update wishlist priority
     */
    public function updatePriority(Request $request, Shop $shop)
    {
        $validated = $request->validate([
            'priority' => 'required|integer|between:1,3',
        ]);

        $userId = Auth::id();

        try {
            $wishlist = Wishlist::where('user_id', $userId)
                ->where('shop_id', $shop->id)
                ->first();

            if (!$wishlist) {
                return response()->json([
                    'error' => '行きたいリストに登録されていません',
                ], 404);
            }

            $wishlist->update([
                'priority' => $validated['priority'],
            ]);

            return response()->json([
                'message' => '優先度を変更しました',
                'data' => $wishlist->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update wishlist priority', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'shop_id' => $shop->id,
            ]);

            return response()->json([
                'error' => '優先度の変更に失敗しました',
            ], 500);
        }
    }

    /**
     * Update wishlist status (want_to_go -> visited)
     */
    public function updateStatus(Request $request, Shop $shop)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['want_to_go', 'visited'])],
        ]);

        $userId = Auth::id();

        try {
            $wishlist = Wishlist::where('user_id', $userId)
                ->where('shop_id', $shop->id)
                ->first();

            if (!$wishlist) {
                return response()->json([
                    'error' => '行きたいリストに登録されていません',
                ], 404);
            }

            $updateData = [
                'status' => $validated['status'],
            ];

            // Set visited_at when changing to visited
            if ($validated['status'] === 'visited' && $wishlist->status !== 'visited') {
                $updateData['visited_at'] = now();
            }

            $wishlist->update($updateData);

            $message = $validated['status'] === 'visited'
                ? '「行った」に変更しました。レビューを書きませんか？'
                : '「行きたい」に変更しました';

            return response()->json([
                'message' => $message,
                'data' => $wishlist->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update wishlist status', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'shop_id' => $shop->id,
            ]);

            return response()->json([
                'error' => 'ステータスの変更に失敗しました',
            ], 500);
        }
    }

    /**
     * Get user's wishlist
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['want_to_go', 'visited'])],
            'sort' => ['sometimes', Rule::in(['priority', 'created_at'])],
        ]);

        $userId = Auth::id();
        $status = $validated['status'] ?? 'want_to_go';
        $sort = $validated['sort'] ?? 'priority';

        try {
            $query = Wishlist::with(['shop.categories', 'shop.publishedImages', 'sourceUser', 'sourceReview'])
                ->where('user_id', $userId)
                ->where('status', $status);

            // Apply sorting
            if ($sort === 'priority') {
                $query->orderBy('priority', 'desc')->orderBy('created_at', 'desc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $wishlists = $query->get();

            return response()->json([
                'data' => WishlistResource::collection($wishlists),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get wishlist', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            return response()->json([
                'error' => '行きたいリストの取得に失敗しました',
            ], 500);
        }
    }
}
