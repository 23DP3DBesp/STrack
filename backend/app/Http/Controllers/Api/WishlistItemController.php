<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesOwnedCar;
use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\WishlistItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WishlistItemController extends Controller
{
    use ResolvesOwnedCar;

    private const STATUSES = ['planned', 'ordered', 'installed', 'skipped'];

    public function index(Request $request, Car $car): JsonResponse
    {
        $this->ensureOwnedCar($request, $car);

        return response()->json(
            $car->wishlistItems()
                ->orderByRaw("field(status, 'ordered', 'planned', 'installed', 'skipped')")
                ->orderBy('priority')
                ->orderBy('name')
                ->get()
        );
    }

    public function store(Request $request, Car $car): JsonResponse
    {
        $this->ensureOwnedCar($request, $car);

        $item = $car->wishlistItems()->create($this->validatedPayload($request));

        return response()->json($item, 201);
    }

    public function update(Request $request, WishlistItem $wishlistItem): JsonResponse
    {
        $this->ensureOwnedCar($request, $wishlistItem->car);

        $wishlistItem->update($this->validatedPayload($request));

        return response()->json($wishlistItem->fresh());
    }

    public function destroy(Request $request, WishlistItem $wishlistItem): JsonResponse
    {
        $this->ensureOwnedCar($request, $wishlistItem->car);
        $wishlistItem->delete();

        return response()->json(['message' => 'Wishlist item deleted.']);
    }

    private function validatedPayload(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', 'max:80'],
            'estimated_price' => ['nullable', 'numeric', 'gte:0'],
            'store' => ['nullable', 'string', 'max:120'],
            'url' => ['nullable', 'url', 'max:1000'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'priority' => ['required', 'integer', 'min:1', 'max:3'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
