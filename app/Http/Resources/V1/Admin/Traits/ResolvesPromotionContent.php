<?php

namespace App\Http\Resources\V1\Admin\Traits;

use App\Models\Package;
use App\Models\Room;
use App\Models\Showroom;
use Illuminate\Support\Facades\Storage;

trait ResolvesPromotionContent
{
    /**
     * @return array{name: string, sku: ?string, image: ?string}
     */
    protected function resolvePromotableContent(): array
    {
        $promotable = $this->promotable;

        if ($promotable instanceof Room) {
            $primaryImage = $promotable->primaryImage;
            $imagePath = $primaryImage?->image_path ?? $promotable->images->first()?->image_path;

            return [
                'name' => $promotable->name,
                'sku' => $promotable->sku ? 'SKU: '.$promotable->sku : null,
                'skuRaw' => $promotable->sku,
                'image' => $this->formatImageUrl($imagePath),
            ];
        }

        if ($promotable instanceof Package) {
            return [
                'name' => $promotable->name,
                'sku' => $promotable->sku ? 'SKU: '.$promotable->sku : null,
                'skuRaw' => $promotable->sku,
                'image' => $this->formatImageUrl($promotable->image_path),
            ];
        }

        if ($promotable instanceof Showroom) {
            return [
                'name' => $promotable->name,
                'sku' => null,
                'skuRaw' => null,
                'image' => $this->formatImageUrl($promotable->logo),
            ];
        }

        $fallbackName = $this->promotionType?->localizedName() ?? $this->type?->localizedName() ?? 'Promotion Content';

        return [
            'name' => $fallbackName,
            'sku' => null,
            'skuRaw' => null,
            'image' => null,
        ];
    }

    protected function formatImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::url($path);
    }

    protected function formatDays(int $days): string
    {
        $locale = app()->getLocale();

        if ($locale === 'en') {
            return $days === 1 ? '1 Day' : "{$days} Days";
        }

        if ($days === 1) {
            return 'يوم واحد';
        }
        if ($days === 2) {
            return 'يومان';
        }
        if ($days >= 3 && $days <= 10) {
            return "{$days} أيام";
        }

        return "{$days} يوماً";
    }

    protected function resolveShowroomInitials(?string $name): string
    {
        if (! $name) {
            return 'SH';
        }

        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($words)) {
            return 'SH';
        }

        if (count($words) === 1) {
            return mb_substr($words[0], 0, 2);
        }

        return mb_substr($words[0], 0, 1).mb_substr($words[1], 0, 1);
    }
}
