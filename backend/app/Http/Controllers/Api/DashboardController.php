<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\FuelLog;
use App\Models\Mod;
use App\Models\Repair;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        return response()->json([
            'stats' => [
                'cars_total' => Car::query()->where('user_id', $userId)->count(),
                'fuel_logs_total' => FuelLog::query()->whereHas('car', fn ($query) => $query->where('user_id', $userId))->count(),
                'repairs_total' => Repair::query()->whereHas('car', fn ($query) => $query->where('user_id', $userId))->count(),
                'mods_total' => Mod::query()->whereHas('car', fn ($query) => $query->where('user_id', $userId))->count(),
                'total_spent' => round(
                    (float) Repair::query()->whereHas('car', fn ($query) => $query->where('user_id', $userId))->sum('cost')
                    + (float) Mod::query()->whereHas('car', fn ($query) => $query->where('user_id', $userId))->sum('cost')
                    + (float) FuelLog::query()->whereHas('car', fn ($query) => $query->where('user_id', $userId))->sum('total_price'),
                    2
                ),
            ],
            'recent_fuel_logs' => FuelLog::query()
                ->with('car:id,brand,model')
                ->whereHas('car', fn ($query) => $query->where('user_id', $userId))
                ->latest('date')
                ->latest('id')
                ->limit(5)
                ->get(),
            'recent_repairs' => Repair::query()
                ->with('car:id,brand,model')
                ->whereHas('car', fn ($query) => $query->where('user_id', $userId))
                ->latest('date')
                ->latest('id')
                ->limit(5)
                ->get(),
            'recent_mods' => Mod::query()
                ->with('car:id,brand,model')
                ->whereHas('car', fn ($query) => $query->where('user_id', $userId))
                ->latest('date_installed')
                ->latest('id')
                ->limit(5)
                ->get(),
        ]);
    }
}
