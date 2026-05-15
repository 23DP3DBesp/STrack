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
        $period = (string) $request->query('period', 'all');
        $periodStart = $this->periodStartDate($period);
        $periodEnd = $periodStart ? now()->toDateString() : null;

        $carsCount = Car::query()
            ->where('user_id', $userId)
            ->count();

        $fuelLogs = FuelLog::query()
            ->with('car:id,brand,model,user_id')
            ->whereHas('car', fn ($query) => $query->where('user_id', $userId))
            ->when($periodStart, fn ($query) => $query->whereDate('date', '>=', $periodStart))
            ->when($periodEnd, fn ($query) => $query->whereDate('date', '<=', $periodEnd))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $repairs = Repair::query()
            ->with('car:id,brand,model,user_id')
            ->whereHas('car', fn ($query) => $query->where('user_id', $userId))
            ->when($periodStart, fn ($query) => $query->whereDate('date', '>=', $periodStart))
            ->when($periodEnd, fn ($query) => $query->whereDate('date', '<=', $periodEnd))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $mods = Mod::query()
            ->with('car:id,brand,model,user_id')
            ->whereHas('car', fn ($query) => $query->where('user_id', $userId))
            ->when($periodStart, fn ($query) => $query->whereDate('date_installed', '>=', $periodStart))
            ->when($periodEnd, fn ($query) => $query->whereDate('date_installed', '<=', $periodEnd))
            ->orderByDesc('date_installed')
            ->orderByDesc('id')
            ->get();

        $fuelSpend = round((float) $fuelLogs->sum(fn ($item) => (float) $item->total_price), 2);
        $repairSpend = round((float) $repairs->sum(fn ($item) => (float) $item->cost), 2);
        $modSpend = round((float) $mods->sum(fn ($item) => (float) $item->cost), 2);
        $totalSpend = round($fuelSpend + $repairSpend + $modSpend, 2);
        $distanceTracked = $this->distanceTracked($fuelLogs);

        $monthly = [];

        foreach ($fuelLogs as $item) {
            $this->addMonthlyAmount($monthly, substr((string) $item->date, 0, 7), 'fuel', (float) $item->total_price);
        }

        foreach ($repairs as $item) {
            $this->addMonthlyAmount($monthly, substr((string) $item->date, 0, 7), 'repairs', (float) $item->cost);
        }

        foreach ($mods as $item) {
            $this->addMonthlyAmount(
                $monthly,
                substr((string) $item->date_installed, 0, 7),
                'mods',
                (float) $item->cost
            );
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

        $currentMonthFuel = $this->sumCurrentMonth($fuelLogs, $currentMonth, 'date', 'total_price');
        $currentMonthRepairs = $this->sumCurrentMonth($repairs, $currentMonth, 'date', 'cost');
        $currentMonthMods = $this->sumCurrentMonth($mods, $currentMonth, 'date_installed', 'cost');

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
                'total_spent' => $totalSpend,
                'distance_tracked' => $distanceTracked,
                'cost_per_km' => $distanceTracked > 0 ? round($totalSpend / $distanceTracked, 2) : 0,
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

    private function periodStartDate(string $period): ?string
    {
        if ($period === 'all') {
            return null;
        }

        $months = match ($period) {
            '3m' => 3,
            '6m' => 6,
            '12m' => 12,
            default => null,
        };

        return $months ? now()->startOfDay()->subMonthsNoOverflow($months)->toDateString() : null;
    }

    private function distanceTracked(iterable $fuelLogs): int
    {
        $byCar = [];

        foreach ($fuelLogs as $fuelLog) {
            $carId = (int) $fuelLog->car_id;
            $mileage = (int) $fuelLog->mileage;

            if ($mileage <= 0) {
                continue;
            }

            $byCar[$carId] ??= ['min' => $mileage, 'max' => $mileage];
            $byCar[$carId]['min'] = min($byCar[$carId]['min'], $mileage);
            $byCar[$carId]['max'] = max($byCar[$carId]['max'], $mileage);
        }

        return array_reduce(
            $byCar,
            fn (int $sum, array $range) => $sum + max(0, $range['max'] - $range['min']),
            0
        );
    }

    private function addMonthlyAmount(array &$monthly, string $month, string $field, float $amount): void
    {
        $monthly[$month] ??= $this->newMonthlyBucket($month);
        $monthly[$month][$field] += $amount;
        $monthly[$month]['total'] += $amount;
    }

    private function newMonthlyBucket(string $month): array
    {
        return [
            'month' => $month,
            'fuel' => 0,
            'repairs' => 0,
            'mods' => 0,
            'total' => 0,
        ];
    }

    private function sumCurrentMonth(iterable $items, string $currentMonth, string $dateField, string $amountField): float
    {
        $total = 0;

        foreach ($items as $item) {
            if (substr((string) data_get($item, $dateField), 0, 7) === $currentMonth) {
                $total += (float) data_get($item, $amountField);
            }
        }

        return round($total, 2);
    }
}
