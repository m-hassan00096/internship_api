<?php

namespace Tests\Feature;

use App\Enums\ReviewStatus;
use App\Enums\UserScope;
use App\Models\Review;
use App\Models\Room;
use App\Models\Showroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserScope::ADMIN]);
    }

    public function test_unauthenticated_user_cannot_access_admin_reviews(): void
    {
        $this->getJson(route('admin.reviews.index'))->assertUnauthorized();
        $this->getJson(route('admin.reviews.show', 1))->assertUnauthorized();
        $this->patchJson(route('admin.reviews.update-status', 1), ['status' => ReviewStatus::APPROVED->value])->assertUnauthorized();
    }

    public function test_non_admin_cannot_access_admin_reviews(): void
    {
        $vendor = User::factory()->create(['role' => UserScope::VENDOR]);
        $client = User::factory()->create(['role' => UserScope::CLIENT]);

        $this
            ->actingAs($vendor, 'web')
            ->getJson(route('admin.reviews.index'))
            ->assertForbidden();

        $this
            ->actingAs($client, 'web')
            ->getJson(route('admin.reviews.index'))
            ->assertForbidden();
    }

    public function test_admin_can_list_reviews_with_kpis(): void
    {
        $showroom = Showroom::factory()->create();
        $room = Room::factory()->create(['showrooms_id' => $showroom->id]);

        // 2 Approved reviews
        Review::factory()->create([
            'showrooms_id' => $showroom->id,
            'reviewable_type' => 'room',
            'reviewable_id' => $room->id,
            'rating' => 5,
            'status' => ReviewStatus::APPROVED,
        ]);

        Review::factory()->create([
            'showrooms_id' => $showroom->id,
            'reviewable_type' => 'room',
            'reviewable_id' => $room->id,
            'rating' => 4,
            'status' => ReviewStatus::APPROVED,
        ]);

        // 1 Pending review
        Review::factory()->create([
            'showrooms_id' => $showroom->id,
            'reviewable_type' => 'showroom',
            'reviewable_id' => $showroom->id,
            'rating' => 3,
            'status' => ReviewStatus::PENDING,
        ]);

        // 1 Rejected review
        Review::factory()->create([
            'showrooms_id' => $showroom->id,
            'reviewable_type' => 'room',
            'reviewable_id' => $room->id,
            'rating' => 1,
            'status' => ReviewStatus::REJECTED,
            'reject_reason' => 'محتوى غير لائق',
        ]);

        // 1 Hidden review
        Review::factory()->create([
            'showrooms_id' => $showroom->id,
            'reviewable_type' => 'room',
            'reviewable_id' => $room->id,
            'rating' => 2,
            'status' => ReviewStatus::HIDDEN,
        ]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.reviews.index'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.kpis.total', 5)
            ->assertJsonPath('data.kpis.pending', 1)
            ->assertJsonPath('data.kpis.rejected', 1)
            ->assertJsonPath('data.pagination.total', 5)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'kpis' => [
                        'total',
                        'pending',
                        'rejected',
                    ],
                    'items' => [
                        '*' => [
                            'id',
                            'user' => ['avatar', 'name', 'handle'],
                            'rating',
                            'target' => ['type', 'name'],
                            'showroom',
                            'comment',
                            'status' => ['value', 'label', 'badgeColor'],
                            'date',
                        ],
                    ],
                    'pagination' => ['currentPage', 'lastPage', 'perPage', 'total'],
                ],
            ]);
    }

    public function test_admin_can_filter_reviews_by_status(): void
    {
        $showroom = Showroom::factory()->create();

        Review::factory()->create([
            'showrooms_id' => $showroom->id,
            'status' => ReviewStatus::PENDING,
        ]);

        Review::factory()->create([
            'showrooms_id' => $showroom->id,
            'status' => ReviewStatus::APPROVED,
        ]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.reviews.index', ['status' => 'pending']));

        $response->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.status.value', ReviewStatus::PENDING->value);
    }

    public function test_admin_can_filter_reviews_by_target_type(): void
    {
        $showroom = Showroom::factory()->create();
        $room = Room::factory()->create(['showrooms_id' => $showroom->id]);

        Review::factory()->create([
            'showrooms_id' => $showroom->id,
            'reviewable_type' => 'room',
            'reviewable_id' => $room->id,
        ]);

        Review::factory()->create([
            'showrooms_id' => $showroom->id,
            'reviewable_type' => 'showroom',
            'reviewable_id' => $showroom->id,
        ]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.reviews.index', ['targetType' => 'room']));

        $response->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.target.name', $room->name);
    }

    public function test_admin_can_filter_reviews_by_rating(): void
    {
        $showroom = Showroom::factory()->create();

        Review::factory()->create(['showrooms_id' => $showroom->id, 'rating' => 5]);
        Review::factory()->create(['showrooms_id' => $showroom->id, 'rating' => 2]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.reviews.index', ['rating' => 5]));

        $response->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.rating', '5.0 / 5 ★');
    }

    public function test_admin_can_search_reviews(): void
    {
        $showroom = Showroom::factory()->create(['name' => 'معرض القصر الملكي']);
        $user = User::factory()->create(['name' => 'أحمد محمود التميمي']);

        Review::factory()->create([
            'showrooms_id' => $showroom->id,
            'user_id' => $user->id,
            'comment' => 'تجربة شراء ممتازة جداً والخامات فاخرة',
        ]);

        Review::factory()->create([
            'comment' => 'خدمة سيئة وتأخير كبير في التوصيل',
        ]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.reviews.index', ['search' => 'التميمي']));

        $response->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.user.name', 'أحمد محمود التميمي');

        $response2 = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.reviews.index', ['search' => 'فاخرة']));

        $response2->assertOk()
            ->assertJsonPath('data.pagination.total', 1);
    }

    public function test_admin_can_view_single_review_details(): void
    {
        $showroom = Showroom::factory()->create(['name' => 'Track Furniture']);
        $room = Room::factory()->create([
            'showrooms_id' => $showroom->id,
            'name' => 'غرفة نوم مودرن Master Suite',
            'sku' => 'RM-0281',
        ]);
        $customer = User::factory()->create([
            'name' => 'محمد أحمد',
            'phone' => '01012345678',
            'email' => 'm_ahmed@example.com',
        ]);

        $review = Review::factory()->create([
            'user_id' => $customer->id,
            'showrooms_id' => $showroom->id,
            'reviewable_type' => 'room',
            'reviewable_id' => $room->id,
            'rating' => 4,
            'comment' => 'الغرفة جميلة جداً والخامات ممتازة وجودة الخشب رائعة',
            'status' => ReviewStatus::PENDING,
        ]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.reviews.show', $review->id));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $review->id)
            ->assertJsonPath('data.rating.score', 4)
            ->assertJsonPath('data.customer.name', 'محمد أحمد')
            ->assertJsonPath('data.customer.userRef', sprintf('#USR-%04d-EG', $customer->id))
            ->assertJsonPath('data.target.type', __('admin/review.target_badges.room'))
            ->assertJsonPath('data.target.name', 'غرفة نوم مودرن Master Suite')
            ->assertJsonPath('data.target.sku', __('admin/review.sku_label', ['sku' => 'RM-0281']))
            ->assertJsonPath('data.showroom.name', 'Track Furniture')
            ->assertJsonPath('data.operationalNote.affectsAverageRating', false);
    }

    public function test_admin_can_approve_review_via_patch_status(): void
    {
        $review = Review::factory()->create(['status' => ReviewStatus::PENDING]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->patchJson(route('admin.reviews.update-status', $review->id), [
                'status' => ReviewStatus::APPROVED->value,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status.value', ReviewStatus::APPROVED->value)
            ->assertJsonPath('data.operationalNote.affectsAverageRating', true);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'status' => ReviewStatus::APPROVED->value,
            'reviewed_by' => $this->admin->id,
            'reject_reason' => null,
        ]);

        // With string status 'approved'
        $review2 = Review::factory()->create(['status' => ReviewStatus::PENDING]);
        $response2 = $this
            ->actingAs($this->admin, 'web')
            ->patchJson(route('admin.reviews.update-status', $review2->id), [
                'status' => 'approved',
            ]);

        $response2->assertOk()
            ->assertJsonPath('data.status.value', ReviewStatus::APPROVED->value);
    }

    public function test_admin_can_hide_review_via_patch_status(): void
    {
        $review = Review::factory()->create(['status' => ReviewStatus::APPROVED]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->patchJson(route('admin.reviews.update-status', $review->id), [
                'status' => ReviewStatus::HIDDEN->value,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status.value', ReviewStatus::HIDDEN->value)
            ->assertJsonPath('data.operationalNote.affectsAverageRating', false);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'status' => ReviewStatus::HIDDEN->value,
            'reviewed_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_reject_review_via_patch_status_with_reason(): void
    {
        $review = Review::factory()->create(['status' => ReviewStatus::PENDING]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->patchJson(route('admin.reviews.update-status', $review->id), [
                'status' => ReviewStatus::REJECTED->value,
                'reject_reason' => 'التعليق يحتوي على عبارات غير لائقة مخالفة لسياسة الاستخدام.',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status.value', ReviewStatus::REJECTED->value)
            ->assertJsonPath('data.rejectReason', 'التعليق يحتوي على عبارات غير لائقة مخالفة لسياسة الاستخدام.');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'status' => ReviewStatus::REJECTED->value,
            'reviewed_by' => $this->admin->id,
            'reject_reason' => 'التعليق يحتوي على عبارات غير لائقة مخالفة لسياسة الاستخدام.',
        ]);
    }

    public function test_admin_cannot_reject_review_without_reason(): void
    {
        $review = Review::factory()->create(['status' => ReviewStatus::PENDING]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->patchJson(route('admin.reviews.update-status', $review->id), [
                'status' => ReviewStatus::REJECTED->value,
            ]);

        $response->assertUnprocessable();
    }

    public function test_admin_gets_descriptive_error_when_sending_invalid_status(): void
    {
        $review = Review::factory()->create(['status' => ReviewStatus::PENDING]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->patchJson(route('admin.reviews.update-status', $review->id), [
                'status' => 99,
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', __('admin/review.errors.status_invalid'));
    }
}
