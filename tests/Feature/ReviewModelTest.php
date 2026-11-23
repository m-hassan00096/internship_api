<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\Room;
use App\Models\Showroom;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_review_for_room(): void
    {
        $user = User::factory()->create();
        $showroom = Showroom::factory()->create();
        $room = Room::factory()->create(['showrooms_id' => $showroom->id]);

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'showrooms_id' => $showroom->id,
            'reviewable_type' => 'room',
            'reviewable_id' => $room->id,
            'rating' => 5,
            'comment' => 'غرفة نوم ممتازة وجودة خامات عالية جداً',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $user->id,
            'showrooms_id' => $showroom->id,
            'reviewable_type' => 'room',
            'reviewable_id' => $room->id,
            'rating' => 5,
        ]);

        $this->assertInstanceOf(Room::class, $review->reviewable);
        $this->assertEquals($room->id, $review->reviewable->id);
        $this->assertEquals($user->id, $review->user->id);
        $this->assertEquals($showroom->id, $review->showroom->id);
        $this->assertTrue($review->isPending());
        $this->assertCount(1, $room->reviews);
    }

    public function test_can_create_review_for_showroom(): void
    {
        $user = User::factory()->create();
        $showroom = Showroom::factory()->create();

        $review = Review::factory()->forShowroom($showroom)->create([
            'user_id' => $user->id,
            'rating' => 4,
            'comment' => 'معرض محترم وتعامل راقي جداً',
        ]);

        $this->assertInstanceOf(Showroom::class, $review->reviewable);
        $this->assertEquals($showroom->id, $review->reviewable->id);
        $this->assertCount(1, $showroom->directReviews);
    }

    public function test_user_cannot_review_same_item_twice(): void
    {
        $user = User::factory()->create();
        $showroom = Showroom::factory()->create();
        $room = Room::factory()->create(['showrooms_id' => $showroom->id]);

        Review::factory()->create([
            'user_id' => $user->id,
            'showrooms_id' => $showroom->id,
            'reviewable_type' => 'room',
            'reviewable_id' => $room->id,
        ]);

        $this->expectException(QueryException::class);

        Review::factory()->create([
            'user_id' => $user->id,
            'showrooms_id' => $showroom->id,
            'reviewable_type' => 'room',
            'reviewable_id' => $room->id,
        ]);
    }

    public function test_review_status_scopes(): void
    {
        Review::factory()->create(); // pending
        Review::factory()->approved()->create();
        Review::factory()->rejected()->create();

        $this->assertEquals(1, Review::pending()->count());
        $this->assertEquals(1, Review::approved()->count());
        $this->assertEquals(1, Review::rejected()->count());
    }

    public function test_can_create_vendor_reply_for_review(): void
    {
        $review = Review::factory()->approved()->create();
        $vendor = User::factory()->create();

        $reply = ReviewReply::factory()->create([
            'reviews_id' => $review->id,
            'user_id' => $vendor->id,
            'reply' => 'شكراً لك ونسعد دائماً بخدمتكم!',
        ]);

        $this->assertDatabaseHas('review_replies', [
            'id' => $reply->id,
            'reviews_id' => $review->id,
            'user_id' => $vendor->id,
        ]);

        $this->assertInstanceOf(Review::class, $reply->review);
        $this->assertInstanceOf(ReviewReply::class, $review->fresh()->reply);
        $this->assertEquals($vendor->id, $reply->user->id);
        $this->assertTrue($reply->isPending());
    }

    public function test_review_cannot_have_more_than_one_reply(): void
    {
        $review = Review::factory()->approved()->create();
        $vendor = User::factory()->create();

        ReviewReply::factory()->create([
            'reviews_id' => $review->id,
            'user_id' => $vendor->id,
        ]);

        $this->expectException(QueryException::class);

        ReviewReply::factory()->create([
            'reviews_id' => $review->id,
            'user_id' => $vendor->id,
        ]);
    }

    public function test_review_reply_status_scopes(): void
    {
        ReviewReply::factory()->create(); // pending
        ReviewReply::factory()->approved()->create();
        ReviewReply::factory()->rejected()->create();

        $this->assertEquals(1, ReviewReply::pending()->count());
        $this->assertEquals(1, ReviewReply::approved()->count());
        $this->assertEquals(1, ReviewReply::rejected()->count());
    }
}
