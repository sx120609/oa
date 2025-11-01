<?php

use App\Http\Controllers\DeviceLifecycleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('devices')->group(function () {
    Route::post('purchase', [DeviceLifecycleController::class, 'purchase']);
    Route::post('inbound', [DeviceLifecycleController::class, 'inbound']);
    Route::post('assign', [DeviceLifecycleController::class, 'assign']);
    Route::post('repair', [DeviceLifecycleController::class, 'repair']);
    Route::post('scrap', [DeviceLifecycleController::class, 'scrap']);
});
