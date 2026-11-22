<?php

namespace App\Models;

use Database\Factories\PromotionTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromotionType extends Model
{
    /** @use HasFactory<PromotionTypeFactory> */
    use HasFactory;

    protected $table = 'promotion_types';

    protected $fillable = [
        'name',
        'placement',
        'daily_price',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => 'array',
            'daily_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function localizedName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $translations = $this->name ?? [];

        return $translations[$locale] ?? $translations['ar'] ?? $translations['en'] ?? '';
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class, 'promotion_type_id');
    }
}
