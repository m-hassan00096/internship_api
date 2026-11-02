<?php

namespace App\Http\Resources;

use App\Enums\UserScope;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $roleName = $this->role instanceof UserScope
            ? $this->role->name
            : (UserScope::tryFrom((int) $this->role)?->name ?? '');

        return [
            'email' => $this->email,
            'scope' => strtolower($roleName),
        ];
    }
}
