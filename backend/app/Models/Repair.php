<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Repair extends Model
{
    use HasFactory;

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'car_id',
        'type',
        'cost',
        'date',
        'mileage',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'date' => 'date:Y-m-d',
            'mileage' => 'integer',
        ];
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }
}
