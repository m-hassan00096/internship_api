<?php

namespace App\Http\Resources\V1\Vendor;

use App\Enums\PackageStatus;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Package
 */
class PackageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof PackageStatus
            ? $this->status
            : PackageStatus::tryFrom((int) $this->status);

        $totalRoomsPrice = 0.0;
        if ($this->relationLoaded('rooms')) {
            $totalRoomsPrice = round((float) $this->rooms->sum(fn ($r) => (float) $r->finalPrice()), 2);
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'image' => $this->image_path ? (
                str_starts_with($this->image_path, 'http')
                    ? $this->image_path
                    : Storage::url($this->image_path)
            ) : null,
            'price' => ! is_null($this->price) ? (float) $this->price : null,
            'totalRoomsPrice' => $totalRoomsPrice > 0 ? $totalRoomsPrice : null,
            'status' => [
                'value' => $status?->value,
                'name' => $status?->name,
                'label' => $status?->label(),
            ],
            'currentStep' => (int) $this->current_step,
            'publishedAt' => $this->published_at?->toISOString(),
            'roomsCount' => $this->whenCounted('rooms', $this->rooms_count, $this->whenLoaded('rooms', fn () => $this->rooms->count())),
            'rooms' => $this->whenLoaded('rooms', function () {
                return $this->rooms->map(function ($room) {
                    $primaryImage = $room->primaryImage;

                    return [
                        'id' => $room->id,
                        'name' => $room->name,
                        'sku' => $room->sku,
                        'price' => (float) $room->price,
                        'finalPrice' => (float) $room->finalPrice(),
                        'primaryImage' => $primaryImage ? (
                            str_starts_with($primaryImage->image_path, 'http')
                                ? $primaryImage->image_path
                                : Storage::url($primaryImage->image_path)
                        ) : null,
                        'components' => $room->relationLoaded('components') ? $room->components->map(function ($c) {
                            return [
                                'id' => $c->id,
                                'name' => $c->name,
                                'quantity' => (int) $c->quantity,
                            ];
                        }) : null,
                        'sortOrder' => (int) ($room->pivot->sort_order ?? 0),
                    ];
                });
            }),
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
