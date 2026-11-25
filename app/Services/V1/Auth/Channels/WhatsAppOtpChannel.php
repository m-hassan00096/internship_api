<?php

namespace App\Services\V1\Auth\Channels;

use App\Enums\HttpStatusCode;
use App\Exceptions\PasswordResetException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppOtpChannel
{
    public function send(string $phone, string $otp, int $expiresInMinutes): void
    {
        $normalizedPhone = $this->normalizePhone($phone);
        $driver = (string) config('services.whatsapp.driver', 'log');

        if (in_array($driver, ['local', 'gateway', 'evolution'], true)) {
            $gatewayUrl = rtrim((string) config('services.whatsapp.gateway_url', 'http://127.0.0.1:8080'), '/');
            $messageText = __('auth.password_reset.whatsapp_otp_message', [
                'otp' => $otp,
                'minutes' => $expiresInMinutes,
            ]);

            $response = Http::acceptJson()
                ->timeout(10)
                ->post("{$gatewayUrl}/send-otp", [
                    'phone' => $normalizedPhone,
                    'message' => $messageText,
                ]);

            if ($response->failed()) {
                Log::error('Local WhatsApp Gateway delivery failed.', [
                    'phone' => $normalizedPhone,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new PasswordResetException(
                    __('auth.password_reset.whatsapp_delivery_failed'),
                    HttpStatusCode::INTERNAL_SERVER_ERROR
                );
            }

            return;
        }

        if ($driver !== 'meta') {
            Log::info('WhatsApp OTP generated for password reset.', [
                'phone' => $normalizedPhone,
                'otp' => $otp,
                'expiresInMinutes' => $expiresInMinutes,
            ]);

            return;
        }

        $token = (string) config('services.whatsapp.token');
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id');
        $baseUrl = rtrim((string) config('services.whatsapp.base_url', 'https://graph.facebook.com/v21.0'), '/');
        $templateName = (string) config('services.whatsapp.template_name', 'password_reset_otp');
        $languageCode = (string) config('services.whatsapp.language_code', 'ar');

        if ($token === '' || $phoneNumberId === '') {
            throw new PasswordResetException(
                __('auth.password_reset.whatsapp_delivery_failed'),
                HttpStatusCode::INTERNAL_SERVER_ERROR
            );
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(10)
            ->post("{$baseUrl}/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $normalizedPhone,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => $languageCode,
                    ],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $otp,
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        if ($response->failed()) {
            Log::error('WhatsApp OTP delivery failed.', [
                'phone' => $normalizedPhone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new PasswordResetException(
                __('auth.password_reset.whatsapp_delivery_failed'),
                HttpStatusCode::INTERNAL_SERVER_ERROR
            );
        }
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (preg_match('/^01[0-9]{9}$/', $digits) === 1) {
            return '2'.$digits;
        }

        return $digits;
    }
}
