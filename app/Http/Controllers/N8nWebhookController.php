<?php

namespace App\Http\Controllers;

use App\Models\AuxiliarItem;
use App\Models\Conciliacion;
use App\Models\Credito;
use App\Models\ExtractoItem;
use App\Models\HistorialEstado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class N8nWebhookController extends Controller
{
    /**
     * Store credit data from n8n extraction.
     */
    public function receive(Request $request)
    {
        Log::info('N8n Webhook incoming data:', $request->all());

        $request->validate([
            'identificacion' => 'required|string',
            'nombre' => 'required|string',
            'monto' => 'required|numeric',
            'tipo' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $credito = Credito::updateOrCreate(
                ['identificacion' => $request->identificacion],
                [
                    'nombre' => $request->nombre,
                    'monto' => $request->monto,
                    'tipo' => $request->tipo,
                    'estado' => $request->estado ?? 'PENDIENTE_ANALISIS',
                    'observaciones' => $request->observaciones,
                ]
            );

            HistorialEstado::create([
                'credito_id' => $credito->id,
                'estado' => $credito->estado,
                'usuario' => 'N8N_SYSTEM',
                'observaciones' => 'Registro inicial desde n8n',
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Credit data processed successfully',
                'data' => $credito
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing n8n webhook: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process credit data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List all credits for the dashboard.
     */
    public function index()
    {
        return response()->json(Credito::with('historial')->latest()->get());
    }

    /**
     * Store bank reconciliation data from n8n extraction.
     */
    public function receiveReconciliation(Request $request)
    {
        Log::info('N8n Reconciliation incoming data:', $request->all());

        $request->validate([
            'banco' => 'required|string',
            'mes' => 'required|string',
            'anio' => 'required|string',
            'extracto_items' => 'required|array',
            'auxiliar_items' => 'required|array',
        ]);

        try {
            DB::beginTransaction();

            $conciliacion = Conciliacion::updateOrCreate(
                [
                    'banco' => $request->banco,
                    'mes' => $request->mes,
                    'anio' => $request->anio,
                ],
                [
                    'saldo_banco' => $request->saldo_banco ?? 0,
                    'saldo_contable' => $request->saldo_contable ?? 0,
                    'estado' => 'PROCESADO',
                ]
            );

            // Clear old items if re-uploading
            $conciliacion->extractoItems()->delete();
            $conciliacion->auxiliarItems()->delete();

            foreach ($request->extracto_items as $item) {
                $conciliacion->extractoItems()->create($item);
            }

            foreach ($request->auxiliar_items as $item) {
                $conciliacion->auxiliarItems()->create($item);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Reconciliation data processed successfully',
                'data' => $conciliacion->load(['extractoItems', 'auxiliarItems'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing n8n reconciliation: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process reconciliation data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List all reconciliations.
     */
    public function indexReconciliations()
    {
        return response()->json(Conciliacion::latest()->get());
    }

    /**
     * Get details of a single reconciliation.
     */
    public function showReconciliation($id)
    {
        return response()->json(Conciliacion::with(['extractoItems', 'auxiliarItems'])->findOrFail($id));
    }
}
