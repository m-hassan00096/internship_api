<?php

namespace App\Models;

use Database\Factories\RoomComponentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomComponent extends Model
{
    /** @use HasFactory<RoomComponentFactory> */
    use HasFactory;

    protected $fillable = [
        'rooms_id',
        'name',
        'quantity',
        'image_path',
        'description',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'rooms_id');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(RoomComponentAttribute::class, 'room_component_id')->orderBy('sort_order');
    }
}
