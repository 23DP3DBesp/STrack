<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesOwnedCar;
use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Repair;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RepairController extends Controller
{
    use ResolvesOwnedCar;

    public function index(Request $request, Car $car): JsonResponse
    {
        $this->ensureOwnedCar($request, $car);

        $repairs = $car->repairs()
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('date', '>=', (string) $request->query('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('date', '<=', (string) $request->query('date_to')))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return response()->json($repairs);
    }

    public function store(Request $request, Car $car): JsonResponse
    {
        $this->ensureOwnedCar($request, $car);

        $repair = $car->repairs()->create($request->validate([
            'type' => ['required', 'string', 'max:120'],
            'cost' => ['required', 'numeric', 'gte:0'],
            'date' => ['required', 'date'],
            'mileage' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]));

        return response()->json($repair, 201);
    }

    public function update(Request $request, Repair $repair): JsonResponse
    {
        $this->ensureOwnedCar($request, $repair->car);

        $repair->update($request->validate([
            'type' => ['required', 'string', 'max:120'],
            'cost' => ['required', 'numeric', 'gte:0'],
            'date' => ['required', 'date'],
            'mileage' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]));

        return response()->json($repair->fresh());
    }

    public function destroy(Request $request, Repair $repair): JsonResponse
    {
        $this->ensureOwnedCar($request, $repair->car);
        $repair->delete();

        return response()->json(['message' => 'Repair deleted.']);
    }
}
