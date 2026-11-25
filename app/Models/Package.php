<?php

namespace App\Models;

use App\Enums\PackageStatus;
use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use HasFactory;

    protected $table = 'packages';

    protected $fillable = [
        'showrooms_id',
        'name',
        'sku',
        'description',
        'image_path',
        'price',
        'status',
        'current_step',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'status' => PackageStatus::class,
            'current_step' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function showroom(): BelongsTo
    {
        return $this->belongsTo(Showroom::class, 'showrooms_id');
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'package_rooms', 'packages_id', 'rooms_id')
            ->withPivot(['id', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function packageRooms(): HasMany
    {
        return $this->hasMany(PackageRoom::class, 'packages_id');
    }

    public function promotions(): MorphMany
    {
        return $this->morphMany(Promotion::class, 'promotable');
    }

    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('status', PackageStatus::PUBLISHED);
    }

    #[Scope]
    protected function draft(Builder $query): Builder
    {
        return $query->where('status', PackageStatus::DRAFT);
    }

    #[Scope]
    protected function archived(Builder $query): Builder
    {
        return $query->where('status', PackageStatus::ARCHIVED);
    }

    public function isPublished(): bool
    {
        return $this->status === PackageStatus::PUBLISHED;
    }

    public function isDraft(): bool
    {
        return $this->status === PackageStatus::DRAFT;
    }

    public function isArchived(): bool
    {
        return $this->status === PackageStatus::ARCHIVED;
    }
}
