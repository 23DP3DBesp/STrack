<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\FuelLog;
use App\Models\Mod;
use App\Models\RecurringCost;
use App\Models\Repair;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $currentMonth = now()->format('Y-m');

        $carsCount = Car::query()
            ->where('user_id', $userId)
            ->count();

        $fuelLogs = FuelLog::query()
            ->with('car:id,brand,model,user_id')
            ->whereHas('car', fn ($query) => $query->where('user_id', $userId))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $repairs = Repair::query()
            ->with('car:id,brand,model,user_id')
            ->whereHas('car', fn ($query) => $query->where('user_id', $userId))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $mods = Mod::query()
            ->with('car:id,brand,model,user_id')
            ->whereHas('car', fn ($query) => $query->where('user_id', $userId))
            ->orderByDesc('date_installed')
            ->orderByDesc('id')
            ->get();

        $fuelSpend = round((float) $fuelLogs->sum(fn ($item) => (float) $item->total_price), 2);
        $repairSpend = round((float) $repairs->sum(fn ($item) => (float) $item->cost), 2);
        $modSpend = round((float) $mods->sum(fn ($item) => (float) $item->cost), 2);

        $monthly = [];

        foreach ($fuelLogs as $item) {
            $month = substr((string) $item->date, 0, 7);

            if (!isset($monthly[$month])) {
                $monthly[$month] = [
                    'month' => $month,
                    'fuel' => 0,
                    'repairs' => 0,
                    'mods' => 0,
                    'total' => 0,
                ];
            }

            $monthly[$month]['fuel'] += (float) $item->total_price;
            $monthly[$month]['total'] += (float) $item->total_price;
        }

        foreach ($repairs as $item) {
            $month = substr((string) $item->date, 0, 7);

            if (!isset($monthly[$month])) {
                $monthly[$month] = [
                    'month' => $month,
                    'fuel' => 0,
                    'repairs' => 0,
                    'mods' => 0,
                    'total' => 0,
                ];
            }

            $monthly[$month]['repairs'] += (float) $item->cost;
            $monthly[$month]['total'] += (float) $item->cost;
        }

        foreach ($mods as $item) {
            $month = substr((string) $item->date_installed, 0, 7);

            if (!isset($monthly[$month])) {
                $monthly[$month] = [
                    'month' => $month,
                    'fuel' => 0,
                    'repairs' => 0,
                    'mods' => 0,
                    'total' => 0,
                ];
            }

            $monthly[$month]['mods'] += (float) $item->cost;
            $monthly[$month]['total'] += (float) $item->cost;
        }

        ksort($monthly);

        $fleetMonthlyBreakdown = array_map(function (array $item) {
            return [
                'month' => $item['month'],
                'fuel' => round((float) $item['fuel'], 2),
                'repairs' => round((float) $item['repairs'], 2),
                'mods' => round((float) $item['mods'], 2),
                'total' => round((float) $item['total'], 2),
            ];
        }, array_values($monthly));

        $currentMonthFuel = round((float) $fuelLogs
            ->filter(fn ($item) => substr((string) $item->date, 0, 7) === $currentMonth)
            ->sum(fn ($item) => (float) $item->total_price), 2);

        $currentMonthRepairs = round((float) $repairs
            ->filter(fn ($item) => substr((string) $item->date, 0, 7) === $currentMonth)
            ->sum(fn ($item) => (float) $item->cost), 2);

        $currentMonthMods = round((float) $mods
            ->filter(fn ($item) => substr((string) $item->date_installed, 0, 7) === $currentMonth)
            ->sum(fn ($item) => (float) $item->cost), 2);

        $upcomingCosts = class_exists(RecurringCost::class)
            ? RecurringCost::query()
                ->whereHas('car', fn ($query) => $query->where('user_id', $userId))
                ->whereDate('next_due_date', '>=', now()->toDateString())
                ->orderBy('next_due_date')
                ->limit(5)
                ->get()
            : collect();

        return response()->json([
            'stats' => [
                'cars_total' => $carsCount,
                'fuel_logs_total' => $fuelLogs->count(),
                'repairs_total' => $repairs->count(),
                'mods_total' => $mods->count(),
                'total_spent' => round($fuelSpend + $repairSpend + $modSpend, 2),
            ],
            'fleet_cost_by_category' => [
                'fuel' => $fuelSpend,
                'repairs' => $repairSpend,
                'mods' => $modSpend,
            ],
            'fleet_monthly_breakdown' => $fleetMonthlyBreakdown,
            'current_month' => [
                'fuel_spend' => $currentMonthFuel,
                'repair_spend' => $currentMonthRepairs,
                'mod_spend' => $currentMonthMods,
                'total_spend' => round($currentMonthFuel + $currentMonthRepairs + $currentMonthMods, 2),
            ],
            'recent_fuel_logs' => $fuelLogs->take(5)->values(),
            'recent_repairs' => $repairs->take(5)->values(),
            'recent_mods' => $mods->take(5)->values(),
            'upcoming_costs' => $upcomingCosts,
        ]);
    }
}