<?php

namespace App\Services\V1\Admin;

use App\Enums\RoomStatus;
use App\Http\Resources\V1\Admin\AdminShowroomRoomResource;
use App\Models\Room;
use App\Models\Showroom;
use Illuminate\Database\Eloquent\Builder;

class AdminShowroomRoomService
{
    /**
     * Get rooms list, quota, and KPIs for a specific showroom.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getShowroomRooms(int|string $showroomId, array $filters = []): array
    {
        $showroom = Showroom::with('activeContract')->findOrFail($showroomId);

        $baseQuery = Room::where('showrooms_id', $showroom->id);

        $total = (clone $baseQuery)->count();
        $published = (clone $baseQuery)->where('status', RoomStatus::PUBLISHED)->count();
        $draft = (clone $baseQuery)->where('status', RoomStatus::DRAFT)->count();
        $archived = (clone $baseQuery)->where('status', RoomStatus::ARCHIVED)->count();
        $pendingReview = (clone $baseQuery)->where('status', RoomStatus::DRAFT)->where('current_step', '>', 1)->count();
        $draftOrArchived = $draft + $archived;

        $query = Room::where('showrooms_id', $showroom->id)
            ->with(['category', 'primaryImage', 'images']);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== 'all') {
            $status = (string) $filters['status'];
            if ($status === 'published' || $status === (string) RoomStatus::PUBLISHED->value) {
                $query->where('status', RoomStatus::PUBLISHED);
            } elseif ($status === 'pending' || $status === 'pending_review') {
                $query->where('status', RoomStatus::DRAFT)->where('current_step', '>', 1);
            } elseif ($status === 'draft' || $status === (string) RoomStatus::DRAFT->value) {
                $query->where('status', RoomStatus::DRAFT);
            } elseif ($status === 'archived' || $status === (string) RoomStatus::ARCHIVED->value) {
                $query->where('status', RoomStatus::ARCHIVED);
            }
        }

        if (! empty($filters['categoryId'])) {
            $query->where('category_id', $filters['categoryId']);
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

        $maxRooms = (int) ($showroom->activeContract?->max_rooms ?? 0);

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
                'max' => $maxRooms,
                'remaining' => max(0, $maxRooms - $total),
            ],
            'items' => AdminShowroomRoomResource::collection($paginator->items()),
            'pagination' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
