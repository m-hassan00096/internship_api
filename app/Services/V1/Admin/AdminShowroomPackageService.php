<?php

namespace App\Services\V1\Admin;

use App\Enums\PackageStatus;
use App\Http\Resources\V1\Admin\AdminShowroomPackageResource;
use App\Models\Package;
use App\Models\Showroom;
use Illuminate\Database\Eloquent\Builder;

class AdminShowroomPackageService
{
    /**
     * Get packages list, quota, and KPIs for a specific showroom.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getShowroomPackages(int|string $showroomId, array $filters = []): array
    {
        $showroom = Showroom::with('activeContract')->findOrFail($showroomId);

        $baseQuery = Package::where('showrooms_id', $showroom->id);

        $total = (clone $baseQuery)->count();
        $published = (clone $baseQuery)->where('status', PackageStatus::PUBLISHED)->count();
        $draft = (clone $baseQuery)->where('status', PackageStatus::DRAFT)->count();
        $archived = (clone $baseQuery)->where('status', PackageStatus::ARCHIVED)->count();
        $pendingReview = (clone $baseQuery)->where('status', PackageStatus::DRAFT)->where('current_step', '>', 1)->count();
        $draftOrArchived = $draft + $archived;

        $query = Package::where('showrooms_id', $showroom->id)
            ->with(['rooms.category', 'rooms.components'])
            ->withCount('rooms');

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== 'all') {
            $status = (string) $filters['status'];
            if ($status === 'published' || $status === (string) PackageStatus::PUBLISHED->value) {
                $query->where('status', PackageStatus::PUBLISHED);
            } elseif ($status === 'pending' || $status === 'pending_review') {
                $query->where('status', PackageStatus::DRAFT)->where('current_step', '>', 1);
            } elseif ($status === 'draft' || $status === (string) PackageStatus::DRAFT->value) {
                $query->where('status', PackageStatus::DRAFT);
            } elseif ($status === 'archived' || $status === (string) PackageStatus::ARCHIVED->value) {
                $query->where('status', PackageStatus::ARCHIVED);
            }
        }

        match ($filters['sortBy'] ?? 'latest') {
            'oldest' => $query->orderBy('created_at', 'asc')->orderBy('id', 'asc'),
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            default => $query->orderByDesc('updated_at')->orderByDesc('id'),
        };

        $perPage = (int) ($filters['perPage'] ?? 10);
        $paginator = $query->paginate($perPage);

        $maxPackages = (int) ($showroom->activeContract?->max_packages ?? 0);

        return [
            'kpis' => [
                'total' => $total,
                'published' => $published,
                'draft' => $draft,
                'archived' => $archived,
                'pendingReview' => $pendingReview,
                'draftOrArchived' => $draftOrArchived,
            ],
            'quota' => [
                'used' => $total,
                'max' => $maxPackages,
                'remaining' => max(0, $maxPackages - $total),
            ],
            'items' => AdminShowroomPackageResource::collection($paginator->items()),
            'pagination' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
