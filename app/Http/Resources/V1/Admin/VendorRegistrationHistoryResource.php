<?php

namespace App\Http\Resources\V1\Admin;

use App\Enums\RegistrationStatus;
use App\Models\VendorRegistrationHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VendorRegistrationHistory
 */
class VendorRegistrationHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof RegistrationStatus
            ? $this->status
            : RegistrationStatus::tryFrom((int) $this->status);

        return [
            'id' => $this->id,
            'status' => [
                'value' => $status?->value,
                'name' => $status?->name,
                'label' => $status?->label(),
            ],
            'notes' => $this->notes,
            'changedBy' => $this->whenLoaded('changedBy', function (): ?array {
                if (! $this->changedBy) {
                    return null;
                }

                return [
                    'id' => $this->changedBy->id,
                    'email' => $this->changedBy->email,
                ];
            }),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
