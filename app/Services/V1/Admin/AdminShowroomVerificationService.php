<?php

namespace App\Services\V1\Admin;

use App\Enums\VerificationStatus;
use App\Models\Showroom;
use App\Models\ShowroomVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class AdminShowroomVerificationService
{
    /**
     * Get all verification records for a showroom.
     *
     * @return Collection<int, ShowroomVerification>
     */
    public function getShowroomVerifications(int|string $id): Collection
    {
        $showroom = Showroom::findOrFail($id);

        return $showroom->verifications()
            ->with('reviewer')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Manage and set official verification status for a showroom.
     *
     * @param  array<string, mixed>  $data
     */
    public function manageVerification(
        int|string $id,
        array $data,
        User $adminUser
    ): ShowroomVerification {
        $showroom = Showroom::findOrFail($id);

        $status = (int) $data['status'];
        $month = (int) $data['month'];
        $monthlyPrice = (float) ($data['monthlyPrice'] ?? $data['monthly_price']);
        $totalAmount = isset($data['totalAmount']) || isset($data['total_amount'])
            ? (float) ($data['totalAmount'] ?? $data['total_amount'])
            : ($month * $monthlyPrice);

        $startAt = ! empty($data['startAt'])
            ? $data['startAt']
            : (! empty($data['start_at'])
                ? $data['start_at']
                : ($status === VerificationStatus::APPROVED->value ? now() : null));

        $endAt = ! empty($data['endAt'])
            ? $data['endAt']
            : (! empty($data['end_at'])
                ? $data['end_at']
                : ($status === VerificationStatus::APPROVED->value && $startAt ? now()->parse($startAt)->addMonths($month) : null));

        return $showroom->verifications()->create([
            'month' => $month,
            'monthly_price' => $monthlyPrice,
            'total_amount' => $totalAmount,
            'status' => $status,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'review_by' => $adminUser->id,
            'review_at' => now(),
            'reject_reason' => $data['rejectReason'] ?? $data['reject_reason'] ?? null,
        ]);
    }
}
