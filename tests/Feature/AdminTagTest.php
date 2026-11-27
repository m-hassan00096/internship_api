<?php

namespace Tests\Feature;

use App\Enums\UserScope;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTagTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserScope::ADMIN]);
    }

    public function test_unauthenticated_user_cannot_access_tags(): void
    {
        $this->getJson(route('admin.tags.index'))->assertUnauthorized();
        $this->postJson(route('admin.tags.store'))->assertUnauthorized();
        $this->getJson(route('admin.tags.show', 1))->assertUnauthorized();
        $this->putJson(route('admin.tags.update', 1))->assertUnauthorized();
        $this->patchJson(route('admin.tags.toggle-status', 1))->assertUnauthorized();
    }

    public function test_non_admin_cannot_access_tags(): void
    {
        $vendor = User::factory()->create(['role' => UserScope::VENDOR]);

        $this
            ->actingAs($vendor, 'web')
            ->getJson(route('admin.tags.index'))
            ->assertForbidden();
    }

    public function test_admin_can_list_tags_with_kpis(): void
    {
        Tag::factory()->create([
            'name' => ['ar' => 'مودرن', 'en' => 'Modern'],
            'is_active' => true,
            'sort_order' => 1,
        ]);
        Tag::factory()->create([
            'name' => ['ar' => 'كلاسيك', 'en' => 'Classic'],
            'is_active' => true,
            'sort_order' => 2,
        ]);
        Tag::factory()->create([
            'name' => ['ar' => 'ريفي', 'en' => 'Rustic'],
            'is_active' => false,
            'sort_order' => 3,
        ]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.tags.index'));

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

    public function test_admin_can_filter_tags_by_search_and_status(): void
    {
        Tag::factory()->create([
            'name' => ['ar' => 'بوهيمي', 'en' => 'Bohemian'],
            'is_active' => true,
        ]);
        Tag::factory()->create([
            'name' => ['ar' => 'صناعي', 'en' => 'Industrial'],
            'is_active' => false,
        ]);

        $searchResponse = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.tags.index', ['search' => 'بوهيمي']));

        $searchResponse->assertOk();
        $this->assertCount(1, $searchResponse->json('data.items'));
        $this->assertEquals('بوهيمي', $searchResponse->json('data.items.0.name.ar'));

        $statusResponse = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.tags.index', ['status' => 'inactive']));

        $statusResponse->assertOk();
        $this->assertCount(1, $statusResponse->json('data.items'));
        $this->assertEquals('صناعي', $statusResponse->json('data.items.0.name.ar'));
    }

    public function test_admin_can_store_tag(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->create('style.jpg', 100, 'image/jpeg');

        $payload = [
            'name' => [
                'ar' => 'نيو كلاسيك',
                'en' => 'Neo Classic',
            ],
            'slug' => 'neo-classic',
            'image' => $image,
            'sort_order' => 3,
            'is_active' => true,
        ];

        $response = $this
            ->actingAs($this->admin, 'web')
            ->postJson(route('admin.tags.store'), $payload);

        $response->assertCreated()
            ->assertJsonPath('data.name.ar', 'نيو كلاسيك')
            ->assertJsonPath('data.slug', 'neo-classic')
            ->assertJsonPath('data.isActive', true);

        $this->assertDatabaseHas('tags', [
            'slug' => 'neo-classic',
            'sort_order' => 3,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_tag(): void
    {
        Storage::fake('public');

        $tag = Tag::factory()->create([
            'name' => ['ar' => 'مينيمال قديم', 'en' => 'Old Minimal'],
            'sort_order' => 1,
        ]);

        $newImage = UploadedFile::fake()->create('new.jpg', 100, 'image/jpeg');

        $payload = [
            'name' => [
                'ar' => 'مينيمالست',
                'en' => 'Minimalist',
            ],
            'image' => $newImage,
            'sort_order' => 4,
        ];

        $response = $this
            ->actingAs($this->admin, 'web')
            ->putJson(route('admin.tags.update', $tag->id), $payload);

        $response->assertOk()
            ->assertJsonPath('data.name.ar', 'مينيمالست')
            ->assertJsonPath('data.sortOrder', 4);

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'sort_order' => 4,
        ]);
    }

    public function test_admin_can_toggle_tag_status(): void
    {
        $tag = Tag::factory()->create([
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->patchJson(route('admin.tags.toggle-status', $tag->id));

        $response->assertOk()
            ->assertJsonPath('data.isActive', false);

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'is_active' => false,
        ]);

        // Toggle back
        $responseBack = $this
            ->actingAs($this->admin, 'web')
            ->patchJson(route('admin.tags.toggle-status', $tag->id));

        $responseBack->assertOk()
            ->assertJsonPath('data.isActive', true);

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'is_active' => true,
        ]);
    }
}
