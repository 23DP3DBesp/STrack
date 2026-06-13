<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\FuelLog;
use App\Models\RecurringCost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CarTrackerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_search_cars_by_brand_or_model(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Car::query()->create([
            'user_id' => $user->id,
            'brand' => 'BMW',
            'model' => 'M3',
            'year' => 2020,
            'engine_volume' => 3.0,
            'license_plate' => 'AAA-111',
        ]);

        Car::query()->create([
            'user_id' => $user->id,
            'brand' => 'Audi',
            'model' => 'RS6',
            'year' => 2021,
            'engine_volume' => 4.0,
            'license_plate' => 'BBB-222',
        ]);

        $response = $this->getJson('/api/cars?search=BM');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.model', 'M3');
    }

    public function test_user_can_create_fuel_log_with_automatic_calculations(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $car = Car::query()->create([
            'user_id' => $user->id,
            'brand' => 'Mazda',
            'model' => 'MX-5',
            'year' => 2019,
            'engine_volume' => 2.0,
            'license_plate' => 'ROAD-5',
        ]);

        FuelLog::query()->create([
            'car_id' => $car->id,
            'date' => '2026-04-01',
            'liters' => 40.00,
            'total_price' => 64.00,
            'price_per_liter' => 1.600,
            'mileage' => 50000,
            'fuel_consumption' => null,
        ]);

        $response = $this->postJson("/api/cars/{$car->id}/fuel-logs", [
            'date' => '2026-04-10',
            'liters' => 35,
            'total_price' => 59.5,
            'mileage' => 50450,
        ]);

        $response->assertCreated()
            ->assertJsonPath('price_per_liter', '1.700')
            ->assertJsonPath('distance_since_previous', 450)
            ->assertJsonPath('fuel_consumption', '7.78');
    }

    public function test_fuel_log_mileage_must_fit_between_neighboring_logs(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $car = Car::query()->create([
            'user_id' => $user->id,
            'brand' => 'Mazda',
            'model' => '3',
            'year' => 2022,
            'engine_volume' => 2.0,
            'license_plate' => 'MID-123',
        ]);

        FuelLog::query()->create([
            'car_id' => $car->id,
            'date' => '2026-04-01',
            'liters' => 40.00,
            'total_price' => 64.00,
            'price_per_liter' => 1.600,
            'mileage' => 50000,
            'fuel_consumption' => null,
        ]);

        FuelLog::query()->create([
            'car_id' => $car->id,
            'date' => '2026-04-20',
            'liters' => 35.00,
            'total_price' => 59.50,
            'price_per_liter' => 1.700,
            'mileage' => 50500,
            'fuel_consumption' => 7.00,
        ]);

        $response = $this->postJson("/api/cars/{$car->id}/fuel-logs", [
            'date' => '2026-04-10',
            'liters' => 20,
            'total_price' => 32,
            'mileage' => 50600,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('mileage');
    }

    public function test_user_can_filter_fuel_logs_by_date_range(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $car = Car::query()->create([
            'user_id' => $user->id,
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2021,
            'engine_volume' => 1.8,
            'license_plate' => 'FUEL-1',
        ]);

        foreach ([
            ['date' => '2026-04-01', 'mileage' => 10000],
            ['date' => '2026-04-12', 'mileage' => 10400],
            ['date' => '2026-04-25', 'mileage' => 10900],
        ] as $index => $item) {
            FuelLog::query()->create([
                'car_id' => $car->id,
                'date' => $item['date'],
                'liters' => 30.00,
                'total_price' => 45.00,
                'price_per_liter' => 1.500,
                'mileage' => $item['mileage'],
                'fuel_consumption' => $index === 0 ? null : 7.50,
            ]);
        }

        $response = $this->getJson("/api/cars/{$car->id}/fuel-logs?date_from=2026-04-10&date_to=2026-04-20");

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.date', '2026-04-12');
    }

    public function test_missing_car_fuel_logs_return_json_not_found(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/cars/999/fuel-logs');

        $response->assertNotFound()
            ->assertJsonPath('message', 'Resource not found.');
    }

    public function test_user_can_filter_repairs_by_date_range(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $car = Car::query()->create([
            'user_id' => $user->id,
            'brand' => 'Toyota',
            'model' => 'GR86',
            'year' => 2023,
            'engine_volume' => 2.4,
            'license_plate' => 'GR-086',
        ]);

        $car->repairs()->create([
            'type' => 'Oil service',
            'cost' => 90,
            'date' => '2026-04-01',
            'mileage' => 10000,
            'description' => null,
        ]);

        $car->repairs()->create([
            'type' => 'Suspension check',
            'cost' => 140,
            'date' => '2026-04-15',
            'mileage' => 10300,
            'description' => null,
        ]);

        $response = $this->getJson("/api/cars/{$car->id}/repairs?date_from=2026-04-10&date_to=2026-04-20");

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.type', 'Suspension check');
    }

    public function test_user_can_update_only_expiry_dates_for_car(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $car = Car::query()->create([
            'user_id' => $user->id,
            'brand' => 'BMW',
            'model' => '730d',
            'year' => 2005,
            'engine_volume' => 3.0,
            'license_plate' => 'KP2133',
        ]);

        $response = $this->putJson("/api/cars/{$car->id}", [
            'insurance_until' => '2026-12-10',
            'inspection_until' => '2026-11-01',
        ]);

        $response->assertOk()
            ->assertJsonPath('insurance_until', '2026-12-10')
            ->assertJsonPath('inspection_until', '2026-11-01')
            ->assertJsonPath('brand', 'BMW')
            ->assertJsonPath('model', '730d');

        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'insurance_until' => '2026-12-10',
            'inspection_until' => '2026-11-01',
            'brand' => 'BMW',
            'model' => '730d',
        ]);
    }

    public function test_recurring_cost_routes_require_authentication(): void
    {
        $user = User::factory()->create();

        $car = Car::query()->create([
            'user_id' => $user->id,
            'brand' => 'Honda',
            'model' => 'S2000',
            'year' => 2008,
            'engine_volume' => 2.0,
            'license_plate' => 'S2K-200',
        ]);

        $response = $this->getJson("/api/cars/{$car->id}/recurring-costs");

        $response->assertUnauthorized();
    }
}
