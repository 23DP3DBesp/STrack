<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\FuelLog;
use App\Models\Mod;
use App\Models\Repair;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['login' => 'demo'],
            ['password' => 'Password123!']
        );

        $car = Car::query()->firstOrCreate(
            ['user_id' => $user->id, 'license_plate' => 'TRK-2026'],
            [
                'brand' => 'Subaru',
                'model' => 'WRX',
                'year' => 2018,
                'engine_volume' => 2.0,
                'insurance_until' => '2026-10-15',
                'inspection_until' => '2026-08-01',
            ]
        );

        FuelLog::query()->firstOrCreate(
            ['car_id' => $car->id, 'date' => '2026-04-01'],
            [
                'liters' => 46.50,
                'total_price' => 78.12,
                'price_per_liter' => 1.680,
                'mileage' => 120000,
                'fuel_consumption' => null,
            ]
        );

        Repair::query()->firstOrCreate(
            ['car_id' => $car->id, 'type' => 'Brake service'],
            [
                'cost' => 245.00,
                'date' => '2026-04-05',
                'mileage' => 120320,
                'description' => 'Front pads and fluid refresh.',
            ]
        );

        Mod::query()->firstOrCreate(
            ['car_id' => $car->id, 'name' => 'Cat-back exhaust'],
            [
                'cost' => 690.00,
                'date_installed' => '2026-04-10',
                'performance_impact' => 'Sharper throttle response and a deeper exhaust note.',
            ]
        );
    }
}
