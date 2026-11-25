<?php

namespace App\Models;

use App\Enums\ContractStatus;
use App\Enums\ShowroomStatus;
use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Showroom extends Model
{
    use HasFactory;

    protected $table = 'showrooms';

    protected $fillable = [
        'user_id',
        'business_type_id',
        'name',
        'slug',
        'bio',
        'logo',
        'cover_image',
        'phone',
        'whatsapp',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ShowroomStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class, 'business_type_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(ShowroomContract::class, 'showrooms_id')
            ->orderByDesc('started_at');
    }

    public function activeContract(): HasOne
    {
        return $this->hasOne(ShowroomContract::class, 'showrooms_id')
            ->ofMany(
                ['id' => 'max'],
                fn ($query) => $query->where('status', ContractStatus::ACTIVE->value)
                    ->whereDate('started_at', '<=', now())
                    ->whereDate('end_at', '>=', now())
            );
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(ShowroomVerification::class, 'showrooms_id')
            ->orderByDesc('id');
    }

    public function activeVerification(): HasOne
    {
        return $this->hasOne(ShowroomVerification::class, 'showrooms_id')
            ->ofMany(
                ['id' => 'max'],
                fn ($query) => $query->where('status', VerificationStatus::APPROVED->value)
                    ->where('start_at', '<=', now())
                    ->where('end_at', '>=', now())
            );
    }

    public function isVerified(): bool
    {
        return $this->activeVerification()->exists();
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'showrooms_id');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(ShowroomBranch::class, 'showrooms_id')
            ->orderByDesc('is_main')
            ->orderBy('id');
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class, 'showrooms_id');
    }

    public function mainBranch(): HasOne
    {
        return $this->hasOne(ShowroomBranch::class, 'showrooms_id')
            ->where('is_main', true);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'showrooms_id');
    }

    public function directReviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class, 'showrooms_id');
    }
}
