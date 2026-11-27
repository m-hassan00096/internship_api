<?php

namespace App\Http\Resources\V1\Admin;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Category
 */
class AdminCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $nameAr = $this->name['ar'] ?? '';
        $nameEn = $this->name['en'] ?? '';

        return [
            'id' => $this->id,
            'name' => [
                'ar' => $nameAr,
                'en' => $nameEn,
            ],
            'displayName' => $this->localizedName(),
            'slug' => $this->slug,
            'image' => $this->image_path ? Storage::disk('public')->url($this->image_path) : null,
            'isActive' => (bool) $this->is_active,
            'statusLabel' => $this->is_active ? __('admin/cms.active') : __('admin/cms.inactive'),
            'sortOrder' => (int) $this->sort_order,
            'createdAt' => $this->created_at?->format('Y-m-d H:i'),
        ];
    }
}
