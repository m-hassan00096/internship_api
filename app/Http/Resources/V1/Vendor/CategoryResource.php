<?php

namespace App\Http\Resources\V1\Vendor;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Category
 */
class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->localizedName(),
            'slug' => $this->slug,
            'image' => $this->image_path ? Storage::disk('public')->url($this->image_path) : null,
            'isActive' => (bool) $this->is_active,
            'sortOrder' => (int) $this->sort_order,
        ];
    }
}
