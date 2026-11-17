<?php

namespace Tests\Feature;

use App\Enums\UserActive;
use App\Enums\UserScope;
use App\Mail\PasswordResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Services\V1\Auth\Channels\WhatsAppOtpChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Mockery\MockInterface;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_complete_three_step_email_otp_password_reset_flow(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'email' => 'admin.security@baytfolio.sa',
            'role' => UserScope::ADMIN,
            'password' => 'OldPass!123',
            'user_active' => UserActive::ACTIVE,
        ]);

        $admin->createToken('test-token');
        $this->assertCount(1, $admin->tokens);

        // 1. طلب إرسال الـ OTP
        $forgotResponse = $this->withHeader('Accept-Language', 'ar')
            ->postJson(route('admin.auth.forgot-password'), [
                'email' => 'admin.security@baytfolio.sa',
            ]);

        $forgotResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'تم إرسال رمز التحقق إلى بريدك الإلكتروني.')
            ->assertJsonPath('data', null);

        $capturedOtp = '';
        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$capturedOtp): bool {
            $capturedOtp = $mail->otp;

            return $mail->hasTo('admin.security@baytfolio.sa') && strlen($mail->otp) === 6;
        });

        // 2. التحقق من الـ 6 أرقام والحصول على resetToken
        $verifyResponse = $this->withHeader('Accept-Language', 'ar')
            ->postJson(route('admin.auth.verify-otp'), [
                'email' => 'admin.security@baytfolio.sa',
                'otp' => $capturedOtp,
            ]);

        $verifyResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'تم التحقق من الرمز بنجاح.')
            ->assertJsonStructure(['success', 'message', 'data' => ['resetToken']]);

        $resetToken = (string) $verifyResponse->json('data.resetToken');
        $this->assertNotEmpty($resetToken);

        // التأكد من حرق الـ OTP وعدم قبوله مرة ثانية
        $this->postJson(route('admin.auth.verify-otp'), [
            'email' => 'admin.security@baytfolio.sa',
            'otp' => $capturedOtp,
        ])->assertBadRequest();

        // 3. تعيين كلمة المرور الجديدة
        $resetResponse = $this->withHeader('Accept-Language', 'ar')
            ->postJson(route('admin.auth.reset-password'), [
                'email' => 'admin.security@baytfolio.sa',
                'resetToken' => $resetToken,
                'password' => 'NewAdmin@2026',
                'passwordConfirmation' => 'NewAdmin@2026',
            ]);

        $resetResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'تم تحديث كلمة المرور بنجاح، يمكنك تسجيل الدخول الآن.')
            ->assertJsonPath('data', null);

        $admin->refresh();
        $this->assertTrue(Hash::check('NewAdmin@2026', $admin->password));
        $this->assertCount(0, $admin->tokens);
        $this->assertDatabaseMissing('password_reset_otps', ['user_id' => $admin->id]);

        // تسجيل الدخول بكلمة المرور الجديدة ينجح
        $this->postJson('/api/v1/admin/auth/login', [
            'login' => 'admin.security@baytfolio.sa',
            'password' => 'NewAdmin@2026',
        ])->assertOk();
    }

    public function test_vendor_can_complete_email_otp_password_reset_flow(): void
    {
        Mail::fake();

        $vendor = User::factory()->create([
            'email' => 'vendor.owner@baytfolio.sa',
            'role' => UserScope::VENDOR,
            'password' => 'OldVendor@1',
            'user_active' => UserActive::ACTIVE,
        ]);

        $this->withHeader('Accept-Language', 'ar')
            ->postJson(route('vendor.auth.forgot-password'), [
                'email' => 'vendor.owner@baytfolio.sa',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'تم إرسال رمز التحقق إلى بريدك الإلكتروني.')
            ->assertJsonPath('data', null);

        $capturedOtp = '';
        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$capturedOtp): bool {
            $capturedOtp = $mail->otp;

            return $mail->hasTo('vendor.owner@baytfolio.sa');
        });

        $resetToken = (string) $this->postJson(route('vendor.auth.verify-otp'), [
            'email' => 'vendor.owner@baytfolio.sa',
            'otp' => $capturedOtp,
        ])->assertOk()->json('data.resetToken');

        $this->postJson(route('vendor.auth.reset-password'), [
            'email' => 'vendor.owner@baytfolio.sa',
            'resetToken' => $resetToken,
            'password' => 'VendorPass#99',
            'passwordConfirmation' => 'VendorPass#99',
        ])->assertOk();

        $this->assertTrue(Hash::check('VendorPass#99', $vendor->fresh()->password));
    }

    public function test_mobile_client_receives_otp_via_whatsapp_for_phone_and_email_for_email(): void
    {
        Mail::fake();

        $client = User::factory()->create([
            'email' => 'client.reset@example.com',
            'phone' => '01012345678',
            'role' => UserScope::CLIENT,
            'password' => 'OldClient@1',
            'user_active' => UserActive::ACTIVE,
        ]);

        $client->createToken('mobile-client');

        $capturedOtp = '';
        $this->mock(WhatsAppOtpChannel::class, function (MockInterface $mock) use (&$capturedOtp): void {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (string $phone, string $otp, int $ttl) use (&$capturedOtp): bool {
                    $capturedOtp = $otp;

                    return $phone === '01012345678' && strlen($otp) === 6 && $ttl === 10;
                });
        });

        $this->withHeader('Accept-Language', 'ar')
            ->postJson(route('mobile.auth.forgot-password'), [
                'phone' => '01012345678',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'تم إرسال رمز التحقق إلى رقم الواتساب الخاص بك.')
            ->assertJsonPath('data', null);

        Mail::assertNothingSent();

        $resetToken = (string) $this->postJson(route('mobile.auth.verify-otp'), [
            'phone' => '01012345678',
            'otp' => $capturedOtp,
        ])->assertOk()->json('data.resetToken');

        $this->postJson(route('mobile.auth.reset-password'), [
            'phone' => '01012345678',
            'resetToken' => $resetToken,
            'password' => 'MobileClient!88',
            'passwordConfirmation' => 'MobileClient!88',
        ])->assertOk();

        $client->refresh();
        $this->assertTrue(Hash::check('MobileClient!88', $client->password));
        $this->assertCount(0, $client->tokens);

        // التأكد أن نفس العميل يقدر يستخدم الإيميل من بوابة العميل الويب أيضاً
        $this->withHeader('Accept-Language', 'ar')
            ->postJson(route('client.auth.forgot-password'), [
                'email' => 'client.reset@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'تم إرسال رمز التحقق إلى بريدك الإلكتروني.')
            ->assertJsonPath('data', null);

        Mail::assertSent(PasswordResetOtpMail::class, fn (PasswordResetOtpMail $mail): bool => $mail->hasTo('client.reset@example.com'));
    }

    public function test_whatsapp_channel_sends_meta_template_and_normalizes_egyptian_phone(): void
    {
        Config::set('services.whatsapp.driver', 'meta');
        Config::set('services.whatsapp.base_url', 'https://graph.facebook.com/v21.0');
        Config::set('services.whatsapp.token', 'meta-secret-token');
        Config::set('services.whatsapp.phone_number_id', '987654321');
        Config::set('services.whatsapp.template_name', 'password_reset_otp');
        Config::set('services.whatsapp.language_code', 'ar');

        Http::fake([
            'https://graph.facebook.com/v21.0/987654321/messages' => Http::response(['messages' => [['id' => 'wamid.1']]], 200),
        ]);

        $channel = new WhatsAppOtpChannel;
        $channel->send('01012345678', '654321', 10);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://graph.facebook.com/v21.0/987654321/messages'
                && $request->hasHeader('Authorization', 'Bearer meta-secret-token')
                && $request['to'] === '201012345678'
                && $request['template']['components'][0]['parameters'][0]['text'] === '654321';
        });
    }

    public function test_portals_enforce_scope_isolation_and_email_validation(): void
    {
        User::factory()->create([
            'email' => 'client.only@example.com',
            'phone' => '01099887766',
            'role' => UserScope::CLIENT,
            'user_active' => UserActive::ACTIVE,
        ]);

        // العميل لا يقدر يستخدم بوابة الأدمن أو الفيندر
        $this->postJson(route('admin.auth.forgot-password'), [
            'email' => 'client.only@example.com',
        ])->assertNotFound();

        $this->postJson(route('vendor.auth.forgot-password'), [
            'email' => 'client.only@example.com',
        ])->assertNotFound();

        // بوابات الأدمن والفيندر ترفض رقم التليفون سواء في email أو phone
        $this->postJson(route('admin.auth.forgot-password'), [
            'email' => '01099887766',
        ])->assertUnprocessable();

        $this->postJson(route('admin.auth.forgot-password'), [
            'phone' => '01099887766',
        ])->assertUnprocessable();

        $this->postJson(route('vendor.auth.forgot-password'), [
            'email' => '01099887766',
        ])->assertUnprocessable();

        $this->postJson(route('vendor.auth.forgot-password'), [
            'phone' => '01099887766',
        ])->assertUnprocessable();

        // بوابات العميل والموبايل تفرض اختيار email أو phone فقط وبصيغة سليمة
        $this->postJson(route('client.auth.forgot-password'), [
            'email' => '01099887766',
        ])->assertUnprocessable();

        $this->postJson(route('client.auth.forgot-password'), [
            'email' => 'client.only@example.com',
            'phone' => '01099887766',
        ])->assertUnprocessable();

        $this->postJson(route('mobile.auth.forgot-password'), [])
            ->assertUnprocessable();
    }

    public function test_inactive_and_pending_password_users_cannot_request_otp(): void
    {
        Mail::fake();

        User::factory()->inactive()->create([
            'email' => 'inactive.admin@baytfolio.sa',
            'role' => UserScope::ADMIN,
        ]);

        User::factory()->pendingPassword()->create([
            'email' => 'pending.vendor@baytfolio.sa',
            'role' => UserScope::VENDOR,
        ]);

        $this->withHeader('Accept-Language', 'ar')
            ->postJson(route('admin.auth.forgot-password'), [
                'email' => 'inactive.admin@baytfolio.sa',
            ])
            ->assertForbidden()
            ->assertJsonPath('message', 'حسابك غير نشط، يرجى التواصل مع الإدارة.');

        $this->withHeader('Accept-Language', 'ar')
            ->postJson(route('vendor.auth.forgot-password'), [
                'email' => 'pending.vendor@baytfolio.sa',
            ])
            ->assertForbidden()
            ->assertJsonPath('message', 'يجب تعيين كلمة المرور الجديدة من رابط التفعيل المرسل إلى بريدك الإلكتروني قبل تسجيل الدخول.');

        Mail::assertNothingSent();
        $this->assertDatabaseCount('password_reset_otps', 0);
    }

    public function test_otp_enforces_cooldown_max_attempts_and_expiration(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'email' => 'cooldown.admin@baytfolio.sa',
            'role' => UserScope::ADMIN,
            'user_active' => UserActive::ACTIVE,
        ]);

        $this->postJson(route('admin.auth.forgot-password'), [
            'email' => 'cooldown.admin@baytfolio.sa',
        ])->assertOk();

        // إعادة الإرسال قبل 60 ثانية تترفض بـ 429
        $this->withHeader('Accept-Language', 'ar')
            ->postJson(route('admin.auth.forgot-password'), [
                'email' => 'cooldown.admin@baytfolio.sa',
            ])
            ->assertStatus(429)
            ->assertJsonPath('success', false);

        // بعد 61 ثانية يُسمح بإعادة الإرسال ويصدر كود جديد
        $this->travel(61)->seconds();

        $this->postJson(route('admin.auth.forgot-password'), [
            'email' => 'cooldown.admin@baytfolio.sa',
        ])->assertOk();

        $latestOtp = '';
        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$latestOtp): bool {
            $latestOtp = $mail->otp;

            return true;
        });

        // 5 محاولات خاطئة تقفل الرمز فعلياً
        $wrongOtp = $latestOtp === '000000' ? '111111' : '000000';

        for ($i = 1; $i <= 4; $i++) {
            $this->withHeader('Accept-Language', 'ar')
                ->postJson(route('admin.auth.verify-otp'), [
                    'email' => 'cooldown.admin@baytfolio.sa',
                    'otp' => $wrongOtp,
                ])
                ->assertBadRequest()
                ->assertJsonPath('message', 'رمز التحقق غير صحيح.');
        }

        $this->withHeader('Accept-Language', 'ar')
            ->postJson(route('admin.auth.verify-otp'), [
                'email' => 'cooldown.admin@baytfolio.sa',
                'otp' => $wrongOtp,
            ])
            ->assertBadRequest()
            ->assertJsonPath('message', 'تم تجاوز الحد الأقصى لمحاولات التحقق، يرجى طلب رمز جديد.');

        $this->assertSame(5, PasswordResetOtp::where('user_id', $admin->id)->firstOrFail()->attempts);

        // حتى بالكود الصحيح لا يُقبل بعد تجاوز الحد الأقصى
        $this->postJson(route('admin.auth.verify-otp'), [
            'email' => 'cooldown.admin@baytfolio.sa',
            'otp' => $latestOtp,
        ])->assertBadRequest();

        // بعد مرور 11 دقيقة الكود يعتبر منتهي الصلاحية عند إعادة المحاولة
        $this->travel(65)->seconds();
        $this->postJson(route('admin.auth.forgot-password'), [
            'email' => 'cooldown.admin@baytfolio.sa',
        ])->assertOk();

        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$latestOtp): bool {
            $latestOtp = $mail->otp;

            return true;
        });

        $this->travel(11)->minutes();

        $this->withHeader('Accept-Language', 'ar')
            ->postJson(route('admin.auth.verify-otp'), [
                'email' => 'cooldown.admin@baytfolio.sa',
                'otp' => $latestOtp,
            ])
            ->assertBadRequest()
            ->assertJsonPath('message', 'انتهت صلاحية رمز التحقق أو لم يتم طلبه، يرجى طلب رمز جديد.');
    }

    public function test_reset_password_validates_token_and_figma_password_policy(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'email' => 'policy.admin@baytfolio.sa',
            'role' => UserScope::ADMIN,
            'user_active' => UserActive::ACTIVE,
        ]);

        // محاولة تخطي خطوة OTP بـ resetToken مزيف
        $this->postJson(route('admin.auth.reset-password'), [
            'email' => 'policy.admin@baytfolio.sa',
            'resetToken' => 'fake-reset-token',
            'password' => 'StrongPass!123',
            'passwordConfirmation' => 'StrongPass!123',
        ])->assertBadRequest();

        $this->postJson(route('admin.auth.forgot-password'), [
            'email' => 'policy.admin@baytfolio.sa',
        ])->assertOk();

        $otp = '';
        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$otp): bool {
            $otp = $mail->otp;

            return true;
        });

        $resetToken = (string) $this->postJson(route('admin.auth.verify-otp'), [
            'email' => 'policy.admin@baytfolio.sa',
            'otp' => $otp,
        ])->assertOk()->json('data.resetToken');

        // كلمة مرور بدون رموز
        $this->postJson(route('admin.auth.reset-password'), [
            'email' => 'policy.admin@baytfolio.sa',
            'resetToken' => $resetToken,
            'password' => 'StrongPass123',
            'passwordConfirmation' => 'StrongPass123',
        ])->assertUnprocessable();

        // كلمة مرور بدون أرقام
        $this->postJson(route('admin.auth.reset-password'), [
            'email' => 'policy.admin@baytfolio.sa',
            'resetToken' => $resetToken,
            'password' => 'StrongPass!@#',
            'passwordConfirmation' => 'StrongPass!@#',
        ])->assertUnprocessable();

        // تأكيد كلمة المرور بـ snake_case مرفوض
        $this->postJson(route('admin.auth.reset-password'), [
            'email' => 'policy.admin@baytfolio.sa',
            'resetToken' => $resetToken,
            'password' => 'StrongPass!123',
            'password_confirmation' => 'StrongPass!123',
        ])->assertUnprocessable();

        // تأكيد أن كلمة المرور القديمة لم تتغير
        $this->assertFalse(Hash::check('StrongPass!123', $admin->fresh()->password));
    }
}
