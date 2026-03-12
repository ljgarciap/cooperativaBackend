<?php

use App\Http\Controllers\N8nWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/creditos', [N8nWebhookController::class, 'receive']);
Route::get('/creditos', [N8nWebhookController::class, 'index']);

Route::post('/conciliaciones', [N8nWebhookController::class, 'receiveReconciliation']);
Route::get('/conciliaciones', [N8nWebhookController::class, 'indexReconciliations']);
Route::get('/conciliaciones/{id}', [N8nWebhookController::class, 'showReconciliation']);
