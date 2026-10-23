<?php

namespace App\Services\V1\Auth;

use App\Enums\UserRole;
use App\Exceptions\InvalidLoginException;
use App\Exceptions\InvalidLogoutException;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function login(
        string $login,
        string $password,
        UserRole $expectedRole
    ): array {
        $user = User::query()
            ->where(function ($query) use ($login, $expectedRole) {
                $query->where('email', $login);

                if ($expectedRole === UserRole::CLIENT) {
                    $query->orWhere('phone', $login);
                }
            })
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw new InvalidLoginException();
        }

        $role = $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((int) $user->role);

        if ($role !== $expectedRole) {
            throw new InvalidLoginException();
        }

        Auth::guard('web')->login($user);

        return [
            'avatar' => $user->avatar,
            'phone' => $user->phone,
            'email' => $user->email,
            'role' => $role->value,
        ];
    }

    public function logout(UserRole $expectedRole): void
    {
        $guard = Auth::guard('web');
        $user = $guard->user();

        if (!$user) {
            throw new InvalidLogoutException();
        }

        $role = $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((int) $user->role);

        if ($role !== $expectedRole) {
            throw new InvalidLogoutException();
        }

        $guard->logout();
    }
}
