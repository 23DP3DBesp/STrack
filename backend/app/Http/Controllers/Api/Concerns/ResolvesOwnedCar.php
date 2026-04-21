<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Car;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait ResolvesOwnedCar
{
    protected function ensureOwnedCar(Request $request, Car $car): void
    {
        if ((int) $car->user_id !== (int) $request->user()->id) {
            throw new HttpException(404, 'Car not found.');
        }
    }
}
