<?php

namespace App\Http\Resources\V1\Admin;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\Room;
use App\Models\Showroom;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Review
 */
class AdminReviewDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof ReviewStatus
            ? $this->status
            : ReviewStatus::tryFrom((int) $this->status);

        $badgeColor = match ($status) {
            ReviewStatus::PENDING => 'warning',
            ReviewStatus::APPROVED => 'success',
            ReviewStatus::REJECTED => 'danger',
            ReviewStatus::HIDDEN => 'neutral',
            default => 'default',
        };

        $statusLabel = match ($status) {
            ReviewStatus::PENDING => __('admin/review.status.PENDING'),
            ReviewStatus::APPROVED => __('admin/review.status.APPROVED'),
            ReviewStatus::REJECTED => __('admin/review.status.REJECTED'),
            ReviewStatus::HIDDEN => __('admin/review.status.HIDDEN'),
            default => '',
        };

        $ratingScore = (float) $this->rating;
        $ratingPercentage = (int) round(($ratingScore / 5) * 100);
        $sentimentLabel = match (true) {
            $ratingScore >= 4 => __('admin/review.sentiments.positive', ['percentage' => $ratingPercentage]),
            $ratingScore >= 3 => __('admin/review.sentiments.neutral', ['percentage' => $ratingPercentage]),
            default => __('admin/review.sentiments.negative', ['percentage' => $ratingPercentage]),
        };

        // Customer initials
        $initials = 'م';
        if ($this->user?->name) {
            $parts = preg_split('/\s+/', trim((string) $this->user->name));
            if (count($parts) >= 2) {
                $initials = mb_substr($parts[0], 0, 1).' '.mb_substr($parts[1], 0, 1);
            } else {
                $initials = mb_substr($parts[0], 0, 1);
            }
        }

        // Masked phone & email matching Figma preview
        $rawPhone = $this->user?->phone ?? '';
        $maskedPhone = $rawPhone;
        if (strlen($rawPhone) >= 10) {
            $maskedPhone = '+20 '.substr($rawPhone, 0, 3).' *** '.substr($rawPhone, -4);
        }

        $rawEmail = $this->user?->email ?? '';
        $maskedEmail = $rawEmail;
        if (str_contains($rawEmail, '@')) {
            $emailParts = explode('@', $rawEmail);
            $maskedEmail = substr($emailParts[0], 0, 1).'***@'.$emailParts[1];
        }

        // Target entity
        $target = $this->reviewable;
        $targetType = $target instanceof Room ? __('admin/review.target_badges.room') : __('admin/review.target_badges.showroom');
        $targetName = $target?->name;
        $targetSku = null;
        $targetImage = null;

        if ($target instanceof Room) {
            $targetSku = $target->sku ? __('admin/review.sku_label', ['sku' => $target->sku]) : null;
            $primaryImage = $target->primaryImage ?? $target->images?->first();
            $targetImage = $this->formatImageUrl($primaryImage?->image_path);
        } elseif ($target instanceof Showroom) {
            $targetImage = $this->formatImageUrl($target->logo);
        }

        // Showroom
        $showroom = $this->showroom ?? ($target instanceof Room ? $target->showroom : null);

        // Operational Note matching Figma note
        $operationalNote = match ($status) {
            ReviewStatus::APPROVED => __('admin/review.operational_notes.approved'),
            ReviewStatus::REJECTED => __('admin/review.operational_notes.rejected'),
            ReviewStatus::HIDDEN => __('admin/review.operational_notes.hidden'),
            default => __('admin/review.operational_notes.pending'),
        };

        // Formatted timestamp: 11 أكتوبر 2026 - 02:45 م
        $formattedDate = $this->created_at?->format('d/m/Y - h:i A');

        return [
            'id' => $this->id,
            'reviewRef' => sprintf('#REV-%s-%04d', $this->created_at?->format('Y') ?? date('Y'), $this->id),
            'createdAtFormatted' => $formattedDate,
            'status' => [
                'value' => $status?->value ?? (int) $this->status,
                'label' => $statusLabel,
                'badgeColor' => $badgeColor,
            ],
            'customer' => [
                'name' => $this->user?->name ?? __('admin/user.anonymous_user'),
                'userRef' => $this->user ? sprintf('#USR-%04d-EG', $this->user->id) : null,
                'avatar' => $this->formatImageUrl($this->user?->avatar),
                'initials' => $initials,
                'phone' => $maskedPhone,
                'email' => $maskedEmail,
            ],
            'rating' => [
                'score' => $ratingScore,
                'scoreFormatted' => sprintf('%.1f / 5.0', $ratingScore),
                'stars' => (int) round($ratingScore),
                'percentage' => $ratingPercentage,
                'sentimentLabel' => $sentimentLabel,
            ],
            'target' => [
                'type' => $targetType,
                'name' => $targetName,
                'sku' => $targetSku,
                'image' => $targetImage,
            ],
            'showroom' => [
                'name' => $showroom?->name,
                'logo' => $this->formatImageUrl($showroom?->logo),
            ],
            'comment' => $this->comment,
            'operationalNote' => [
                'title' => __('admin/review.operational_note_title'),
                'text' => $operationalNote,
                'affectsAverageRating' => $status === ReviewStatus::APPROVED,
            ],
            'rejectReason' => $this->when($status === ReviewStatus::REJECTED, $this->reject_reason),
            'moderation' => $this->when($this->reviewed_by !== null, [
                'reviewedBy' => $this->reviewer ? [
                    'id' => $this->reviewer->id,
                    'name' => $this->reviewer->name,
                    'email' => $this->reviewer->email,
                ] : null,
                'reviewedAt' => $this->reviewed_at?->format('Y-m-d H:i'),
                'reviewedAtFormatted' => $this->reviewed_at?->format('d/m/Y - h:i A'),
            ]),
        ];
    }

    protected function formatImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::url($path);
    }
}
