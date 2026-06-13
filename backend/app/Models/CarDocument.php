<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarDocument extends Model
{
    use HasFactory;

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'car_id',
        'title',
        'type',
        'file_url',
        'issued_at',
        'expires_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date:Y-m-d',
            'expires_at' => 'date:Y-m-d',
        ];
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }
}
