<?php

namespace App\Services\V1\Admin;

use App\Http\Resources\V1\Admin\AdminTagResource;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminTagService
{
    /**
     * Get paginated tags list with KPIs and filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getTagsList(array $filters = []): array
    {
        $total = Tag::count();
        $active = Tag::where('is_active', true)->count();
        $inactive = Tag::where('is_active', false)->count();

        $query = Tag::query();

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name->ar', 'like', "%{$search}%")
                    ->orWhere('name->en', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== 'all') {
            $status = (string) $filters['status'];
            if ($status === 'active' || $status === '1') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive' || $status === '0') {
                $query->where('is_active', false);
            }
        }

        match ($filters['sortBy'] ?? 'sort_order_asc') {
            'sort_order_desc' => $query->orderByDesc('sort_order')->orderByDesc('id'),
            'name_asc' => $query->orderBy('name->ar', 'asc'),
            'name_desc' => $query->orderBy('name->ar', 'desc'),
            'latest' => $query->orderByDesc('created_at')->orderByDesc('id'),
            default => $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc'),
        };

        $perPage = (int) ($filters['perPage'] ?? 10);
        $paginator = $query->paginate($perPage);

        return [
            'kpis' => [
                'total' => $total,
                'active' => $active,
                'inactive' => $inactive,
            ],
            'items' => AdminTagResource::collection($paginator->items()),
            'pagination' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /**
     * Get single tag details.
     */
    public function getTagDetail(int|string $id): Tag
    {
        return Tag::findOrFail($id);
    }

    /**
     * Create a new tag.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTag(array $data, ?UploadedFile $image = null): Tag
    {
        if (empty($data['slug'])) {
            $sourceText = ! empty($data['name']['en']) ? $data['name']['en'] : ($data['name']['ar'] ?? 'tag');
            $data['slug'] = Str::slug($sourceText).'-'.Str::random(5);
        }

        if ($image) {
            $data['image_path'] = $image->store('tags', 'public');
        }

        return Tag::create($data);
    }

    /**
     * Update an existing tag.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTag(int|string $id, array $data, ?UploadedFile $image = null): Tag
    {
        $tag = Tag::findOrFail($id);

        if ($image) {
            if ($tag->image_path && Storage::disk('public')->exists($tag->image_path)) {
                Storage::disk('public')->delete($tag->image_path);
            }
            $data['image_path'] = $image->store('tags', 'public');
        }

        $tag->update($data);

        return $tag->fresh();
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleStatus(int|string $id): Tag
    {
        $tag = Tag::findOrFail($id);
        $tag->update([
            'is_active' => ! $tag->is_active,
        ]);

        return $tag->fresh();
    }
}
