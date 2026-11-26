<?php

namespace App\Services\V1\Admin;

use App\Enums\UserActive;
use App\Enums\UserScope;
use App\Http\Resources\V1\Admin\AdminUserListResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AdminUserService
{
    /**
     * Get paginated app clients list with KPIs and filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getUsersList(array $filters = []): array
    {
        $baseQuery = User::where('role', UserScope::CLIENT);

        $total = (clone $baseQuery)->count();
        $active = (clone $baseQuery)->where('user_active', UserActive::ACTIVE)->count();
        $blocked = (clone $baseQuery)->where('user_active', UserActive::BLOCKED)->count();
        $inactive = (clone $baseQuery)->whereIn('user_active', [UserActive::INACTIVE, UserActive::PENDING_PASSWORD])->count();

        $query = User::where('role', UserScope::CLIENT);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== 'all') {
            $status = (string) $filters['status'];
            if ($status === 'active' || $status === (string) UserActive::ACTIVE->value) {
                $query->where('user_active', UserActive::ACTIVE);
            } elseif ($status === 'blocked' || $status === (string) UserActive::BLOCKED->value) {
                $query->where('user_active', UserActive::BLOCKED);
            } elseif ($status === 'inactive' || $status === (string) UserActive::INACTIVE->value) {
                $query->whereIn('user_active', [UserActive::INACTIVE, UserActive::PENDING_PASSWORD]);
            }
        }

        if (! empty($filters['authMethod'])) {
            if ($filters['authMethod'] === 'google') {
                $query->whereNotNull('google_id');
            } elseif ($filters['authMethod'] === 'phone_password') {
                $query->whereNull('google_id')->whereNotNull('phone');
            }
        }

        match ($filters['sortBy'] ?? 'latest') {
            'oldest' => $query->orderBy('created_at', 'asc')->orderBy('id', 'asc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        $perPage = (int) ($filters['perPage'] ?? 10);
        $paginator = $query->paginate($perPage);

        return [
            'kpis' => [
                'total' => $total,
                'active' => $active,
                'blocked' => $blocked,
                'inactive' => $inactive,
            ],
            'items' => AdminUserListResource::collection($paginator->items()),
            'pagination' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /**
     * Get specific client user details.
     */
    public function getUserDetail(int|string $id): User
    {
        return User::where('role', UserScope::CLIENT)->findOrFail($id);
    }

    /**
     * Update user account status (active, blocked, inactive).
     */
    public function updateUserStatus(int|string $id, UserActive|int|string $status): User
    {
        $user = User::where('role', UserScope::CLIENT)->findOrFail($id);

        $statusEnum = $status instanceof UserActive
            ? $status
            : UserActive::from((int) $status);

        $user->update([
            'user_active' => $statusEnum,
        ]);

        if ($statusEnum === UserActive::BLOCKED) {
            $user->tokens()->delete();
        }

        return $user;
    }
}
