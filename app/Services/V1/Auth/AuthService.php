<?php

namespace App\Services\V1\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Exceptions\InvalidLoginException;
use App\Exceptions\InvalidLogoutException;
class AuthService
{
    public function login(string $login, string $password): array
    {
        $user = User::query()
            ->where('email', $login)
            ->first();

        if (!$user) {
            $user = User::query()
                ->where('phone', $login)
                ->first();
        }
        // dd($user);

        if (!$user || !Hash::check($password, $user->password)) {
            throw new InvalidLoginException();
        }

        if (
            in_array($user->role, [UserRole::VENDOR, UserRole::ADMIN], true)
            && $user->email !== $login
        ) 
        {
            throw new InvalidLoginException();
        }

        // Create a Sanctum token.
        // $token = $user->createToken('auth-token')->plainTextToken;
        Auth::guard('web')->login($user);


        return [
             [
                'avatar' => $user->avatar,
                'phone' => $user->phone,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
            // 'token' => $token,
            // 'token_type' => 'Bearer',
        ];
    }

    public function logout(): void
    {
    if (!Auth::guard('web')->check()) {
        throw new InvalidLogoutException();
    }

    Auth::guard('web')->logout();
}
        // $token = $user->currentAccessToken()?->delete();

        // if ($token && method_exists($token, 'delete')) {
        //     $token->delete();
        // }
    }

