<?php

namespace App\Models;

use App\Enums\ContractStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShowroomContract extends Model
{
    use HasFactory;

    protected $table = 'showrooms_contract';

    protected $fillable = [
        'showrooms_id',
        'contract_number',
        'started_at',
        'end_at',
        'amount',
        'max_rooms',
        'max_packages',
        'contract_file',
        'created_by',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'end_at' => 'date',
            'amount' => 'decimal:2',
            'max_rooms' => 'integer',
            'max_packages' => 'integer',
            'status' => ContractStatus::class,
        ];
    }

    public function showroom(): BelongsTo
    {
        return $this->belongsTo(Showroom::class, 'showrooms_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ContractStatus::ACTIVE)
            ->where('started_at', '<=', now()->toDateString())
            ->where('end_at', '>=', now()->toDateString());
    }

    public function isExpired(): bool
    {
        return $this->end_at->isPast();
    }
}
