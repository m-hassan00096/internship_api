<?php

namespace App\Http\Resources\V1\Vendor;

use App\Models\RoomImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin RoomImage
 */
class RoomImageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'imagePath' => $this->image_path,
            'url' => str_starts_with($this->image_path, 'http')
                ? $this->image_path
                : Storage::url($this->image_path),
            'isPrimary' => (bool) $this->is_primary,
            'sortOrder' => (int) $this->sort_order,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
