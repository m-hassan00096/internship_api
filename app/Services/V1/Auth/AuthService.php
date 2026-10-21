<?php

namespace App\Services\V1\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function login(string $login, string $password): ?array
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
            return null;
        }

        if (
            in_array($user->role, [UserRole::VENDOR, UserRole::ADMIN], true)
            && $user->email !== $login
        ) {
            return null;
        }

        // Create a Sanctum token.
        // $token = $user->createToken('auth-token')->plainTextToken;
        Auth::guard('web')->login($user);


        return [
            'user' => [
                'avatar' => $user->avatar,
                'phone' => $user->phone,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
            // 'token' => $token,
            // 'token_type' => 'Bearer',
        ];
    }

    public function logout(User $user): void
    {
        Auth::guard('web')->logout();

        // $token = $user->currentAccessToken()?->delete();

        // if ($token && method_exists($token, 'delete')) {
        //     $token->delete();
        // }
    }
}
