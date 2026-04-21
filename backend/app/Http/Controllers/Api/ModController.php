<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesOwnedCar;
use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Mod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModController extends Controller
{
    use ResolvesOwnedCar;

    public function index(Request $request, Car $car): JsonResponse
    {
        $this->ensureOwnedCar($request, $car);

        return response()->json(
            $car->mods()
                ->orderByDesc('date_installed')
                ->orderByDesc('id')
                ->get()
        );
    }

    public function store(Request $request, Car $car): JsonResponse
    {
        $this->ensureOwnedCar($request, $car);

        $mod = $car->mods()->create($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'cost' => ['required', 'numeric', 'gte:0'],
            'date_installed' => ['required', 'date'],
            'performance_impact' => ['required', 'string', 'max:500'],
        ]));

        return response()->json($mod, 201);
    }

    public function update(Request $request, Mod $mod): JsonResponse
    {
        $this->ensureOwnedCar($request, $mod->car);

        $mod->update($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'cost' => ['required', 'numeric', 'gte:0'],
            'date_installed' => ['required', 'date'],
            'performance_impact' => ['required', 'string', 'max:500'],
        ]));

        return response()->json($mod->fresh());
    }

    public function destroy(Request $request, Mod $mod): JsonResponse
    {
        $this->ensureOwnedCar($request, $mod->car);
        $mod->delete();

        return response()->json(['message' => 'Mod deleted.']);
    }
}
