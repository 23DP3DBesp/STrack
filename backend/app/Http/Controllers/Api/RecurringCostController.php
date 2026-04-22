<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesOwnedCar;
use App\Models\Car;
use App\Models\RecurringCost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecurringCostController extends Controller
{
    use ResolvesOwnedCar;

    public function index(Request $request, Car $car): JsonResponse
    {
        $this->ensureOwnedCar($request, $car);

        return response()->json(
            $car->recurringCosts()
                ->orderBy('next_due_date')
                ->get()
        );
    }

    public function store(Request $request, Car $car): JsonResponse
    {
        $this->ensureOwnedCar($request, $car);

        $cost = $car->recurringCosts()->create(
            $request->validate([
                'name' => ['required', 'string', 'max:120'],
                'amount' => ['required', 'numeric', 'gte:0'],
                'interval' => ['required', 'in:monthly,yearly'],
                'next_due_date' => ['required', 'date'],
            ])
        );

        return response()->json($cost, 201);
    }

    public function update(Request $request, RecurringCost $recurringCost): JsonResponse
    {
        $this->ensureOwnedCar($request, $recurringCost->car);

        $recurringCost->update(
            $request->validate([
                'name' => ['required', 'string', 'max:120'],
                'amount' => ['required', 'numeric', 'gte:0'],
                'interval' => ['required', 'in:monthly,yearly'],
                'next_due_date' => ['required', 'date'],
            ])
        );

        return response()->json($recurringCost->fresh());
    }

    public function destroy(Request $request, RecurringCost $recurringCost): JsonResponse
    {
        $this->ensureOwnedCar($request, $recurringCost->car);

        $recurringCost->delete();

        return response()->json(['message' => 'Recurring cost deleted']);
    }
}