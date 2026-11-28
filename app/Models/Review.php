<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    protected $table = 'reviews';

    protected $fillable = [
        'user_id',
        'showrooms_id',
        'reviewable_type',
        'reviewable_id',
        'rating',
        'comment',
        'status',
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
            'rating' => 'integer',
            'status' => ReviewStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function showroom(): BelongsTo
    {
        return $this->belongsTo(Showroom::class, 'showrooms_id');
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reply(): HasOne
    {
        return $this->hasOne(ReviewReply::class, 'reviews_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    #[Scope]
    protected function approved(Builder $query): Builder
    {
        return $query->where('status', ReviewStatus::APPROVED);
    }

    #[Scope]
    protected function pending(Builder $query): Builder
    {
        return $query->where('status', ReviewStatus::PENDING);
    }

    #[Scope]
    protected function rejected(Builder $query): Builder
    {
        return $query->where('status', ReviewStatus::REJECTED);
    }

    #[Scope]
    protected function hidden(Builder $query): Builder
    {
        return $query->where('status', ReviewStatus::HIDDEN);
    }

    public function isApproved(): bool
    {
        return $this->status === ReviewStatus::APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === ReviewStatus::PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === ReviewStatus::REJECTED;
    }

    public function isHidden(): bool
    {
        return $this->status === ReviewStatus::HIDDEN;
    }
}
