<?php

namespace App\Http\Resources\V1\Admin;

use App\Enums\PackageStatus;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Package
 */
class AdminShowroomPackageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof PackageStatus
            ? $this->status
            : PackageStatus::tryFrom((int) $this->status);

        $roomsCount = $this->rooms_count ?? $this->rooms->count();

        $itemsCount = (int) $this->rooms->sum(function ($room) {
            return $room->components ? ($room->components->sum('quantity') ?: $room->components->count()) : 0;
        });

        $categoryNames = $this->rooms
            ->map(function ($room) {
                if ($room->category) {
                    return method_exists($room->category, 'localizedName')
                        ? $room->category->localizedName()
                        : $room->category->name;
                }

                return $room->name;
            })
            ->filter()
            ->unique()
            ->values()
            ->implode(' + ');

        $roomsDetail = $categoryNames ? "({$categoryNames})" : '';
        $spacesLabel = $roomsCount > 1 ? 'مساحات' : 'مساحة';
        $itemsLabel = $itemsCount > 1 ? 'عناصر' : 'عنصر';
        $displayText = "{$roomsCount} {$spacesLabel}".($itemsCount > 0 ? " / {$itemsCount} {$itemsLabel}" : '');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'image' => $this->image_path,
            'content' => [
                'spacesCount' => $roomsCount,
                'itemsCount' => $itemsCount,
                'displayText' => $displayText,
                'roomsDetail' => $roomsDetail,
            ],
            'roomsSummary' => $displayText.($roomsDetail ? " {$roomsDetail}" : ''),
            'roomsCount' => $roomsCount,
            'itemsCount' => $itemsCount,
            'price' => (float) $this->price,
            'status' => [
                'value' => $status?->value ?? (int) $this->status,
                'label' => $status?->label() ?? '',
            ],
            'updatedAt' => $this->updated_at?->diffForHumans(),
            'updatedAtFormatted' => $this->updated_at?->format('Y-m-d H:i'),
        ];
    }
}
