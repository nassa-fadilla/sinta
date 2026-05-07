<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WaController;
use App\Http\Controllers\Api\SintaProxyController;
use App\Http\Controllers\Api\DashboardStatController;

/*
|--------------------------------------------------------------------------
| API Routes – SINTA
|--------------------------------------------------------------------------
*/

// CHAT API 
Route::post('/chat', [WaController::class, 'store'])->middleware('auth:sanctum');

