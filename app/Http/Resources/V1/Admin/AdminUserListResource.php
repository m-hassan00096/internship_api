<?php

namespace App\Http\Resources\V1\Admin;

use App\Enums\UserActive;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class AdminUserListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->user_active instanceof UserActive
            ? $this->user_active
            : UserActive::tryFrom((int) $this->user_active);

        $authMethodValue = $this->google_id ? 'google' : 'phone_password';
        $authMethodLabel = $this->google_id ? 'Google' : 'Phone + Password';

        return [
            'id' => $this->id,
            'userRef' => sprintf('#USR-%04d', $this->id),
            'name' => $this->name ?? __('admin/user.anonymous_user'),
            'phone' => $this->phone,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'authMethod' => [
                'value' => $authMethodValue,
                'label' => $authMethodLabel,
            ],
            'status' => [
                'value' => $status?->value ?? (int) $this->user_active,
                'name' => $status?->name ?? 'UNKNOWN',
                'label' => $status?->label() ?? '',
            ],
            'registeredAt' => $this->created_at?->format('Y-m-d'),
            'registeredAtFormatted' => $this->created_at?->format('d/m/Y'),
            'lastLoginAt' => $this->updated_at?->diffForHumans(),
        ];
    }
}
