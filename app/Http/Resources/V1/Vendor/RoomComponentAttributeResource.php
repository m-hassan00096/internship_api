<?php

namespace App\Http\Resources\V1\Vendor;

use App\Models\RoomComponentAttribute;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RoomComponentAttribute
 */
class RoomComponentAttributeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'value' => $this->value,
            'unit' => $this->unit,
            'sortOrder' => (int) $this->sort_order,
        ];
    }
}
