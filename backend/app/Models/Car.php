<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Car extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'brand',
        'model',
        'year',
        'engine_volume',
        'license_plate',
        'insurance_until',
        'inspection_until',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'engine_volume' => 'decimal:1',
            'insurance_until' => 'date:Y-m-d',
            'inspection_until' => 'date:Y-m-d',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fuelLogs(): HasMany
    {
        return $this->hasMany(FuelLog::class);
    }

    public function repairs(): HasMany
    {
        return $this->hasMany(Repair::class);
    }

    public function mods(): HasMany
    {
        return $this->hasMany(Mod::class);
    }
    public function recurringCosts(): HasMany
    {
    return $this->hasMany(\App\Models\RecurringCost::class);
    }
}
