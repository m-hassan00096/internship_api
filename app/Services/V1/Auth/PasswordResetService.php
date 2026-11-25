<?php

namespace App\Services\V1\Auth;

use App\Enums\HttpStatusCode;
use App\Enums\UserActive;
use App\Enums\UserScope;
use App\Exceptions\AccountInactiveException;
use App\Exceptions\PasswordResetException;
use App\Mail\PasswordResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Services\V1\Auth\Channels\WhatsAppOtpChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PasswordResetService
{
    public const int OTP_TTL_MINUTES = 10;

    public const int RESEND_COOLDOWN_SECONDS = 60;

    public const int MAX_ATTEMPTS = 5;

    public function __construct(
        private WhatsAppOtpChannel $whatsAppChannel
    ) {}

    public function sendOtp(string $identifier, UserScope $expectedScope, string $channel = 'email'): string
    {
        $normalizedIdentifier = trim($identifier);
        $resolvedChannel = ($channel === 'whatsapp' && $expectedScope === UserScope::CLIENT)
            ? 'whatsapp'
            : 'email';

        $user = $this->findScopedUser($normalizedIdentifier, $expectedScope, $resolvedChannel);

        $throttledRemaining = DB::transaction(function () use ($user, $normalizedIdentifier, $resolvedChannel): ?int {
            $existing = PasswordResetOtp::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $cooldownEndsAt = $existing->sent_at->copy()->addSeconds(self::RESEND_COOLDOWN_SECONDS);

                if ($cooldownEndsAt->isFuture()) {
                    return max(1, (int) ceil(now()->diffInSeconds($cooldownEndsAt, false)));
                }
            }

            $otp = (string) random_int(100000, 999999);

            PasswordResetOtp::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'identifier' => $normalizedIdentifier,
                    'channel' => $resolvedChannel,
                    'otp_hash' => Hash::make($otp),
                    'reset_token_hash' => null,
                    'attempts' => 0,
                    'sent_at' => now(),
                    'verified_at' => null,
                    'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
                ]
            );

            if ($resolvedChannel === 'email') {
                Mail::to((string) $user->email)->send(new PasswordResetOtpMail($otp, self::OTP_TTL_MINUTES));
            } else {
                $this->whatsAppChannel->send((string) $user->phone, $otp, self::OTP_TTL_MINUTES);
            }

            return null;
        });

        if ($throttledRemaining !== null) {
            throw new PasswordResetException(
                __('auth.password_reset.throttled', ['seconds' => $throttledRemaining]),
                HttpStatusCode::TOO_MANY_REQUESTS
            );
        }

        return $resolvedChannel;
    }

    public function verifyOtp(
        string $identifier,
        string $otp,
        UserScope $expectedScope,
        string $channel = 'email'
    ): string {
        $normalizedIdentifier = trim($identifier);
        $user = $this->findScopedUser($normalizedIdentifier, $expectedScope, $channel);

        $outcome = DB::transaction(function () use ($user, $otp): array {
            $record = PasswordResetOtp::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $record || ! $record->otp_hash || $record->expires_at->isPast()) {
                return ['error' => 'expired'];
            }

            if ($record->attempts >= self::MAX_ATTEMPTS) {
                return ['error' => 'max_attempts'];
            }

            if (! Hash::check($otp, $record->otp_hash)) {
                $record->increment('attempts');

                if ($record->attempts >= self::MAX_ATTEMPTS) {
                    return ['error' => 'max_attempts'];
                }

                return ['error' => 'invalid_otp'];
            }

            $resetToken = Str::random(64);

            $record->update([
                'otp_hash' => null,
                'reset_token_hash' => Hash::make($resetToken),
                'verified_at' => now(),
                'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
            ]);

            return ['resetToken' => $resetToken];
        });

        if (isset($outcome['error'])) {
            throw new PasswordResetException(
                __('auth.password_reset.'.$outcome['error']),
                HttpStatusCode::BAD_REQUEST
            );
        }

        return $outcome['resetToken'];
    }

    public function resetPassword(
        string $identifier,
        string $resetToken,
        string $newPassword,
        UserScope $expectedScope,
        string $channel = 'email'
    ): void {
        $normalizedIdentifier = trim($identifier);
        $user = $this->findScopedUser($normalizedIdentifier, $expectedScope, $channel);

        DB::transaction(function () use ($user, $resetToken, $newPassword): void {
            $record = PasswordResetOtp::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (
                ! $record
                || ! $record->reset_token_hash
                || ! $record->verified_at
                || $record->expires_at->isPast()
                || ! Hash::check($resetToken, $record->reset_token_hash)
            ) {
                throw new PasswordResetException(
                    __('auth.password_reset.invalid_reset_token'),
                    HttpStatusCode::BAD_REQUEST
                );
            }

            $user->update([
                'password' => $newPassword,
            ]);

            $user->tokens()->delete();

            if (config('session.driver') === 'database' && Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            $record->delete();
        });
    }

    private function findScopedUser(string $identifier, UserScope $expectedScope, string $channel = 'email'): User
    {
        $column = $channel === 'whatsapp' && $expectedScope === UserScope::CLIENT
            ? 'phone'
            : 'email';

        $user = User::query()
            ->where($column, $identifier)
            ->where('role', $expectedScope->value)
            ->first();

        if (! $user) {
            throw new PasswordResetException(
                __('auth.password_reset.user_not_found'),
                HttpStatusCode::NOT_FOUND
            );
        }

        $userActive = $user->user_active instanceof UserActive
            ? $user->user_active
            : (UserActive::tryFrom((int) $user->user_active) ?? UserActive::INACTIVE);

        if ($userActive !== UserActive::ACTIVE) {
            throw new AccountInactiveException($userActive->message());
        }

        return $user;
    }
}
