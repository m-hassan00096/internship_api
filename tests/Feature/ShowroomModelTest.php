<?php

namespace Tests\Feature;

use App\Enums\ContractStatus;
use App\Enums\ShowroomStatus;
use App\Enums\VerificationStatus;
use App\Models\BusinessType;
use App\Models\Showroom;
use App\Models\ShowroomContract;
use App\Models\ShowroomVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowroomModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_showroom_with_factory_and_relations(): void
    {
        $user = User::factory()->create();
        $businessType = BusinessType::factory()->create();

        $showroom = Showroom::factory()->create([
            'user_id' => $user->id,
            'business_type_id' => $businessType->id,
            'name' => 'معرض فيلو للأثاث',
            'slug' => 'filo-furniture',
            'status' => ShowroomStatus::ACTIVE,
        ]);

        $this->assertDatabaseHas('showrooms', [
            'id' => $showroom->id,
            'name' => 'معرض فيلو للأثاث',
            'slug' => 'filo-furniture',
            'status' => ShowroomStatus::ACTIVE->value,
        ]);

        $this->assertTrue($showroom->user->is($user));
        $this->assertTrue($showroom->businessType->is($businessType));
        $this->assertTrue($user->showroom->is($showroom));
        $this->assertSame(ShowroomStatus::ACTIVE, $showroom->status);
    }

    public function test_showroom_contract_relation_and_active_contract_scope(): void
    {
        $showroom = Showroom::factory()->create();

        $activeContract = ShowroomContract::factory()->create([
            'showrooms_id' => $showroom->id,
            'started_at' => now()->subMonth()->toDateString(),
            'end_at' => now()->addMonths(11)->toDateString(),
            'max_rooms' => 25,
            'max_packages' => 10,
            'status' => ContractStatus::ACTIVE,
        ]);

        $expiredContract = ShowroomContract::factory()->expired()->create([
            'showrooms_id' => $showroom->id,
        ]);

        $this->assertCount(2, $showroom->fresh()->contracts);
        $this->assertNotNull($showroom->fresh()->activeContract);
        $this->assertTrue($showroom->fresh()->activeContract->is($activeContract));
        $this->assertSame(25, $activeContract->max_rooms);
        $this->assertSame(10, $activeContract->max_packages);
        $this->assertTrue($expiredContract->isExpired());
    }

    public function test_showroom_verification_relation_and_is_verified_helper(): void
    {
        $showroom = Showroom::factory()->create();

        $this->assertFalse($showroom->isVerified());

        $pendingVerification = ShowroomVerification::factory()->create([
            'showrooms_id' => $showroom->id,
            'month' => 6,
            'monthly_price' => 500.00,
            'total_amount' => 3000.00,
            'status' => VerificationStatus::PENDING,
        ]);

        $this->assertFalse($showroom->fresh()->isVerified());

        $approvedVerification = ShowroomVerification::factory()->approved()->create([
            'showrooms_id' => $showroom->id,
            'start_at' => now()->subDay(),
            'end_at' => now()->addMonths(6),
        ]);

        $this->assertTrue($showroom->fresh()->isVerified());
        $this->assertNotNull($showroom->fresh()->activeVerification);
        $this->assertTrue($showroom->fresh()->activeVerification->is($approvedVerification));
    }

    public function test_enum_labels_translate_correctly_in_both_languages(): void
    {
        app()->setLocale('ar');
        $this->assertSame('نشط', ShowroomStatus::ACTIVE->label());
        $this->assertSame('ساري', ContractStatus::ACTIVE->label());
        $this->assertSame('معتمد وموثق', VerificationStatus::APPROVED->label());
        $this->assertSame('غير موثق', VerificationStatus::UNVERIFIED->label());

        app()->setLocale('en');
        $this->assertSame('Active', ShowroomStatus::ACTIVE->label());
        $this->assertSame('Active', ContractStatus::ACTIVE->label());
        $this->assertSame('Verified', VerificationStatus::APPROVED->label());
        $this->assertSame('Unverified', VerificationStatus::UNVERIFIED->label());
    }
}
