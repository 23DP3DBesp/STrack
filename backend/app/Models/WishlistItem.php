<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WishlistItem extends Model
{
    use HasFactory;

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'car_id',
        'name',
        'category',
        'estimated_price',
        'store',
        'url',
        'status',
        'priority',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'estimated_price' => 'decimal:2',
            'priority' => 'integer',
        ];
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }
}
