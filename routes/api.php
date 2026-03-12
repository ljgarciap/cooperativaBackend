<?php

use App\Http\Controllers\N8nWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/creditos', [N8nWebhookController::class, 'receive']);
Route::get('/creditos', [N8nWebhookController::class, 'index']);
Route::put('/creditos/{id}', [\App\Http\Controllers\CreditoController::class, 'update']);
Route::delete('/creditos/{id}', [\App\Http\Controllers\CreditoController::class, 'destroy']);

Route::post('/conciliaciones', [N8nWebhookController::class, 'receiveReconciliation']);
Route::get('/conciliaciones', [N8nWebhookController::class, 'indexReconciliations']);
Route::get('/conciliaciones/{id}', [N8nWebhookController::class, 'showReconciliation']);
Route::post('/conciliaciones/{id}/run', [N8nWebhookController::class, 'runReconciliation']);

Route::get('/bitacora', [N8nWebhookController::class, 'indexBitacora']);
Route::post('/conciliaciones-batch', [N8nWebhookController::class, 'receiveConciliacionBatch']);

// Proxy routes for n8n to avoid CORS
Route::post('/proxy-n8n/pdf', [N8nWebhookController::class, 'forwardToN8nPdf']);
Route::post('/proxy-n8n/excel', [N8nWebhookController::class, 'forwardToN8nExcel']);
