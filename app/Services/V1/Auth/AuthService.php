<?php

namespace App\Services\V1\Auth;

use App\Enums\UserScope;
use App\Exceptions\AccountInactiveException;
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
        UserScope $expectedRole
    ): User {
        $user = User::query()
            ->where(function ($query) use ($login, $expectedRole) {
                $query->where('email', $login);

                if ($expectedRole === UserScope::CLIENT) {
                    $query->orWhere('phone', $login);
                }
            })
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new InvalidLoginException;
        }

        if (! $user->is_active) {
            throw new AccountInactiveException;
        }

        $role = $this->getRole($user);

        if ($role !== $expectedRole) {
            throw new InvalidLoginException;
        }

        Auth::guard('web')->login($user);

        return $user;
    }

    public function logout(UserScope $expectedRole): void
    {
        $guard = Auth::guard('web');
        $user = $guard->user();

        if (! $user) {
            throw new InvalidLogoutException;
        }

        $role = $this->getRole($user);

        if ($role !== $expectedRole) {
            throw new InvalidLogoutException;
        }

        $guard->logout();
    }

    private function getRole(User $user): UserScope
    {
        $role = $user->role instanceof UserScope
            ? $user->role
            : UserScope::tryFrom((int) $user->role);

        if (! $role) {
            throw new InvalidLoginException;
        }

        return $role;
    }
}
