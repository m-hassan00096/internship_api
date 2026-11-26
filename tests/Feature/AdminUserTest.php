<?php

namespace Tests\Feature;

use App\Enums\UserActive;
use App\Enums\UserScope;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserScope::ADMIN]);
    }

    public function test_unauthenticated_user_cannot_access_admin_users(): void
    {
        $this->getJson(route('admin.users.index'))->assertUnauthorized();
        $this->getJson(route('admin.users.show', 1))->assertUnauthorized();
        $this->patchJson(route('admin.users.update-status', 1), ['status' => UserActive::BLOCKED->value])->assertUnauthorized();
    }

    public function test_non_admin_cannot_access_admin_users(): void
    {
        $vendor = User::factory()->create(['role' => UserScope::VENDOR]);
        $client = User::factory()->create(['role' => UserScope::CLIENT]);

        $this
            ->actingAs($vendor, 'web')
            ->getJson(route('admin.users.index'))
            ->assertForbidden();

        $this
            ->actingAs($client, 'web')
            ->getJson(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_can_list_client_users_with_kpis(): void
    {
        // 2 Active clients
        User::factory()->create([
            'role' => UserScope::CLIENT,
            'user_active' => UserActive::ACTIVE,
            'name' => 'محمد أحمد',
        ]);
        User::factory()->create([
            'role' => UserScope::CLIENT,
            'user_active' => UserActive::ACTIVE,
            'name' => 'سارة عبدالله',
        ]);

        // 1 Blocked client
        User::factory()->create([
            'role' => UserScope::CLIENT,
            'user_active' => UserActive::BLOCKED,
            'name' => 'كريم حسن',
        ]);

        // 1 Inactive client & 1 Pending Password client
        User::factory()->create([
            'role' => UserScope::CLIENT,
            'user_active' => UserActive::INACTIVE,
            'name' => 'نورا علي',
        ]);
        User::factory()->create([
            'role' => UserScope::CLIENT,
            'user_active' => UserActive::PENDING_PASSWORD,
            'name' => 'سمر الشريف',
        ]);

        // Non-client users (must be excluded from client counts and listing)
        User::factory()->create(['role' => UserScope::VENDOR]);
        User::factory()->create(['role' => UserScope::ADMIN]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.users.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'kpis' => [
                        'total',
                        'active',
                        'blocked',
                        'inactive',
                    ],
                    'items' => [
                        '*' => [
                            'id',
                            'userRef',
                            'name',
                            'phone',
                            'email',
                            'avatar',
                            'authMethod' => [
                                'value',
                                'label',
                            ],
                            'status' => [
                                'value',
                                'name',
                                'label',
                            ],
                            'registeredAt',
                            'registeredAtFormatted',
                            'lastLoginAt',
                        ],
                    ],
                    'pagination' => [
                        'currentPage',
                        'lastPage',
                        'perPage',
                        'total',
                    ],
                ],
            ])
            ->assertJsonPath('data.kpis.total', 5)
            ->assertJsonPath('data.kpis.active', 2)
            ->assertJsonPath('data.kpis.blocked', 1)
            ->assertJsonPath('data.kpis.inactive', 2)
            ->assertJsonPath('data.pagination.total', 5);
    }

    public function test_admin_can_filter_users_by_search(): void
    {
        $targetUser = User::factory()->create([
            'role' => UserScope::CLIENT,
            'name' => 'عمر الشامي',
            'phone' => '01012345678',
            'email' => 'omar@baytfolio.com',
        ]);

        User::factory()->create([
            'role' => UserScope::CLIENT,
            'name' => 'خالد منصور',
            'phone' => '01198765432',
            'email' => 'khaled@baytfolio.com',
        ]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.users.index', ['search' => 'عمر']));

        $response->assertOk();
        $items = $response->json('data.items');
        $this->assertCount(1, $items);
        $this->assertEquals($targetUser->id, $items[0]['id']);
        $this->assertEquals('عمر الشامي', $items[0]['name']);
    }

    public function test_admin_can_filter_users_by_status(): void
    {
        User::factory()->create([
            'role' => UserScope::CLIENT,
            'user_active' => UserActive::ACTIVE,
            'name' => 'نشط الأول',
        ]);
        User::factory()->create([
            'role' => UserScope::CLIENT,
            'user_active' => UserActive::BLOCKED,
            'name' => 'محظور الأول',
        ]);

        $activeResponse = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.users.index', ['status' => 'active']));

        $activeResponse->assertOk();
        $activeItems = $activeResponse->json('data.items');
        $this->assertCount(1, $activeItems);
        $this->assertEquals('نشط الأول', $activeItems[0]['name']);

        $blockedResponse = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.users.index', ['status' => 'blocked']));

        $blockedResponse->assertOk();
        $blockedItems = $blockedResponse->json('data.items');
        $this->assertCount(1, $blockedItems);
        $this->assertEquals('محظور الأول', $blockedItems[0]['name']);
    }

    public function test_admin_can_filter_users_by_auth_method(): void
    {
        $googleUser = User::factory()->create([
            'role' => UserScope::CLIENT,
            'name' => 'مستخدم جوجل',
            'google_id' => 'google-user-id-9988',
        ]);

        $phoneUser = User::factory()->create([
            'role' => UserScope::CLIENT,
            'name' => 'مستخدم هاتف',
            'google_id' => null,
            'phone' => '01511223344',
        ]);

        $googleResponse = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.users.index', ['authMethod' => 'google']));

        $googleResponse->assertOk();
        $googleItems = $googleResponse->json('data.items');
        $this->assertCount(1, $googleItems);
        $this->assertEquals($googleUser->id, $googleItems[0]['id']);

        $phoneResponse = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.users.index', ['authMethod' => 'phone_password']));

        $phoneResponse->assertOk();
        $phoneItems = $phoneResponse->json('data.items');
        $this->assertCount(1, $phoneItems);
        $this->assertEquals($phoneUser->id, $phoneItems[0]['id']);
    }

    public function test_admin_can_view_user_details(): void
    {
        $client = User::factory()->create([
            'role' => UserScope::CLIENT,
            'name' => 'أحمد سمير',
            'phone' => '01011223344',
            'email' => 'ahmed@baytfolio.com',
            'user_active' => UserActive::ACTIVE,
        ]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.users.show', $client->id));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'userRef',
                    'name',
                    'avatar',
                    'phone',
                    'email',
                    'authMethod' => [
                        'value',
                        'label',
                    ],
                    'registeredAt',
                    'status' => [
                        'value',
                        'label',
                        'description',
                        'isBlocked',
                        'canBlock',
                    ],
                ],
            ])
            ->assertJsonPath('data.id', $client->id)
            ->assertJsonPath('data.userRef', sprintf('#USR-%04d', $client->id))
            ->assertJsonPath('data.status.isBlocked', false)
            ->assertJsonPath('data.status.canBlock', true);
    }

    public function test_admin_cannot_view_non_client_user_details(): void
    {
        $vendor = User::factory()->create(['role' => UserScope::VENDOR]);

        $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.users.show', $vendor->id))
            ->assertNotFound();
    }

    public function test_admin_can_block_and_unblock_user(): void
    {
        $client = User::factory()->create([
            'role' => UserScope::CLIENT,
            'user_active' => UserActive::ACTIVE,
        ]);

        $client->createToken('test-device-token');
        $this->assertDatabaseCount('personal_access_tokens', 1);

        // Block the user
        $blockResponse = $this
            ->actingAs($this->admin, 'web')
            ->patchJson(route('admin.users.update-status', $client->id), [
                'status' => UserActive::BLOCKED->value,
            ]);

        $blockResponse->assertOk()
            ->assertJsonPath('data.status.isBlocked', true)
            ->assertJsonPath('data.status.canBlock', false);

        $this->assertDatabaseHas('users', [
            'id' => $client->id,
            'user_active' => UserActive::BLOCKED->value,
        ]);

        // Tokens revoked on block
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Unblock (activate) the user
        $unblockResponse = $this
            ->actingAs($this->admin, 'web')
            ->patchJson(route('admin.users.update-status', $client->id), [
                'status' => UserActive::ACTIVE->value,
            ]);

        $unblockResponse->assertOk()
            ->assertJsonPath('data.status.isBlocked', false)
            ->assertJsonPath('data.status.canBlock', true);

        $this->assertDatabaseHas('users', [
            'id' => $client->id,
            'user_active' => UserActive::ACTIVE->value,
        ]);
    }

    public function test_admin_cannot_update_status_with_invalid_value(): void
    {
        $client = User::factory()->create([
            'role' => UserScope::CLIENT,
            'user_active' => UserActive::ACTIVE,
        ]);

        $this
            ->actingAs($this->admin, 'web')
            ->patchJson(route('admin.users.update-status', $client->id), [
                'status' => 999,
            ])
            ->assertUnprocessable();
    }
}
