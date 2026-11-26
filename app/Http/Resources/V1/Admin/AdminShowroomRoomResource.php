<?php

namespace App\Http\Resources\V1\Admin;

use App\Enums\RoomStatus;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Room
 */
class AdminShowroomRoomResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof RoomStatus
            ? $this->status
            : RoomStatus::tryFrom((int) $this->status);

        $imagePath = $this->primaryImage?->image_path
            ?? $this->images->first()?->image_path;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'image' => $imagePath,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => method_exists($this->category, 'localizedName')
                    ? $this->category->localizedName()
                    : ($this->category->name ?? null),
            ] : null,
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
