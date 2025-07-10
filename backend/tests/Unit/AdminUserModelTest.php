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

    /** @test */
    public function user_can_be_created_with_admin_role()
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->assertEquals('admin', $user->role);
        $this->assertTrue($user->isAdmin());
    }

    /** @test */
    public function user_can_be_created_with_moderator_role()
    {
        $user = User::factory()->create([
            'role' => 'moderator',
            'status' => 'active',
        ]);

        $this->assertEquals('moderator', $user->role);
        $this->assertTrue($user->isModerator());
        $this->assertFalse($user->isAdmin());
    }

    /** @test */
    public function user_can_be_created_with_regular_user_role()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
        ]);

        $this->assertEquals('user', $user->role);
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isModerator());
    }

    /** @test */
    public function user_defaults_to_user_role_when_not_specified()
    {
        $user = User::factory()->create();

        $this->assertEquals('user', $user->role);
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isModerator());
    }

    /** @test */
    public function user_defaults_to_active_status_when_not_specified()
    {
        $user = User::factory()->create();

        $this->assertEquals('active', $user->status);
        $this->assertTrue($user->isActive());
    }

    /** @test */
    public function user_can_be_banned()
    {
        $user = User::factory()->create([
            'status' => 'banned',
        ]);

        $this->assertEquals('banned', $user->status);
        $this->assertFalse($user->isActive());
    }

    /** @test */
    public function user_can_be_marked_as_deleted()
    {
        $user = User::factory()->create([
            'status' => 'deleted',
        ]);

        $this->assertEquals('deleted', $user->status);
        $this->assertFalse($user->isActive());
    }

    /** @test */
    public function admin_is_also_moderator()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $moderator = User::factory()->create(['role' => 'moderator']);
        $user = User::factory()->create(['role' => 'user']);

        $this->assertTrue($admin->isModerator());
        $this->assertTrue($moderator->isModerator());
        $this->assertFalse($user->isModerator());
    }

    /** @test */
    public function role_and_status_are_hidden_from_api_responses()
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

    /** @test */
    public function user_can_have_reviews_relationship()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $user->reviews()
        );
    }

    /** @test */
    public function user_can_have_rankings_relationship()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $user->rankings()
        );
    }

    /** @test */
    public function user_can_have_oauth_providers_relationship()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $user->oauthProviders()
        );
    }

    /** @test */
    public function shop_model_has_correct_status_methods()
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

    /** @test */
    public function shop_model_has_moderator_relationship()
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

    /** @test */
    public function review_image_model_has_correct_moderation_methods()
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

    /** @test */
    public function review_image_model_has_moderator_relationship()
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
