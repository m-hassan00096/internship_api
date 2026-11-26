<?php

namespace App\Http\Resources\V1\Admin;

use App\Models\Showroom;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Showroom
 */
class AdminShowroomCreatedResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'contractNumber' => $this->activeContract?->contract_number,
        ];
    }
}
