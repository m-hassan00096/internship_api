<?php

namespace App\Services\V1\Vendor;

use App\Enums\RoomStatus;
use App\Exceptions\RoomActionException;
use App\Exceptions\RoomLimitExceededException;
use App\Exceptions\RoomNotFoundException;
use App\Models\Category;
use App\Models\Room;
use App\Models\RoomComponent;
use App\Models\RoomImage;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RoomService
{
    public function __construct(
        private ShowroomService $showroomService
    ) {}

    /**
     * جلب التصنيفات النشطة للغرف
     *
     * @return Collection<int, Category>
     */
    public function listCategories(): Collection
    {
        return Category::query()
            ->active()
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * جلب قائمة التاجات النشطة المتاحة لربطها بالغرف
     *
     * @return Collection<int, Tag>
     */
    public function listTags(): Collection
    {
        return Tag::query()
            ->active()
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * جلب قائمة غرف المعرض مع الفلترة والترقيم
     *
     * @param  array<string, mixed>  $filters
     */
    public function listRooms(User $user, array $filters = []): LengthAwarePaginator
    {
        $showroom = $this->showroomService->getShowroom($user);

        $query = $showroom->rooms()
            ->with(['category', 'primaryImage'])
            ->withCount(['images', 'components']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['search'])) {
            $term = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', $term)
                    ->orWhere('sku', 'like', $term);
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * جلب تفاصيل غرفة واحدة تابعة لمعرض المستخدم
     */
    public function getRoom(User $user, int $roomId): Room
    {
        $showroom = $this->showroomService->getShowroom($user);

        $room = $showroom->rooms()
            ->with([
                'category',
                'tags',
                'images',
                'primaryImage',
                'components.attributes',
            ])
            ->find($roomId);

        if (! $room) {
            throw new RoomNotFoundException;
        }

        return $room;
    }

    /**
     * إنشاء غرفة جديدة مع التحقق من حد الكوتا في العقد
     *
     * @param  array<string, mixed>  $data
     */
    public function createRoom(User $user, array $data): Room
    {
        $showroom = $this->showroomService->getShowroom($user);

        // التحقق من الحد الأقصى للغرف في العقد الساري
        $contract = $showroom->activeContract;
        if ($contract && $contract->max_rooms > 0) {
            $currentRoomsCount = $showroom->rooms()->count();
            if ($currentRoomsCount >= $contract->max_rooms) {
                throw new RoomLimitExceededException;
            }
        }

        $tagIds = $data['tag_ids'] ?? null;
        unset($data['tag_ids']);

        $data['status'] = RoomStatus::DRAFT;
        $data['current_step'] = $data['current_step'] ?? 1;

        $room = $showroom->rooms()->create($data);

        if (! empty($tagIds)) {
            $room->tags()->sync($tagIds);
        }

        return $room->load(['category', 'primaryImage', 'tags']);
    }

    /**
     * تحديث بيانات الغرفة
     *
     * @param  array<string, mixed>  $data
     */
    public function updateRoom(User $user, int $roomId, array $data): Room
    {
        $room = $this->getRoom($user, $roomId);

        $tagIds = $data['tag_ids'] ?? null;
        unset($data['tag_ids']);

        $room->update($data);

        if ($tagIds !== null) {
            $room->tags()->sync($tagIds);
        }

        return $room->fresh(['category', 'primaryImage', 'images', 'components.attributes', 'tags']);
    }

    /**
     * حذف الغرفة ومرفقاتها
     */
    public function deleteRoom(User $user, int $roomId): bool
    {
        $room = $this->getRoom($user, $roomId);

        // حذف الملفات الفيزيائية إن وجدت
        foreach ($room->images as $image) {
            if ($image->image_path && ! str_starts_with($image->image_path, 'http')) {
                Storage::disk('public')->delete($image->image_path);
            }
        }

        foreach ($room->components as $component) {
            if ($component->image_path && ! str_starts_with($component->image_path, 'http')) {
                Storage::disk('public')->delete($component->image_path);
            }
        }

        return (bool) $room->delete();
    }

    /**
     * تغيير حالة الغرفة (مسودة / نشر / أرشفة)
     */
    public function changeStatus(User $user, int $roomId, RoomStatus $status): Room
    {
        $room = $this->getRoom($user, $roomId);

        if ($status === RoomStatus::PUBLISHED) {
            if ($room->images()->count() === 0) {
                throw new RoomActionException(__('vendor/room.errors.primary_image_required'));
            }

            // التأكد من وجود صورة رئيسية
            if (! $room->primaryImage) {
                $firstImage = $room->images()->first();
                $firstImage?->update(['is_primary' => true]);
            }

            if (! $room->published_at) {
                $room->published_at = now();
            }
        }

        $room->status = $status;
        $room->save();

        return $room->fresh(['category', 'primaryImage', 'images', 'components.attributes']);
    }

    /**
     * رفع صور للغرفة
     *
     * @param  array<int, UploadedFile|string>  $images
     * @return Collection<int, RoomImage>
     */
    public function uploadImages(User $user, int $roomId, array $images, bool $isPrimary = false): Collection
    {
        $room = $this->getRoom($user, $roomId);
        $hasExistingImages = $room->images()->exists();

        $uploadedImages = new Collection;

        DB::transaction(function () use ($room, $images, $isPrimary, $hasExistingImages, &$uploadedImages): void {
            if ($isPrimary) {
                $room->images()->update(['is_primary' => false]);
            }

            foreach ($images as $index => $image) {
                if ($image instanceof UploadedFile) {
                    $path = $image->store('rooms/'.$room->id, 'public');
                } else {
                    $path = (string) $image;
                }

                // إذا كانت أول صورة على الإطلاق، تصبح رئيسية تلقائياً
                $primaryFlag = $isPrimary && $index === 0
                    ? true
                    : (! $hasExistingImages && $index === 0);

                $uploadedImages->push(
                    $room->images()->create([
                        'image_path' => $path,
                        'is_primary' => $primaryFlag,
                        'sort_order' => $room->images()->count() + $index,
                    ])
                );
            }
        });

        return $uploadedImages;
    }

    /**
     * حذف صورة من الغرفة
     */
    public function deleteImage(User $user, int $roomId, int $imageId): bool
    {
        $room = $this->getRoom($user, $roomId);

        $image = $room->images()->find($imageId);
        if (! $image) {
            throw new RoomActionException(__('vendor/room.errors.image_not_found'));
        }

        $wasPrimary = (bool) $image->is_primary;

        if ($image->image_path && ! str_starts_with($image->image_path, 'http')) {
            Storage::disk('public')->delete($image->image_path);
        }

        $image->delete();

        // إذا حُذفت الصورة الرئيسية، نجعل الصورة الأولى التالية هي الرئيسية
        if ($wasPrimary) {
            $nextImage = $room->images()->first();
            $nextImage?->update(['is_primary' => true]);
        }

        return true;
    }

    /**
     * تعيين صورة كصورة رئيسية للغرفة
     */
    public function setPrimaryImage(User $user, int $roomId, int $imageId): RoomImage
    {
        $room = $this->getRoom($user, $roomId);

        $image = $room->images()->find($imageId);
        if (! $image) {
            throw new RoomActionException(__('vendor/room.errors.image_not_found'));
        }

        DB::transaction(function () use ($room, $image): void {
            $room->images()->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
        });

        return $image->fresh();
    }

    /**
     * إضافة مكون للغرفة مع أبعاده ومواصفاته
     *
     * @param  array<string, mixed>  $data
     */
    public function storeComponent(User $user, int $roomId, array $data): RoomComponent
    {
        $room = $this->getRoom($user, $roomId);

        $imagePath = null;
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $imagePath = $data['image']->store('rooms/'.$room->id.'/components', 'public');
        } elseif (isset($data['image_path'])) {
            $imagePath = (string) $data['image_path'];
        }

        return DB::transaction(function () use ($room, $data, $imagePath): RoomComponent {
            $component = $room->components()->create([
                'name' => $data['name'],
                'quantity' => $data['quantity'] ?? 1,
                'image_path' => $imagePath,
                'description' => $data['description'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            if (! empty($data['attributes']) && is_array($data['attributes'])) {
                foreach ($data['attributes'] as $attr) {
                    $component->attributes()->create([
                        'name' => $attr['name'],
                        'value' => $attr['value'],
                        'unit' => $attr['unit'] ?? null,
                        'sort_order' => $attr['sort_order'] ?? 0,
                    ]);
                }
            }

            return $component->load('attributes');
        });
    }

    /**
     * تحديث مكون في الغرفة
     *
     * @param  array<string, mixed>  $data
     */
    public function updateComponent(User $user, int $roomId, int $componentId, array $data): RoomComponent
    {
        $room = $this->getRoom($user, $roomId);

        $component = $room->components()->find($componentId);
        if (! $component) {
            throw new RoomActionException(__('vendor/room.errors.component_not_found'));
        }

        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            if ($component->image_path && ! str_starts_with($component->image_path, 'http')) {
                Storage::disk('public')->delete($component->image_path);
            }
            $data['image_path'] = $data['image']->store('rooms/'.$room->id.'/components', 'public');
        }

        return DB::transaction(function () use ($component, $data): RoomComponent {
            $allowed = ['name', 'quantity', 'image_path', 'description', 'sort_order'];
            $component->update(array_intersect_key($data, array_flip($allowed)));

            if (isset($data['attributes']) && is_array($data['attributes'])) {
                $component->attributes()->delete();
                foreach ($data['attributes'] as $attr) {
                    $component->attributes()->create([
                        'name' => $attr['name'],
                        'value' => $attr['value'],
                        'unit' => $attr['unit'] ?? null,
                        'sort_order' => $attr['sort_order'] ?? 0,
                    ]);
                }
            }

            return $component->fresh('attributes');
        });
    }

    /**
     * حذف مكون من الغرفة
     */
    public function deleteComponent(User $user, int $roomId, int $componentId): bool
    {
        $room = $this->getRoom($user, $roomId);

        $component = $room->components()->find($componentId);
        if (! $component) {
            throw new RoomActionException(__('vendor/room.errors.component_not_found'));
        }

        if ($component->image_path && ! str_starts_with($component->image_path, 'http')) {
            Storage::disk('public')->delete($component->image_path);
        }

        return (bool) $component->delete();
    }
}
