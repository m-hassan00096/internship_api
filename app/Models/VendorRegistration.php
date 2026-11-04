<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_number',
        'vendor_name',
        'business_type',
        'contact_name',
        'email',
        'phone',
        'has_whatsapp',
        'status',
        'rejection_reason',
        'notes',
        'reviewed_by',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'has_whatsapp' => 'boolean',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
