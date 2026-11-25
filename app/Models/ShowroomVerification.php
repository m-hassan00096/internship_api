<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShowroomVerification extends Model
{
    use HasFactory;

    protected $table = 'showrooms_verification';

    protected $fillable = [
        'showrooms_id',
        'month',
        'monthly_price',
        'total_amount',
        'status',
        'start_at',
        'end_at',
        'review_by',
        'review_at',
        'reject_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'monthly_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'status' => VerificationStatus::class,
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'review_at' => 'datetime',
        ];
    }

    public function showroom(): BelongsTo
    {
        return $this->belongsTo(Showroom::class, 'showrooms_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'review_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', VerificationStatus::APPROVED)
            ->where('start_at', '<=', now())
            ->where('end_at', '>=', now());
    }
}
