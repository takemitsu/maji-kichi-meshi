<?php

namespace Tests\Unit;

use App\Models\ReviewImage;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created_with_admin_role(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->assertEquals('admin', $user->role);
        $this->assertTrue($user->isAdmin());
    }

    public function test_user_can_be_created_with_moderator_role(): void
    {
        $user = User::factory()->create([
            'role' => 'moderator',
            'status' => 'active',
        ]);

        $this->assertEquals('moderator', $user->role);
        $this->assertTrue($user->isModerator());
        $this->assertFalse($user->isAdmin());
    }

    public function test_user_can_be_created_with_regular_user_role(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
        ]);

        $this->assertEquals('user', $user->role);
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isModerator());
    }

    public function test_user_defaults_to_user_role_when_not_specified(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('user', $user->role);
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isModerator());
    }

    public function test_user_defaults_to_active_status_when_not_specified(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('active', $user->status);
        $this->assertTrue($user->isActive());
    }

    public function test_user_can_be_banned(): void
    {
        $user = User::factory()->create([
            'status' => 'banned',
        ]);

        $this->assertEquals('banned', $user->status);
        $this->assertFalse($user->isActive());
    }

    public function test_user_can_be_marked_as_deleted(): void
    {
        $user = User::factory()->create([
            'status' => 'deleted',
        ]);

        $this->assertEquals('deleted', $user->status);
        $this->assertFalse($user->isActive());
    }

    public function test_admin_is_also_moderator(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $moderator = User::factory()->create(['role' => 'moderator']);
        $user = User::factory()->create(['role' => 'user']);

        $this->assertTrue($admin->isModerator());
        $this->assertTrue($moderator->isModerator());
        $this->assertFalse($user->isModerator());
    }

    public function test_role_and_status_are_hidden_from_api_responses(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $hiddenAttributes = $user->getHidden();

        $this->assertContains('role', $hiddenAttributes);
        $this->assertContains('status', $hiddenAttributes);
        $this->assertContains('password', $hiddenAttributes);
        $this->assertContains('remember_token', $hiddenAttributes);
    }

    public function test_user_can_have_reviews_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $user->reviews()
        );
    }

    public function test_user_can_have_rankings_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $user->rankings()
        );
    }

    public function test_user_can_have_oauth_providers_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $user->oauthProviders()
        );
    }

    public function test_shop_model_has_correct_status_methods(): void
    {
        $activeShop = Shop::factory()->create(['status' => 'active']);
        $hiddenShop = Shop::factory()->create(['status' => 'hidden']);
        $deletedShop = Shop::factory()->create(['status' => 'deleted']);

        $this->assertTrue($activeShop->isActive());
        $this->assertFalse($activeShop->isHidden());

        $this->assertFalse($hiddenShop->isActive());
        $this->assertTrue($hiddenShop->isHidden());

        $this->assertFalse($deletedShop->isActive());
        $this->assertFalse($deletedShop->isHidden());
    }

    public function test_shop_model_has_moderator_relationship(): void
    {
        $moderator = User::factory()->create(['role' => 'admin']);
        $shop = Shop::factory()->create([
            'status' => 'hidden',
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
        ]);

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $shop->moderator()
        );

        $this->assertEquals($moderator->id, $shop->moderator->id);
    }

    public function test_review_image_model_has_correct_moderation_methods(): void
    {
        $review = \App\Models\Review::factory()->create();

        $publishedImage = ReviewImage::factory()->create([
            'review_id' => $review->id,
            'moderation_status' => 'published',
        ]);
        $underReviewImage = ReviewImage::factory()->create([
            'review_id' => $review->id,
            'moderation_status' => 'under_review',
        ]);
        $rejectedImage = ReviewImage::factory()->create([
            'review_id' => $review->id,
            'moderation_status' => 'rejected',
        ]);

        $this->assertTrue($publishedImage->isPublished());
        $this->assertFalse($publishedImage->isUnderReview());
        $this->assertFalse($publishedImage->isRejected());

        $this->assertFalse($underReviewImage->isPublished());
        $this->assertTrue($underReviewImage->isUnderReview());
        $this->assertFalse($underReviewImage->isRejected());

        $this->assertFalse($rejectedImage->isPublished());
        $this->assertFalse($rejectedImage->isUnderReview());
        $this->assertTrue($rejectedImage->isRejected());
    }

    public function test_review_image_model_has_moderator_relationship(): void
    {
        $review = \App\Models\Review::factory()->create();
        $moderator = User::factory()->create(['role' => 'admin']);
        $image = ReviewImage::factory()->create([
            'review_id' => $review->id,
            'moderation_status' => 'rejected',
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
        ]);

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $image->moderator()
        );

        $this->assertEquals($moderator->id, $image->moderator->id);
    }
}
