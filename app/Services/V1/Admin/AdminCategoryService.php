<?php

namespace App\Services\V1\Admin;

use App\Http\Resources\V1\Admin\AdminCategoryResource;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminCategoryService
{
    /**
     * Get paginated categories list with KPIs and filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getCategoriesList(array $filters = []): array
    {
        $total = Category::count();
        $active = Category::where('is_active', true)->count();
        $inactive = Category::where('is_active', false)->count();

        $query = Category::query();

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
            'items' => AdminCategoryResource::collection($paginator->items()),
            'pagination' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /**
     * Get single category details.
     */
    public function getCategoryDetail(int|string $id): Category
    {
        return Category::findOrFail($id);
    }

    /**
     * Create a new category.
     *
     * @param  array<string, mixed>  $data
     */
    public function createCategory(array $data, ?UploadedFile $image = null): Category
    {
        if (empty($data['slug'])) {
            $sourceText = ! empty($data['name']['en']) ? $data['name']['en'] : ($data['name']['ar'] ?? 'category');
            $data['slug'] = Str::slug($sourceText).'-'.Str::random(5);
        }

        if ($image) {
            $data['image_path'] = $image->store('categories', 'public');
        }

        return Category::create($data);
    }

    /**
     * Update an existing category.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateCategory(int|string $id, array $data, ?UploadedFile $image = null): Category
    {
        $category = Category::findOrFail($id);

        if ($image) {
            if ($category->image_path && Storage::disk('public')->exists($category->image_path)) {
                Storage::disk('public')->delete($category->image_path);
            }
            $data['image_path'] = $image->store('categories', 'public');
        }

        $category->update($data);

        return $category->fresh();
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleStatus(int|string $id): Category
    {
        $category = Category::findOrFail($id);
        $category->update([
            'is_active' => ! $category->is_active,
        ]);

        return $category->fresh();
    }
}
