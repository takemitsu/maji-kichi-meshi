<?php

namespace App\Policies;

use App\Models\Ranking;
use App\Models\User;

class RankingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Ranking $ranking): bool
    {
        // Public rankings are viewable by everyone
        if ($ranking->is_public) {
            return true;
        }

        // Private rankings are only viewable by owner
        return $user && $user->id === $ranking->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Ranking $ranking): bool
    {
        return $user->id === $ranking->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Ranking $ranking): bool
    {
        return $user->id === $ranking->user_id;
    }
}
