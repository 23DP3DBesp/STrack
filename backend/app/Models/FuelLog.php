<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelLog extends Model
{
    use HasFactory;

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'car_id',
        'date',
        'liters',
        'total_price',
        'price_per_liter',
        'mileage',
        'fuel_consumption',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'liters' => 'decimal:2',
            'total_price' => 'decimal:2',
            'price_per_liter' => 'decimal:3',
            'mileage' => 'integer',
            'fuel_consumption' => 'decimal:2',
        ];
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }
}
