<?php

namespace App\Http\Resources\V1\Vendor;

use App\Models\RoomComponent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin RoomComponent
 */
class RoomComponentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'quantity' => (int) $this->quantity,
            'imagePath' => $this->image_path,
            'imageUrl' => $this->image_path
                ? (str_starts_with($this->image_path, 'http') ? $this->image_path : Storage::url($this->image_path))
                : null,
            'description' => $this->description,
            'sortOrder' => (int) $this->sort_order,
            'attributes' => RoomComponentAttributeResource::collection($this->whenLoaded('attributes')),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
