<?php

use App\Http\Controllers\CreditoController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\N8nWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Credits Routes
Route::get('/creditos', [CreditoController::class, 'index']);
Route::post('/creditos/upload', [CreditoController::class, 'upload']);
Route::post('/creditos/{id}/approve', [CreditoController::class, 'approve']);
Route::post('/creditos/{id}/reject', [CreditoController::class, 'reject']);
Route::get('/creditos/{id}/download-pdf', [CreditoController::class, 'downloadPdf']);
Route::put('/creditos/{id}', [CreditoController::class, 'update']);
Route::delete('/creditos/{id}', [CreditoController::class, 'destroy']);


// System Routes
Route::post('/system/reset', [SystemController::class, 'reset']);

// Webhook Routes (from n8n)
Route::post('/webhook/creditos', [N8nWebhookController::class, 'receive']);

Route::post('/conciliaciones', [N8nWebhookController::class, 'receiveReconciliation']);
Route::get('/conciliaciones', [N8nWebhookController::class, 'indexReconciliations']);
Route::get('/conciliaciones/{id}', [N8nWebhookController::class, 'showReconciliation']);
Route::post('/conciliaciones/{id}/run', [N8nWebhookController::class, 'runReconciliation']);

Route::get('/bitacora', [N8nWebhookController::class, 'indexBitacora']);
Route::post('/conciliaciones-batch', [N8nWebhookController::class, 'receiveReconciliationBatch']);

// Proxy routes for n8n to avoid CORS
Route::post('/proxy-n8n/pdf', [N8nWebhookController::class, 'forwardToN8nPdf']);
Route::post('/proxy-n8n/excel', [N8nWebhookController::class, 'forwardToN8nExcel']);
