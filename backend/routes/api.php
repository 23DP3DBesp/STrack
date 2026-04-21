<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FuelLogController;
use App\Http\Controllers\Api\ModController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RepairController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:login');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
    Route::delete('/profile', [ProfileController::class, 'destroy']);

    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

    Route::get('/cars', [CarController::class, 'index']);
    Route::post('/cars', [CarController::class, 'store']);
    Route::get('/cars/{car}', [CarController::class, 'show']);
    Route::put('/cars/{car}', [CarController::class, 'update']);
    Route::delete('/cars/{car}', [CarController::class, 'destroy']);

    Route::get('/cars/{car}/fuel-logs', [FuelLogController::class, 'index']);
    Route::post('/cars/{car}/fuel-logs', [FuelLogController::class, 'store']);
    Route::put('/fuel-logs/{fuelLog}', [FuelLogController::class, 'update']);
    Route::delete('/fuel-logs/{fuelLog}', [FuelLogController::class, 'destroy']);

    Route::get('/cars/{car}/repairs', [RepairController::class, 'index']);
    Route::post('/cars/{car}/repairs', [RepairController::class, 'store']);
    Route::put('/repairs/{repair}', [RepairController::class, 'update']);
    Route::delete('/repairs/{repair}', [RepairController::class, 'destroy']);

    Route::get('/cars/{car}/mods', [ModController::class, 'index']);
    Route::post('/cars/{car}/mods', [ModController::class, 'store']);
    Route::put('/mods/{mod}', [ModController::class, 'update']);
    Route::delete('/mods/{mod}', [ModController::class, 'destroy']);
});
