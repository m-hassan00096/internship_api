<?php

namespace App\Models;

use App\Enums\PromotionStatus;
use Database\Factories\PromotionFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Promotion extends Model
{
    /** @use HasFactory<PromotionFactory> */
    use HasFactory;

    protected $table = 'promotions';

    protected $fillable = [
        'showrooms_id',
        'promotable_type',
        'promotable_id',
        'promotion_type_id',
        'days',
        'daily_price',
        'total_amount',
        'status',
        'starts_at',
        'ends_at',
        'created_by',
        'reviewed_by',
        'reviewed_at',
        'reject_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'days' => 'integer',
            'daily_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'status' => PromotionStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function showroom(): BelongsTo
    {
        return $this->belongsTo(Showroom::class, 'showrooms_id');
    }

    public function promotionType(): BelongsTo
    {
        return $this->belongsTo(PromotionType::class, 'promotion_type_id');
    }

    public function type(): BelongsTo
    {
        return $this->promotionType();
    }

    public function promotable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', PromotionStatus::ACTIVE)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }

    #[Scope]
    protected function pending(Builder $query): Builder
    {
        return $query->where('status', PromotionStatus::PENDING);
    }

    public function isActive(): bool
    {
        return $this->status === PromotionStatus::ACTIVE
            && $this->starts_at?->isPast()
            && ! $this->ends_at?->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === PromotionStatus::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === PromotionStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === PromotionStatus::REJECTED;
    }

    public function isExpired(): bool
    {
        return $this->status === PromotionStatus::EXPIRED || ($this->ends_at && $this->ends_at->isPast());
    }
}
