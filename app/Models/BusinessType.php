<?php

namespace App\Models;

use Database\Factories\BusinessTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessType extends Model
{
    /** @use HasFactory<BusinessTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Restrict the query to active business types only.
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the localized name for the requested or active locale.
     */
    public function localizedName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $translations = $this->name ?? [];

        return $translations[$locale]
            ?? $translations['ar']
            ?? $translations['en']
            ?? '';
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(VendorRegistration::class, 'business_type_id');
    }
}
