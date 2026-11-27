<?php

namespace Tests\Feature;

use App\Enums\UserScope;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserScope::ADMIN]);
    }

    public function test_unauthenticated_user_cannot_access_categories(): void
    {
        $this->getJson(route('admin.categories.index'))->assertUnauthorized();
        $this->postJson(route('admin.categories.store'))->assertUnauthorized();
        $this->getJson(route('admin.categories.show', 1))->assertUnauthorized();
        $this->putJson(route('admin.categories.update', 1))->assertUnauthorized();
        $this->patchJson(route('admin.categories.toggle-status', 1))->assertUnauthorized();
    }

    public function test_non_admin_cannot_access_categories(): void
    {
        $vendor = User::factory()->create(['role' => UserScope::VENDOR]);

        $this
            ->actingAs($vendor, 'web')
            ->getJson(route('admin.categories.index'))
            ->assertForbidden();
    }

    public function test_admin_can_list_categories_with_kpis(): void
    {
        Category::factory()->create([
            'name' => ['ar' => 'غرف النوم', 'en' => 'Bedrooms'],
            'is_active' => true,
            'sort_order' => 1,
        ]);
        Category::factory()->create([
            'name' => ['ar' => 'غرف المعيشة', 'en' => 'Living Rooms'],
            'is_active' => true,
            'sort_order' => 2,
        ]);
        Category::factory()->create([
            'name' => ['ar' => 'أثاث خارجي', 'en' => 'Outdoor'],
            'is_active' => false,
            'sort_order' => 3,
        ]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.categories.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'kpis' => [
                        'total',
                        'active',
                        'inactive',
                    ],
                    'items' => [
                        '*' => [
                            'id',
                            'name' => ['ar', 'en'],
                            'displayName',
                            'slug',
                            'image',
                            'isActive',
                            'statusLabel',
                            'sortOrder',
                            'createdAt',
                        ],
                    ],
                    'pagination',
                ],
            ])
            ->assertJsonPath('data.kpis.total', 3)
            ->assertJsonPath('data.kpis.active', 2)
            ->assertJsonPath('data.kpis.inactive', 1);
    }

    public function test_admin_can_filter_categories_by_search_and_status(): void
    {
        Category::factory()->create([
            'name' => ['ar' => 'صالونات ومجالس', 'en' => 'Salons'],
            'is_active' => true,
        ]);
        Category::factory()->create([
            'name' => ['ar' => 'مكاتب منزلية', 'en' => 'Home Offices'],
            'is_active' => false,
        ]);

        $searchResponse = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.categories.index', ['search' => 'صالونات']));

        $searchResponse->assertOk();
        $this->assertCount(1, $searchResponse->json('data.items'));
        $this->assertEquals('صالونات ومجالس', $searchResponse->json('data.items.0.name.ar'));

        $statusResponse = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.categories.index', ['status' => 'inactive']));

        $statusResponse->assertOk();
        $this->assertCount(1, $statusResponse->json('data.items'));
        $this->assertEquals('مكاتب منزلية', $statusResponse->json('data.items.0.name.ar'));
    }

    public function test_admin_can_store_category(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->create('category.jpg', 100, 'image/jpeg');

        $payload = [
            'name' => [
                'ar' => 'غرف السفرة',
                'en' => 'Dining Rooms',
            ],
            'slug' => 'dining-rooms',
            'image' => $image,
            'sort_order' => 3,
            'is_active' => true,
        ];

        $response = $this
            ->actingAs($this->admin, 'web')
            ->postJson(route('admin.categories.store'), $payload);

        $response->assertCreated()
            ->assertJsonPath('data.name.ar', 'غرف السفرة')
            ->assertJsonPath('data.slug', 'dining-rooms')
            ->assertJsonPath('data.isActive', true);

        $this->assertDatabaseHas('categories', [
            'slug' => 'dining-rooms',
            'sort_order' => 3,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_category(): void
    {
        Storage::fake('public');

        $category = Category::factory()->create([
            'name' => ['ar' => 'قديم', 'en' => 'Old'],
            'sort_order' => 1,
        ]);

        $newImage = UploadedFile::fake()->create('new.jpg', 100, 'image/jpeg');

        $payload = [
            'name' => [
                'ar' => 'جديد',
                'en' => 'New',
            ],
            'image' => $newImage,
            'sort_order' => 5,
        ];

        $response = $this
            ->actingAs($this->admin, 'web')
            ->putJson(route('admin.categories.update', $category->id), $payload);

        $response->assertOk()
            ->assertJsonPath('data.name.ar', 'جديد')
            ->assertJsonPath('data.sortOrder', 5);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'sort_order' => 5,
        ]);
    }

    public function test_admin_can_toggle_category_status(): void
    {
        $category = Category::factory()->create([
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->patchJson(route('admin.categories.toggle-status', $category->id));

        $response->assertOk()
            ->assertJsonPath('data.isActive', false);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_active' => false,
        ]);

        // Toggle back
        $responseBack = $this
            ->actingAs($this->admin, 'web')
            ->patchJson(route('admin.categories.toggle-status', $category->id));

        $responseBack->assertOk()
            ->assertJsonPath('data.isActive', true);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_active' => true,
        ]);
    }
}
