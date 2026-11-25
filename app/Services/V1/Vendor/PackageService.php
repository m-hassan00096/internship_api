<?php

namespace App\Services\V1\Vendor;

use App\Enums\PackageStatus;
use App\Exceptions\PackageActionException;
use App\Models\Package;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PackageService
{
    public function __construct(
        private ShowroomService $showroomService
    ) {}

    /**
     * قائمة باقات المعرض مع الفلترة والترقيم
     *
     * @param  array<string, mixed>  $filters
     */
    public function listPackages(User $user, array $filters = []): LengthAwarePaginator
    {
        $showroom = $this->showroomService->getShowroom($user);

        $query = $showroom->packages()
            ->with(['rooms.primaryImage'])
            ->withCount('rooms');

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * جلب باقة محددة مع التحقق من ملكيتها للمعرض
     */
    public function getPackage(User $user, int $id): Package
    {
        $showroom = $this->showroomService->getShowroom($user);

        $package = $showroom->packages()
            ->with(['rooms.primaryImage', 'rooms.components'])
            ->withCount('rooms')
            ->find($id);

        if (! $package) {
            throw new PackageActionException(__('vendor/package.errors.not_found'));
        }

        return $package;
    }

    /**
     * إنشاء باقة جديدة
     *
     * @param  array<string, mixed>  $data
     */
    public function createPackage(User $user, array $data): Package
    {
        $showroom = $this->showroomService->getShowroom($user);

        // التحقق من أن جميع الغرف تابعة لمعرض التاجر
        $roomsData = $data['rooms'] ?? [];
        $roomIds = collect($roomsData)->pluck('id')->unique();

        $validCount = $showroom->rooms()->whereIn('id', $roomIds)->count();
        if ($validCount !== $roomIds->count()) {
            throw new PackageActionException(__('vendor/package.errors.invalid_rooms'));
        }

        $imagePath = null;
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $imagePath = $data['image']->store('packages', 'public');
        }

        $status = isset($data['status'])
            ? ($data['status'] instanceof PackageStatus ? $data['status'] : PackageStatus::from((int) $data['status']))
            : PackageStatus::DRAFT;

        $publishedAt = $status === PackageStatus::PUBLISHED ? now() : null;

        return DB::transaction(function () use ($showroom, $data, $imagePath, $status, $publishedAt, $roomsData) {
            /** @var Package $package */
            $package = $showroom->packages()->create([
                'name' => $data['name'],
                'sku' => $data['sku'] ?? null,
                'description' => $data['description'],
                'image_path' => $imagePath,
                'price' => $data['price'] ?? null,
                'status' => $status,
                'current_step' => $data['current_step'] ?? 1,
                'published_at' => $publishedAt,
            ]);

            $attach = [];
            foreach ($roomsData as $index => $item) {
                $attach[$item['id']] = [
                    'sort_order' => $item['sort_order'] ?? ($index + 1),
                ];
            }

            $package->rooms()->attach($attach);

            return $package->load(['rooms.primaryImage'])->loadCount('rooms');
        });
    }

    /**
     * تعديل بيانات باقة
     *
     * @param  array<string, mixed>  $data
     */
    public function updatePackage(User $user, int $id, array $data): Package
    {
        $package = $this->getPackage($user, $id);
        $showroom = $this->showroomService->getShowroom($user);

        // إذا تم إرسال غرف جديدة، نتحقق من ملكيتها للمعرض
        if (isset($data['rooms'])) {
            $roomsData = $data['rooms'];
            $roomIds = collect($roomsData)->pluck('id')->unique();

            $validCount = $showroom->rooms()->whereIn('id', $roomIds)->count();
            if ($validCount !== $roomIds->count()) {
                throw new PackageActionException(__('vendor/package.errors.invalid_rooms'));
            }
        }

        // معالجة تحديث الصورة
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            if ($package->image_path && Storage::disk('public')->exists($package->image_path)) {
                Storage::disk('public')->delete($package->image_path);
            }
            $data['image_path'] = $data['image']->store('packages', 'public');
        }

        if (isset($data['status'])) {
            $newStatus = $data['status'] instanceof PackageStatus
                ? $data['status']
                : PackageStatus::from((int) $data['status']);

            if ($newStatus === PackageStatus::PUBLISHED && is_null($package->published_at)) {
                $data['published_at'] = now();
            }
            $data['status'] = $newStatus;
        }

        return DB::transaction(function () use ($package, $data) {
            $package->update(collect($data)->except(['rooms', 'image'])->all());

            if (isset($data['rooms'])) {
                $sync = [];
                foreach ($data['rooms'] as $index => $item) {
                    $sync[$item['id']] = [
                        'sort_order' => $item['sort_order'] ?? ($index + 1),
                    ];
                }
                $package->rooms()->sync($sync);
            }

            return $package->fresh(['rooms.primaryImage'])->loadCount('rooms');
        });
    }

    /**
     * حذف باقة
     */
    public function deletePackage(User $user, int $id): void
    {
        $package = $this->getPackage($user, $id);

        if ($package->image_path && Storage::disk('public')->exists($package->image_path)) {
            Storage::disk('public')->delete($package->image_path);
        }

        $package->delete();
    }

    /**
     * تغيير حالة الباقة
     */
    public function changeStatus(User $user, int $id, PackageStatus $status): Package
    {
        $package = $this->getPackage($user, $id);

        if ($status === PackageStatus::PUBLISHED && $package->rooms()->count() === 0) {
            throw new PackageActionException(__('vendor/package.errors.empty_rooms_cannot_publish'));
        }

        $update = ['status' => $status];
        if ($status === PackageStatus::PUBLISHED && is_null($package->published_at)) {
            $update['published_at'] = now();
        }

        $package->update($update);

        return $package->fresh(['rooms.primaryImage'])->loadCount('rooms');
    }
}
