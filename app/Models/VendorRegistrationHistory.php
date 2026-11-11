<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorRegistrationHistory extends Model
{
    public const ?string UPDATED_AT = null;

    protected $fillable = [
        'vendor_registration_id',
        'status',
        'notes',
        'changed_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(VendorRegistration::class, 'vendor_registration_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
