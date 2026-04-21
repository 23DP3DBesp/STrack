<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesOwnedCar;
use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\FuelLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FuelLogController extends Controller
{
    use ResolvesOwnedCar;

    public function index(Request $request, Car $car): JsonResponse
    {
        $this->ensureOwnedCar($request, $car);

        $logs = $car->fuelLogs()
            ->when($request->filled('date'), fn ($query) => $query->whereDate('date', (string) $request->query('date')))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return response()->json($logs);
    }

    public function store(Request $request, Car $car): JsonResponse
    {
        $this->ensureOwnedCar($request, $car);
        $fuelLog = $car->fuelLogs()->create($this->validatedPayload($request, $car));
        $this->recalculateFuelConsumption($car);

        return response()->json($fuelLog->fresh(), 201);
    }

    public function update(Request $request, FuelLog $fuelLog): JsonResponse
    {
        $this->ensureOwnedCar($request, $fuelLog->car);
        $fuelLog->update($this->validatedPayload($request, $fuelLog->car, $fuelLog));
        $this->recalculateFuelConsumption($fuelLog->car);

        return response()->json($fuelLog->fresh());
    }

    public function destroy(Request $request, FuelLog $fuelLog): JsonResponse
    {
        $car = $fuelLog->car;
        $this->ensureOwnedCar($request, $car);
        $fuelLog->delete();
        $this->recalculateFuelConsumption($car);

        return response()->json(['message' => 'Fuel log deleted.']);
    }

    private function validatedPayload(Request $request, Car $car, ?FuelLog $existing = null): array
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'liters' => ['required', 'numeric', 'gt:0'],
            'total_price' => ['required', 'numeric', 'gte:0'],
            'mileage' => ['required', 'integer', 'min:0'],
        ]);

        $liters = (float) $validated['liters'];
        $totalPrice = (float) $validated['total_price'];
        $mileage = (int) $validated['mileage'];

        $previousLog = $car->fuelLogs()
            ->when($existing, fn ($query) => $query->whereKeyNot($existing->id))
            ->where(function ($query) use ($validated, $existing) {
                $query->whereDate('date', '<', $validated['date'])
                    ->orWhere(function ($sameDate) use ($validated, $existing) {
                        $sameDate->whereDate('date', $validated['date']);
                        if ($existing) {
                            $sameDate->where('id', '<', $existing->id);
                        }
                    });
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();

        if ($previousLog && $mileage <= (int) $previousLog->mileage) {
            throw ValidationException::withMessages([
                'mileage' => ['Mileage must be greater than the previous fuel log for this car.'],
            ]);
        }

        $distance = $previousLog ? $mileage - (int) $previousLog->mileage : null;
        $fuelConsumption = $distance && $distance > 0 ? round(($liters / $distance) * 100, 2) : null;

        return [
            'date' => $validated['date'],
            'liters' => round($liters, 2),
            'total_price' => round($totalPrice, 2),
            'price_per_liter' => round($totalPrice / $liters, 3),
            'mileage' => $mileage,
            'fuel_consumption' => $fuelConsumption,
        ];
    }

    private function recalculateFuelConsumption(Car $car): void
    {
        $previousMileage = null;

        $car->fuelLogs()
            ->getQuery()
            ->where('car_id', $car->id)
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->each(function (FuelLog $fuelLog) use (&$previousMileage) {
                $consumption = null;

                if ($previousMileage !== null && (int) $fuelLog->mileage > $previousMileage) {
                    $distance = (int) $fuelLog->mileage - $previousMileage;
                    $consumption = round(((float) $fuelLog->liters / $distance) * 100, 2);
                }

                $fuelLog->updateQuietly([
                    'fuel_consumption' => $consumption,
                ]);

                $previousMileage = (int) $fuelLog->mileage;
            });
    }
}
