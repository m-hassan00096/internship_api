<?php

namespace App\Services\V1\Vendor;

use App\Enums\VerificationStatus;
use App\Exceptions\ShowroomActionException;
use App\Exceptions\ShowroomVerificationException;
use App\Models\Showroom;
use App\Models\ShowroomBranch;
use App\Models\ShowroomContract;
use App\Models\ShowroomVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ShowroomService
{
    /**
     * جلب المعرض التابع للمستخدم (سواء كان المالك أو موظف تابع للمالك)
     */
    public function getShowroom(User $user): Showroom
    {
        $ownerId = $user->manager_id ?? $user->id;

        $showroom = Showroom::query()
            ->with(['businessType', 'activeContract', 'activeVerification', 'branches'])
            ->where('user_id', $ownerId)
            ->first();

        if (! $showroom) {
            throw new ShowroomActionException(__('vendor/showroom.errors.not_found'));
        }

        return $showroom;
    }

    /**
     * جلب تفاصيل العقد الساري للمعرض
     */
    public function getContract(User $user): ShowroomContract
    {
        $showroom = $this->getShowroom($user);

        $contract = $showroom->activeContract()->first()
            ?? $showroom->contracts()->first();

        if (! $contract) {
            throw new ShowroomActionException(__('vendor/showroom.errors.no_active_contract'));
        }

        return $contract;
    }

    /**
     * تقديم طلب توثيق جديد للمعرض بالعلامة الزرقاء
     */
    public function applyVerification(User $user, int $month, float $monthlyPrice = 500.00): ShowroomVerification
    {
        $showroom = $this->getShowroom($user);

        $hasPending = $showroom->verifications()
            ->where('status', VerificationStatus::PENDING->value)
            ->exists();

        if ($hasPending) {
            throw new ShowroomVerificationException(__('vendor/showroom.errors.pending_verification_exists'));
        }

        if ($showroom->isVerified()) {
            throw new ShowroomVerificationException(__('vendor/showroom.errors.already_verified'));
        }

        $totalAmount = $month * $monthlyPrice;

        return $showroom->verifications()->create([
            'month' => $month,
            'monthly_price' => $monthlyPrice,
            'total_amount' => $totalAmount,
            'status' => VerificationStatus::PENDING,
        ]);
    }

    /**
     * جلب آخر طلب توثيق للمعرض
     */
    public function getLatestVerification(User $user): ?ShowroomVerification
    {
        $showroom = $this->getShowroom($user);

        return $showroom->verifications()->first();
    }

    /**
     * جلب فروع المعرض
     *
     * @return Collection<int, ShowroomBranch>
     */
    public function listBranches(User $user): Collection
    {
        $showroom = $this->getShowroom($user);

        return $showroom->branches;
    }

    /**
     * جلب فرع محدد تابع لمعرض المستخدم
     */
    public function getBranch(User $user, int $branchId): ShowroomBranch
    {
        $showroom = $this->getShowroom($user);

        $branch = $showroom->branches()->find($branchId);
        if (! $branch) {
            throw new ShowroomActionException(__('vendor/showroom.errors.branch_not_found'));
        }

        return $branch;
    }
}
