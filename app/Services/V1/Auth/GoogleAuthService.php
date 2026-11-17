<?php

namespace App\Services\V1\Auth;

use App\Enums\HttpStatusCode;
use App\Enums\UserActive;
use App\Enums\UserScope;
use App\Exceptions\AccountInactiveException;
use App\Exceptions\GoogleAuthException;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthService
{
    /**
     * @return array<string, mixed>
     *
     * @throws GoogleAuthException
     */
    public function verifyIdToken(string $idToken): array
    {
        if (app()->isLocal() && in_array($idToken, ['mock-google-token', 'dev-token'], true)) {
            return [
                'sub' => 'google-mock-client-123456',
                'email' => 'client.test@baytfolio.sa',
                'email_verified' => 'true',
                'picture' => 'https://lh3.googleusercontent.com/a/default-user.png',
                'name' => 'Mohamed Mahmoud',
            ];
        }

        try {
            $response = Http::timeout(10)
                ->asJson()
                ->get('https://oauth2.googleapis.com/tokeninfo', [
                    'id_token' => $idToken,
                ]);
        } catch (\Throwable) {
            throw new GoogleAuthException(
                __('auth.google.failed'),
                HttpStatusCode::UNAUTHORIZED
            );
        }

        if (! $response->successful()) {
            throw new GoogleAuthException(
                __('auth.google.invalid_token'),
                HttpStatusCode::UNAUTHORIZED
            );
        }

        $payload = $response->json();

        if (! is_array($payload) || ! empty($payload['error'])) {
            throw new GoogleAuthException(
                __('auth.google.invalid_token'),
                HttpStatusCode::UNAUTHORIZED
            );
        }

        $googleId = $payload['sub'] ?? null;
        $email = $payload['email'] ?? null;

        if (empty($googleId)) {
            throw new GoogleAuthException(
                __('auth.google.invalid_token'),
                HttpStatusCode::UNAUTHORIZED
            );
        }

        if (empty($email)) {
            throw new GoogleAuthException(
                __('auth.google.email_required'),
                HttpStatusCode::BAD_REQUEST
            );
        }

        $configuredClientId = (string) config('services.google.client_id');
        if ($configuredClientId !== '') {
            $allowedClients = array_filter(array_map('trim', explode(',', $configuredClientId)));
            $aud = (string) ($payload['aud'] ?? '');
            $azp = (string) ($payload['azp'] ?? '');

            if (! in_array($aud, $allowedClients, true) && ! in_array($azp, $allowedClients, true)) {
                throw new GoogleAuthException(
                    __('auth.google.invalid_audience'),
                    HttpStatusCode::UNAUTHORIZED
                );
            }
        }

        return $payload;
    }

    /**
     * Resolve existing client user or register a new one.
     *
     * @throws GoogleAuthException
     * @throws AccountInactiveException
     */
    public function findOrCreateUser(
        string $googleId,
        string $email,
        ?string $avatar = null
    ): User {
        // 1. Match by Google ID
        $user = User::query()->where('google_id', $googleId)->first();

        if ($user) {
            $this->ensureClientRole($user);
            $this->ensureUserIsActive($user);

            if (! empty($avatar) && empty($user->avatar)) {
                $user->avatar = $avatar;
                $user->save();
            }

            return $user;
        }

        // 2. Match by Email (Omnichannel account linking)
        $user = User::query()->where('email', $email)->first();

        if ($user) {
            $this->ensureClientRole($user);
            $this->ensureUserIsActive($user);

            $user->google_id = $googleId;

            if (! empty($avatar) && empty($user->avatar)) {
                $user->avatar = $avatar;
            }

            if ($user->email_verified_at === null) {
                $user->email_verified_at = now();
            }

            $user->save();

            return $user;
        }

        // 3. Register new Client user
        return User::create([
            'email' => $email,
            'google_id' => $googleId,
            'avatar' => $avatar,
            'role' => UserScope::CLIENT,
            'user_active' => UserActive::ACTIVE,
            'password' => Hash::make(Str::random(32)),
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Handle Mobile Google Login flow.
     *
     * @return array{user: array<string, mixed>, token: string}
     */
    public function handleMobileLogin(string $idToken): array
    {
        $payload = $this->verifyIdToken($idToken);

        $googleId = (string) $payload['sub'];
        $email = (string) $payload['email'];
        $avatar = isset($payload['picture']) ? (string) $payload['picture'] : null;

        $user = $this->findOrCreateUser($googleId, $email, $avatar);

        $token = $user->createToken('mobile-client')->plainTextToken;

        $role = $user->role instanceof UserScope
            ? $user->role
            : UserScope::tryFrom((int) $user->role);

        return [
            'user' => [
                // 'id' => $user->id,
                'avatar' => $user->avatar,
                // 'phone' => $user->phone,
                'email' => $user->email,
                'role' => $role?->value ?? UserScope::CLIENT->value,
            ],
            'token' => $token,
            // 'tokenType' => 'Bearer',
        ];
    }

    /**
     * Generate Google OAuth redirect URL for Web client.
     */
    public function getWebRedirectUrl(): string
    {
        return Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
    }

    /**
     * Handle Web Google OAuth Callback.
     *
     * @throws GoogleAuthException
     */
    public function handleWebCallback(): User
    {
        if (app()->isLocal() && request('code') === 'mock-code') {
            $user = $this->findOrCreateUser(
                'google-mock-client-123456',
                'client.test@baytfolio.sa',
                'https://lh3.googleusercontent.com/a/default-user.png'
            );

            Auth::guard('web')->login($user);

            return $user;
        }

        try {
            /** @var \Laravel\Socialite\Contracts\User $googleUser */
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable) {
            throw new GoogleAuthException(
                __('auth.google.failed'),
                HttpStatusCode::UNAUTHORIZED
            );
        }

        $googleId = $googleUser->getId();
        $email = $googleUser->getEmail();

        if (empty($googleId) || empty($email)) {
            throw new GoogleAuthException(
                __('auth.google.invalid_token'),
                HttpStatusCode::BAD_REQUEST
            );
        }

        $user = $this->findOrCreateUser(
            (string) $googleId,
            (string) $email,
            $googleUser->getAvatar()
        );

        Auth::guard('web')->login($user);

        return $user;
    }

    /**
     * Ensure the user belongs to the CLIENT portal.
     *
     * @throws GoogleAuthException
     */
    protected function ensureClientRole(User $user): void
    {
        $role = $user->role instanceof UserScope
            ? $user->role
            : UserScope::tryFrom((int) $user->role);

        if ($role !== UserScope::CLIENT) {
            throw new GoogleAuthException(
                __('auth.google.invalid_role'),
                HttpStatusCode::FORBIDDEN
            );
        }
    }

    /**
     * Ensure the user account is active.
     *
     * @throws AccountInactiveException
     */
    protected function ensureUserIsActive(User $user): void
    {
        $status = $user->user_active instanceof UserActive
            ? $user->user_active
            : (UserActive::tryFrom((int) $user->user_active) ?? UserActive::INACTIVE);

        if ($status === UserActive::PENDING_PASSWORD) {
            throw new AccountInactiveException($status->message());
        }

        if ($status !== UserActive::ACTIVE) {
            throw new AccountInactiveException($status->message());
        }
    }
}
