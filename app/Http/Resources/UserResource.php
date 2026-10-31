<?php

namespace App\Http\Resources;

use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $roleName = $this->role instanceof UserRole
            ? $this->role->name
            : (UserRole::tryFrom((int) $this->role)?->name ?? '');

        return [
            'email' => $this->email,
            'role' => strtolower($roleName),
            'managerId' => $this->whenNotNull($this->manager_id),
        ];
    }
}
