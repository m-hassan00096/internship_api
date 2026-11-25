<?php

namespace Tests\Feature;

use App\Enums\HttpStatusCode;
use App\Enums\UserActive;
use App\Enums\UserScope;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_google_login_registers_new_client_and_returns_token(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'sub' => 'google-sub-1001',
                'email' => 'new.client@example.com',
                'email_verified' => 'true',
                'picture' => 'https://lh3.googleusercontent.com/avatar-1001.png',
                'name' => 'New Client',
            ], 200),
        ]);

        $response = $this->withHeader('Accept-Language', 'ar')
            ->postJson(route('mobile.auth.google'), [
                'idToken' => 'valid-mock-google-id-token',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'تم تسجيل الدخول بنجاح.')
            ->assertJsonPath('data.user.email', 'new.client@example.com')
            ->assertJsonPath('data.user.avatar', 'https://lh3.googleusercontent.com/avatar-1001.png')
            ->assertJsonPath('data.user.role', UserScope::CLIENT->value);

        $this->assertNotEmpty($response->json('data.token'));

        $this->assertDatabaseHas('users', [
            'email' => 'new.client@example.com',
            'google_id' => 'google-sub-1001',
            'role' => UserScope::CLIENT->value,
            'user_active' => UserActive::ACTIVE->value,
            'avatar' => 'https://lh3.googleusercontent.com/avatar-1001.png',
        ]);
    }

    public function test_mobile_google_login_links_existing_client_account_by_email(): void
    {
        $client = User::factory()->create([
            'email' => 'existing.client@example.com',
            'phone' => '01018557045',
            'role' => UserScope::CLIENT,
            'google_id' => null,
            'user_active' => UserActive::ACTIVE,
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'sub' => 'google-sub-2002',
                'email' => 'existing.client@example.com',
                'email_verified' => 'true',
                'picture' => 'https://lh3.googleusercontent.com/avatar-2002.png',
            ], 200),
        ]);

        $response = $this->postJson(route('mobile.auth.google'), [
            'idToken' => 'valid-mock-id-token',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'existing.client@example.com')
            ->assertJsonPath('data.user.avatar', 'https://lh3.googleusercontent.com/avatar-2002.png')
            ->assertJsonPath('data.user.role', UserScope::CLIENT->value);

        $this->assertNotEmpty($response->json('data.token'));

        $this->assertDatabaseHas('users', [
            'id' => $client->id,
            'google_id' => 'google-sub-2002',
            'avatar' => 'https://lh3.googleusercontent.com/avatar-2002.png',
        ]);
    }

    public function test_mobile_google_login_succeeds_for_existing_google_id(): void
    {
        $client = User::factory()->create([
            'email' => 'client.linked@example.com',
            'google_id' => 'google-sub-already-linked',
            'role' => UserScope::CLIENT,
            'user_active' => UserActive::ACTIVE,
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'sub' => 'google-sub-already-linked',
                'email' => 'client.linked@example.com',
                'email_verified' => 'true',
            ], 200),
        ]);

        $response = $this->postJson(route('mobile.auth.google'), [
            'idToken' => 'valid-mock-id-token',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', $client->email)
            ->assertJsonPath('data.user.role', UserScope::CLIENT->value);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_mobile_google_login_fails_when_token_is_invalid(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'error' => 'invalid_token',
                'error_description' => 'Invalid Value',
            ], 400),
        ]);

        $response = $this->withHeader('Accept-Language', 'ar')
            ->postJson(route('mobile.auth.google'), [
                'idToken' => 'invalid-token-here',
            ]);

        $response->assertStatus(HttpStatusCode::UNAUTHORIZED->value)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'رمز التحقق من جوجل غير صالح أو منتهي الصلاحية.');
    }

    public function test_mobile_google_login_rejects_inactive_client(): void
    {
        User::factory()->create([
            'email' => 'inactive.client@example.com',
            'google_id' => 'google-sub-inactive',
            'role' => UserScope::CLIENT,
            'user_active' => UserActive::INACTIVE,
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'sub' => 'google-sub-inactive',
                'email' => 'inactive.client@example.com',
                'email_verified' => 'true',
            ], 200),
        ]);

        $response = $this->withHeader('Accept-Language', 'ar')
            ->postJson(route('mobile.auth.google'), [
                'idToken' => 'token-for-inactive',
            ]);

        $response->assertStatus(HttpStatusCode::FORBIDDEN->value)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'حسابك غير نشط، يرجى التواصل مع الإدارة.');
    }

    public function test_mobile_google_login_rejects_non_client_roles(): void
    {
        User::factory()->create([
            'email' => 'admin.user@example.com',
            'role' => UserScope::ADMIN,
            'user_active' => UserActive::ACTIVE,
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'sub' => 'google-sub-admin-attempt',
                'email' => 'admin.user@example.com',
                'email_verified' => 'true',
            ], 200),
        ]);

        $response = $this->withHeader('Accept-Language', 'ar')
            ->postJson(route('mobile.auth.google'), [
                'idToken' => 'token-admin',
            ]);

        $response->assertStatus(HttpStatusCode::FORBIDDEN->value)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'هذا الحساب غير مسجل كحساب عميل.');
    }

    public function test_mobile_google_login_validates_required_id_token(): void
    {
        $response = $this->postJson(route('mobile.auth.google'), []);

        $response->assertStatus(HttpStatusCode::VALIDATION_ERROR->value)
            ->assertJsonPath('success', false);
    }

    public function test_client_web_google_redirect_returns_authorization_url_in_json(): void
    {
        Config::set('services.google.client_id', 'test-client-id');
        Config::set('services.google.client_secret', 'test-client-secret');
        Config::set('services.google.redirect', 'http://localhost:8000/api/v1/client/auth/google/callback');

        $response = $this->getJson(route('client.auth.google.redirect'));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $url = $response->json('data.url');
        $this->assertStringContainsString('accounts.google.com', $url);
        $this->assertStringContainsString('client_id=test-client-id', $url);
    }

    public function test_client_web_google_redirect_redirects_browser(): void
    {
        Config::set('services.google.client_id', 'test-client-id');
        Config::set('services.google.client_secret', 'test-client-secret');
        Config::set('services.google.redirect', 'http://localhost:8000/api/v1/client/auth/google/callback');

        $response = $this->get(route('client.auth.google.redirect'));

        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
    }

    public function test_client_web_google_callback_authenticates_user_via_web_session(): void
    {
        Config::set('services.google.client_id', 'test-client-id');
        Config::set('services.google.client_secret', 'test-client-secret');
        Config::set('services.google.redirect', 'http://localhost:8000/api/v1/client/auth/google/callback');

        $socialiteUser = Mockery::mock(SocialiteUserContract::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google-web-1002');
        $socialiteUser->shouldReceive('getEmail')->andReturn('web.client@example.com');
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://lh3.googleusercontent.com/web-avatar.png');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->withHeader('Accept-Language', 'ar')
            ->getJson(route('client.auth.google.callback'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'تم تسجيل الدخول بنجاح.')
            ->assertJsonPath('data.user.email', 'web.client@example.com')
            ->assertJsonPath('data.user.scope', 'client');

        $this->assertAuthenticated('web');
        $this->assertEquals('web.client@example.com', Auth::guard('web')->user()->email);

        $this->assertDatabaseHas('users', [
            'email' => 'web.client@example.com',
            'google_id' => 'google-web-1002',
            'role' => UserScope::CLIENT->value,
            'user_active' => UserActive::ACTIVE->value,
        ]);
    }
}
