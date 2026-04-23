<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesOwnedCar;
use App\Http\Controllers\Controller;
use App\Models\Car;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarController extends Controller
{
    use ResolvesOwnedCar;

    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $cars = Car::query()
            ->where('user_id', $request->user()->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('brand', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%");
                });
            })
            ->withCount(['fuelLogs', 'repairs', 'mods'])
            ->withSum('fuelLogs as fuel_logs_total_spent', 'total_price')
            ->withSum('repairs as repairs_total_spent', 'cost')
            ->withSum('mods as mods_total_spent', 'cost')
            ->orderBy('brand')
            ->orderBy('model')
            ->get();

        return response()->json($cars);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'brand' => ['required', 'string', 'max:120'],
            'model' => ['required', 'string', 'max:120'],
            'year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'engine_volume' => ['required', 'numeric', 'min:0.1', 'max:20'],
            'license_plate' => ['required', 'string', 'max:30'],
            'insurance_until' => ['nullable', 'date'],
            'inspection_until' => ['nullable', 'date'],
        ]);

        $car = $request->user()->cars()->create($validated);

        return response()->json($car, 201);
    }

    public function show(Request $request, Car $car): JsonResponse
    {
        $this->ensureOwnedCar($request, $car);

        return response()->json(
            $car->loadCount(['fuelLogs', 'repairs', 'mods'])
        );
    }

    public function update(Request $request, Car $car): JsonResponse
    {
        $this->ensureOwnedCar($request, $car);

        $validated = $request->validate([
            'brand' => ['sometimes', 'required', 'string', 'max:120'],
            'model' => ['sometimes', 'required', 'string', 'max:120'],
            'year' => ['sometimes', 'required', 'integer', 'min:1900', 'max:2100'],
            'engine_volume' => ['sometimes', 'required', 'numeric', 'min:0.1', 'max:20'],
            'license_plate' => ['sometimes', 'required', 'string', 'max:30'],
            'insurance_until' => ['nullable', 'date'],
            'inspection_until' => ['nullable', 'date'],
        ]);

        $car->update($validated);

        return response()->json($car->fresh()->loadCount(['fuelLogs', 'repairs', 'mods']));
    }

    public function destroy(Request $request, Car $car): JsonResponse
    {
        $this->ensureOwnedCar($request, $car);
        $car->delete();

        return response()->json(['message' => 'Car deleted.']);
    }
}
