<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Database\Factories\ReviewReplyFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewReply extends Model
{
    /** @use HasFactory<ReviewReplyFactory> */
    use HasFactory;

    protected $table = 'review_replies';

    protected $fillable = [
        'reviews_id',
        'user_id',
        'reply',
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
            'status' => ReviewStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class, 'reviews_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
}
