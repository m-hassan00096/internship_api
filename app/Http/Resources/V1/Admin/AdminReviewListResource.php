<?php

namespace App\Http\Resources\V1\Admin;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @mixin Review
 */
class AdminReviewListResource extends JsonResource
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

        $handle = null;
        if ($this->user) {
            if (! empty($this->user->email)) {
                $handle = '@'.explode('@', (string) $this->user->email)[0];
            } elseif (! empty($this->user->name)) {
                $handle = '@'.Str::slug($this->user->name, '_');
            } else {
                $handle = sprintf('@user_%d', $this->user->id);
            }
        }

        $showroomName = $this->showroom?->name
            ?? ($this->reviewable instanceof Room ? $this->reviewable->showroom?->name : null);

        $targetType = $this->reviewable instanceof Room
            ? __('admin/review.target_types.room')
            : __('admin/review.target_types.showroom');

        return [
            'id' => $this->id,
            'user' => [
                'avatar' => $this->formatImageUrl($this->user?->avatar),
                'name' => $this->user?->name ?? __('admin/user.anonymous_user'),
                'handle' => $handle,
            ],
            'rating' => sprintf('%.1f / 5 ★', (float) $this->rating),
            'target' => [
                'type' => $targetType,
                'name' => $this->reviewable?->name,
            ],
            'showroom' => $showroomName,
            'comment' => $this->comment,
            'status' => [
                'value' => $status?->value ?? (int) $this->status,
                'label' => $statusLabel,
                'badgeColor' => $badgeColor,
            ],
            'date' => $this->created_at?->format('d/m/Y'),
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
