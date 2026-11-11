<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_number',
        'vendor_name',
        'business_type_id',
        'contact_name',
        'email',
        'phone',
        'has_whatsapp',
        'status',
        'user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'has_whatsapp' => 'boolean',
        ];
    }

    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class, 'business_type_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(VendorRegistrationHistory::class, 'vendor_registration_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
