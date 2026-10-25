<?php

namespace App\Services\V1\Auth;

use App\Enums\UserRole;
use App\Exceptions\InvalidLoginException;
use App\Exceptions\InvalidLogoutException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MobileService
{
    public function login(string $login, string $password): array
    {
        $user = User::query()
            ->where(function ($query) use ($login) {
                $query->where('email', $login)
                    ->orWhere('phone', $login);
            })
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw new InvalidLoginException();
        }

        $role = $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((int) $user->role);

        if ($role !== UserRole::CLIENT) {
            throw new InvalidLoginException();
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
            'token_type' => 'Bearer',
        ];
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if (!$token || !method_exists($token, 'delete')) {
            throw new InvalidLogoutException();
        }

        $token->delete();
    }
}
