<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageRoom extends Model
{
    protected $table = 'package_rooms';

    protected $fillable = [
        'packages_id',
        'rooms_id',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'packages_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'rooms_id');
    }
}
