<?php

namespace App\Services;

use App\Models\Ranking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RankingService
{
    /**
     * Create a new ranking with items.
     */
    public function create(array $data, int $userId): Ranking
    {
        return DB::transaction(function () use ($data, $userId) {
            // Create ranking record
            $ranking = Ranking::create([
                'user_id' => $userId,
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'is_public' => $data['is_public'] ?? false,
            ]);

            // Create ranking items
            $this->syncRankingItems($ranking, $data['shops']);

            // Load relationships for response
            $ranking->load(['user', 'category', 'items.shop.publishedImages', 'items.shop.categories']);

            return $ranking;
        });
    }

    /**
     * Update an existing ranking with items.
     */
    public function update(Ranking $ranking, array $data): Ranking
    {
        return DB::transaction(function () use ($ranking, $data) {
            // Update ranking basic info
            $ranking->update([
                'category_id' => $data['category_id'] ?? $ranking->category_id,
                'title' => $data['title'],
                'description' => $data['description'] ?? $ranking->description,
                'is_public' => $data['is_public'] ?? $ranking->is_public,
            ]);

            // Sync ranking items
            if (isset($data['shops'])) {
                $this->syncRankingItems($ranking, $data['shops']);
            }

            // Load relationships for response
            $ranking->load(['user', 'category', 'items.shop.publishedImages', 'items.shop.categories']);

            return $ranking;
        });
    }

    /**
     * Delete a ranking.
     */
    public function delete(Ranking $ranking): bool
    {
        try {
            return $ranking->delete();
        } catch (\Exception $e) {
            Log::error('Ranking deletion failed', [
                'error' => $e->getMessage(),
                'ranking_id' => $ranking->id,
            ]);

            throw $e;
        }
    }

    /**
     * Sync ranking items (delete old ones and create new ones).
     */
    protected function syncRankingItems(Ranking $ranking, array $shops): void
    {
        // Delete existing items
        $ranking->items()->delete();

        // Create new items
        foreach ($shops as $shopData) {
            $ranking->items()->create([
                'shop_id' => $shopData['shop_id'],
                'rank_position' => $shopData['position'],
            ]);
        }
    }
}
