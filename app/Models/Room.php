<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\RoomAvailabilityStatus;
use App\Enums\RoomStatus;
use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    protected $fillable = [
        'showrooms_id',
        'category_id',
        'name',
        'sku',
        'warranty',
        'description',
        'price',
        'discount_type',
        'discount_value',
        'availability_status',
        'preparation_days',
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
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
            'availability_status' => RoomAvailabilityStatus::class,
            'preparation_days' => 'integer',
            'status' => RoomStatus::class,
            'current_step' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function showroom(): BelongsTo
    {
        return $this->belongsTo(Showroom::class, 'showrooms_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(RoomImage::class, 'rooms_id')->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(RoomImage::class, 'rooms_id')->where('is_primary', true);
    }

    public function components(): HasMany
    {
        return $this->hasMany(RoomComponent::class, 'rooms_id')->orderBy('sort_order');
    }

    public function promotions(): MorphMany
    {
        return $this->morphMany(Promotion::class, 'promotable');
    }

    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('status', RoomStatus::PUBLISHED);
    }

    #[Scope]
    protected function draft(Builder $query): Builder
    {
        return $query->where('status', RoomStatus::DRAFT);
    }

    public function isPublished(): bool
    {
        return $this->status === RoomStatus::PUBLISHED;
    }

    public function hasDiscount(): bool
    {
        return ! is_null($this->discount_type) && ! is_null($this->discount_value) && (float) $this->discount_value > 0;
    }

    public function discountAmount(): float
    {
        if (! $this->hasDiscount()) {
            return 0.0;
        }

        $basePrice = (float) $this->price;
        $value = (float) $this->discount_value;

        if ($this->discount_type === DiscountType::PERCENTAGE) {
            return round(($basePrice * min($value, 100)) / 100, 2);
        }

        return min($value, $basePrice);
    }

    public function finalPrice(): float
    {
        $basePrice = (float) $this->price;

        return max(0.0, round($basePrice - $this->discountAmount(), 2));
    }
}
