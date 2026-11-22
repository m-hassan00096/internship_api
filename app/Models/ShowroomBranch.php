<?php

namespace App\Models;

use Database\Factories\ShowroomBranchFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShowroomBranch extends Model
{
    /** @use HasFactory<ShowroomBranchFactory> */
    use HasFactory;

    protected $fillable = [
        'showrooms_id',
        'name',
        'city',
        'address',
        'phone',
        'whatsapp',
        'is_main',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function showroom(): BelongsTo
    {
        return $this->belongsTo(Showroom::class, 'showrooms_id');
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function main(Builder $query): Builder
    {
        return $query->where('is_main', true);
    }
}
