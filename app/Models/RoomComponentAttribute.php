<?php

namespace App\Models;

use Database\Factories\RoomComponentAttributeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomComponentAttribute extends Model
{
    /** @use HasFactory<RoomComponentAttributeFactory> */
    use HasFactory;

    protected $fillable = [
        'room_component_id',
        'name',
        'value',
        'unit',
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

    public function component(): BelongsTo
    {
        return $this->belongsTo(RoomComponent::class, 'room_component_id');
    }
}
