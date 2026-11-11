<?php

namespace App\Services\V1\Auth;

use App\Enums\UserActive;
use App\Enums\UserScope;
use App\Exceptions\AccountInactiveException;
use App\Exceptions\InvalidLoginException;
use App\Exceptions\InvalidLogoutException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MobileService
{
    public function login(string $login, string $password): array
    {
        $user = User::query()
            ->where(function ($query) use ($login): void {
                $query->where('email', $login)
                    ->orWhere('phone', $login);
            })
            ->first();

        if (! $user) {
            throw new InvalidLoginException;
        }

        $role = $user->role instanceof UserScope
            ? $user->role
            : UserScope::tryFrom((int) $user->role);

        if ($role !== UserScope::CLIENT) {
            throw new InvalidLoginException;
        }

        $userActive = $user->user_active instanceof UserActive
            ? $user->user_active
            : (UserActive::tryFrom((int) $user->user_active) ?? UserActive::INACTIVE);

        if ($userActive === UserActive::PENDING_PASSWORD) {
            throw new AccountInactiveException($userActive->message());
        }

        if (! Hash::check($password, $user->password)) {
            throw new InvalidLoginException;
        }

        if ($userActive !== UserActive::ACTIVE) {
            throw new AccountInactiveException($userActive->message());
        }

        $token = $user->createToken('mobile-client')->plainTextToken;

        return [
            [
                'avatar' => $user->avatar,
                'phone' => $user->phone,
                'email' => $user->email,
                'role' => $role->value,
            ],
            'token' => $token,
            'tokenType' => 'Bearer',
        ];
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if (! $token || ! method_exists($token, 'delete')) {
            throw new InvalidLogoutException;
        }

        $token->delete();
    }
}
