<?php

namespace App\Services\V1\Auth;

use App\Enums\UserActive;
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
            ->where(function ($query) use ($login, $expectedRole): void {
                $query->where('email', $login);

                if ($expectedRole === UserScope::CLIENT) {
                    $query->orWhere('phone', $login);
                }
            })
            ->first();

        if (! $user) {
            throw new InvalidLoginException;
        }

        $role = $this->getRole($user);

        if ($role !== $expectedRole) {
            throw new InvalidLoginException;
        }

        $userActive = $this->getUserActive($user);

        if ($userActive === UserActive::PENDING_PASSWORD) {
            throw new AccountInactiveException($userActive->message());
        }

        if (! Hash::check($password, $user->password)) {
            throw new InvalidLoginException;
        }

        if ($userActive !== UserActive::ACTIVE) {
            throw new AccountInactiveException($userActive->message());
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

    private function getUserActive(User $user): UserActive
    {
        if ($user->user_active instanceof UserActive) {
            return $user->user_active;
        }

        if ($user->user_active === null) {
            return UserActive::INACTIVE;
        }

        return UserActive::tryFrom((int) $user->user_active) ?? UserActive::INACTIVE;
    }
}
