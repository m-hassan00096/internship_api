<?php

namespace Tests\Feature;

use App\Enums\ReviewStatus;
use App\Enums\UserScope;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\Room;
use App\Models\Showroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorReviewTest extends TestCase
{
    use RefreshDatabase;

    private function createVendorWithShowroom(): array
    {
        $user = User::factory()->create(['role' => UserScope::VENDOR, 'manager_id' => null]);
        $showroom = Showroom::factory()->create(['user_id' => $user->id]);

        return [$user, $showroom];
    }

    public function test_unauthenticated_user_cannot_access_review_endpoints(): void
    {
        $this->getJson(route('vendor.reviews.index'))->assertUnauthorized();
        $this->getJson(route('vendor.reviews.stats'))->assertUnauthorized();
        $this->getJson(route('vendor.reviews.show', ['review' => 1]))->assertUnauthorized();
        $this->postJson(route('vendor.reviews.reply.store', ['review' => 1]), [])->assertUnauthorized();
    }

    public function test_non_vendor_cannot_access_review_endpoints(): void
    {
        $client = User::factory()->create(['role' => UserScope::CLIENT]);

        $this->actingAs($client, 'web')
            ->getJson(route('vendor.reviews.index'))
            ->assertForbidden();
    }

    public function test_vendor_can_list_their_showroom_reviews(): void
    {
        [$vendor, $showroom] = $this->createVendorWithShowroom();
        $customer = User::factory()->create(['role' => UserScope::CLIENT]);

        $room = Room::factory()->create(['showrooms_id' => $showroom->id]);

        // Review for this vendor's room
        $review1 = Review::factory()->create([
            'user_id' => $customer->id,
            'showrooms_id' => $showroom->id,
            'reviewable_type' => 'room',
            'reviewable_id' => $room->id,
            'rating' => 5,
            'comment' => 'غرفة ممتازة وجودة رائعة',
        ]);

        // Review directly for this vendor's showroom
        $review2 = Review::factory()->forShowroom($showroom)->create([
            'user_id' => User::factory()->create(['role' => UserScope::CLIENT])->id,
            'rating' => 4,
            'comment' => 'معرض ممتاز ومحترم',
        ]);

        // Other showroom's review
        $otherShowroom = Showroom::factory()->create();
        Review::factory()->forShowroom($otherShowroom)->create();

        $response = $this->actingAs($vendor, 'web')
            ->getJson(route('vendor.reviews.index'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pagination.total', 2)
            ->assertJsonCount(2, 'data.items');
    }

    public function test_vendor_can_filter_reviews(): void
    {
        [$vendor, $showroom] = $this->createVendorWithShowroom();

        Review::factory()->forShowroom($showroom)->approved()->create(['rating' => 5]);
        Review::factory()->forShowroom($showroom)->pending()->create(['rating' => 3]);
        Review::factory()->forShowroom($showroom)->rejected()->create(['rating' => 1]);

        // Filter by rating = 5
        $responseRating = $this->actingAs($vendor, 'web')
            ->getJson(route('vendor.reviews.index', ['rating' => 5]));

        $responseRating->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.rating', 5);

        // Filter by status = APPROVED (1)
        $responseStatus = $this->actingAs($vendor, 'web')
            ->getJson(route('vendor.reviews.index', ['status' => ReviewStatus::APPROVED->value]));

        $responseStatus->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.status.value', ReviewStatus::APPROVED->value);
    }

    public function test_vendor_can_get_reviews_statistics(): void
    {
        [$vendor, $showroom] = $this->createVendorWithShowroom();

        // 2 approved reviews
        Review::factory()->forShowroom($showroom)->approved()->create(['rating' => 5]);
        Review::factory()->forShowroom($showroom)->approved()->create(['rating' => 3]);
        // 1 pending review
        Review::factory()->forShowroom($showroom)->pending()->create(['rating' => 4]);

        $response = $this->actingAs($vendor, 'web')
            ->getJson(route('vendor.reviews.stats'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.totalReviews', 2)
            ->assertJsonPath('data.averageRating', 4)
            ->assertJsonPath('data.pendingReviews', 1)
            ->assertJsonPath('data.distribution.5.count', 1)
            ->assertJsonPath('data.distribution.3.count', 1);
    }

    public function test_vendor_can_show_review_details(): void
    {
        [$vendor, $showroom] = $this->createVendorWithShowroom();
        $room = Room::factory()->create(['showrooms_id' => $showroom->id]);

        $review = Review::factory()->create([
            'showrooms_id' => $showroom->id,
            'reviewable_type' => 'room',
            'reviewable_id' => $room->id,
            'rating' => 5,
            'comment' => 'رائع جداً',
        ]);

        $response = $this->actingAs($vendor, 'web')
            ->getJson(route('vendor.reviews.show', ['review' => $review->id]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $review->id)
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.comment', 'رائع جداً')
            ->assertJsonPath('data.reviewable.type', 'room')
            ->assertJsonPath('data.reviewable.id', $room->id);
    }

    public function test_vendor_cannot_view_review_belonging_to_another_showroom(): void
    {
        [$vendor] = $this->createVendorWithShowroom();
        $otherShowroom = Showroom::factory()->create();
        $otherReview = Review::factory()->forShowroom($otherShowroom)->create();

        $response = $this->actingAs($vendor, 'web')
            ->getJson(route('vendor.reviews.show', ['review' => $otherReview->id]));

        $response->assertBadRequest()
            ->assertJsonPath('success', false);
    }

    public function test_vendor_can_reply_to_review(): void
    {
        [$vendor, $showroom] = $this->createVendorWithShowroom();
        $review = Review::factory()->forShowroom($showroom)->approved()->create();

        $response = $this->actingAs($vendor, 'web')
            ->postJson(route('vendor.reviews.reply.store', ['review' => $review->id]), [
                'reply' => 'أهلاً بك وسعداء جداً بتقييمك الجميل!',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reply', 'أهلاً بك وسعداء جداً بتقييمك الجميل!')
            ->assertJsonPath('data.status.value', ReviewStatus::PENDING->value);

        $this->assertDatabaseHas('review_replies', [
            'reviews_id' => $review->id,
            'user_id' => $vendor->id,
            'reply' => 'أهلاً بك وسعداء جداً بتقييمك الجميل!',
            'status' => ReviewStatus::PENDING->value,
        ]);
    }

    public function test_vendor_cannot_reply_twice_to_same_review(): void
    {
        [$vendor, $showroom] = $this->createVendorWithShowroom();
        $review = Review::factory()->forShowroom($showroom)->approved()->create();

        ReviewReply::factory()->create([
            'reviews_id' => $review->id,
            'user_id' => $vendor->id,
        ]);

        $response = $this->actingAs($vendor, 'web')
            ->postJson(route('vendor.reviews.reply.store', ['review' => $review->id]), [
                'reply' => 'رد مكرر لن يقبله النظام',
            ]);

        $response->assertBadRequest()
            ->assertJsonPath('success', false);
    }

    public function test_vendor_cannot_reply_to_another_showroom_review(): void
    {
        [$vendor] = $this->createVendorWithShowroom();
        $otherShowroom = Showroom::factory()->create();
        $otherReview = Review::factory()->forShowroom($otherShowroom)->create();

        $response = $this->actingAs($vendor, 'web')
            ->postJson(route('vendor.reviews.reply.store', ['review' => $otherReview->id]), [
                'reply' => 'محاولة رد على تقييم معرض آخر',
            ]);

        $response->assertBadRequest()
            ->assertJsonPath('success', false);
    }

    public function test_vendor_can_update_their_reply(): void
    {
        [$vendor, $showroom] = $this->createVendorWithShowroom();
        $review = Review::factory()->forShowroom($showroom)->approved()->create();
        $reply = ReviewReply::factory()->approved()->create([
            'reviews_id' => $review->id,
            'user_id' => $vendor->id,
            'reply' => 'الرد القديم',
        ]);

        $response = $this->actingAs($vendor, 'web')
            ->putJson(route('vendor.reviews.reply.update', ['review' => $review->id]), [
                'reply' => 'الرد المحدث والجديد بالكامل',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reply', 'الرد المحدث والجديد بالكامل')
            ->assertJsonPath('data.status.value', ReviewStatus::PENDING->value); // يعاد للـ PENDING للمراجعة

        $this->assertDatabaseHas('review_replies', [
            'id' => $reply->id,
            'reply' => 'الرد المحدث والجديد بالكامل',
            'status' => ReviewStatus::PENDING->value,
        ]);
    }

    public function test_vendor_can_delete_their_reply(): void
    {
        [$vendor, $showroom] = $this->createVendorWithShowroom();
        $review = Review::factory()->forShowroom($showroom)->approved()->create();
        $reply = ReviewReply::factory()->create([
            'reviews_id' => $review->id,
            'user_id' => $vendor->id,
        ]);

        $response = $this->actingAs($vendor, 'web')
            ->deleteJson(route('vendor.reviews.reply.delete', ['review' => $review->id]));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('review_replies', [
            'id' => $reply->id,
        ]);
    }

    public function test_reply_validation_requires_text(): void
    {
        [$vendor, $showroom] = $this->createVendorWithShowroom();
        $review = Review::factory()->forShowroom($showroom)->create();

        $response = $this->actingAs($vendor, 'web')
            ->postJson(route('vendor.reviews.reply.store', ['review' => $review->id]), [
                'reply' => '',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['reply']);
    }
}
