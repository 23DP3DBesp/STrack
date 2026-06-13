<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesOwnedCar;
use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarDocumentController extends Controller
{
    use ResolvesOwnedCar;

    public function index(Request $request, Car $car): JsonResponse
    {
        $this->ensureOwnedCar($request, $car);

        return response()->json(
            $car->documents()
                ->orderByRaw('expires_at is null')
                ->orderBy('expires_at')
                ->orderByDesc('issued_at')
                ->orderByDesc('id')
                ->get()
        );
    }

    public function store(Request $request, Car $car): JsonResponse
    {
        $this->ensureOwnedCar($request, $car);

        $document = $car->documents()->create($this->validatedPayload($request));

        return response()->json($document, 201);
    }

    public function update(Request $request, CarDocument $document): JsonResponse
    {
        $this->ensureOwnedCar($request, $document->car);

        $document->update($this->validatedPayload($request));

        return response()->json($document->fresh());
    }

    public function destroy(Request $request, CarDocument $document): JsonResponse
    {
        $this->ensureOwnedCar($request, $document->car);
        $document->delete();

        return response()->json(['message' => 'Document deleted.']);
    }

    private function validatedPayload(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'type' => ['required', 'string', 'max:60'],
            'file_url' => ['required', 'url', 'max:1000'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
