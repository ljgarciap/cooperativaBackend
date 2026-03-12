<?php

namespace App\Http\Controllers;

use App\Models\AuxiliarItem;
use App\Models\Bitacora;
use App\Models\Conciliacion;
use App\Models\Credito;
use App\Models\ExtractoItem;
use App\Models\HistorialEstado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class N8nWebhookController extends Controller
{
    /**
     * Store credit data (General Entry Point).
     */
    public function receive(Request $request)
    {
        Log::info('N8n Webhook direct incoming data:', $request->all());
        
        $result = $this->storeCreditRecord($request->all());

        if (isset($result['status']) && $result['status'] === 'error') {
            return response()->json($result, 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Credit data processed successfully',
            'data' => $result
        ], 201);
    }

    /**
     * Helper to store credit record in database.
     */
    private function storeCreditRecord(array $data)
    {
        Log::info('Attempting to store record with data:', $data);

        $identificacion = $data['identificacion'] ?? $data['Identificación'] ?? null;
        $nombre = $data['nombre'] ?? $data['Nombre'] ?? null;
        $monto = $data['monto'] ?? $data['Monto'] ?? 0;
        $tipo = $data['tipo'] ?? $data['Tipo'] ?? 'DESCONOCIDO';

        if (!$identificacion || !$nombre) {
            Log::warning('Validation failed in storeCreditRecord: missing identification or nombre', ['data' => $data]);
            return [
                'status' => 'error',
                'message' => 'The identificacion and nombre fields are required.',
                'received' => $data
            ];
        }

        try {
            DB::beginTransaction();

            Bitacora::create([
                'nombre_archivo' => $data['nombre_archivo'] ?? 'extracccion_ai.pdf',
                'tipo_archivo' => 'PDF',
                'proceso' => 'CREDITOS',
                'estado' => 'PROCESADO',
                'detalles' => 'Extracción de crédito procesada para: ' . $nombre
            ]);

            $credito = Credito::create([
                'identificacion' => $identificacion,
                'nombre' => $nombre,
                'monto' => $monto,
                'tipo' => $tipo,
                'estado' => $data['estado'] ?? 'PENDIENTE_ANALISIS',
                'observaciones' => $data['observaciones'] ?? 'Extraído via Proxy/n8n',
                'url_archivo' => $data['url_archivo'] ?? null,
            ]);

            HistorialEstado::create([
                'credito_id' => $credito->id,
                'estado' => $credito->estado,
                'usuario' => 'N8N_SYSTEM',
                'observaciones' => 'Registro procesado desde IA',
            ]);

            DB::commit();
            Log::info('Record stored successfully with ID: ' . $credito->id);
            return $credito;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing credit record: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to store credit data',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * List audit log (Bitácora).
     */
    public function indexBitacora()
    {
        return response()->json(Bitacora::latest()->get());
    }

    /**
     * List all credits for the dashboard.
     */
    public function index()
    {
        return response()->json(Credito::with('historial')->latest()->get());
    }

    /**
     * Store bank reconciliation data (Full object from n8n).
     */
    public function receiveReconciliation(Request $request)
    {
        try {
            DB::beginTransaction();

            $conciliacion = Conciliacion::updateOrCreate(
                [
                    'banco' => $request->banco,
                    'mes' => $request->mes,
                    'anio' => $request->anio,
                ],
                ['estado' => 'PROCESANDO']
            );

            // Clear previous items to avoid duplicates on re-upload
            $conciliacion->extractoItems()->delete();
            $conciliacion->auxiliarItems()->delete();

            if ($request->has('extracto_items')) {
                foreach ($request->extracto_items as $item) {
                    $conciliacion->extractoItems()->create($item);
                }
            }

            if ($request->has('auxiliar_items')) {
                foreach ($request->auxiliar_items as $item) {
                    $conciliacion->auxiliarItems()->create($item);
                }
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
     * Receive batched rows from n8n.
     */
    public function receiveConciliacionBatch(Request $request)
    {
        Log::info('Conciliacion Batch Incoming:', $request->all());
        $data = $request->all();
        // If it's a batch of items, wrap it
        $items = isset($data[0]) ? $data : [$data];

        try {
            DB::beginTransaction();

            $firstItem = $items[0] ?? [];
            if (empty($firstItem)) return response()->json(['status' => 'success', 'message' => 'Empty batch']);

            $banco = $firstItem['banco'] ?? 'BANCO_DEMO';
            $mes = $firstItem['mes'] ?? 'ACTUAL';
            $anio = $firstItem['anio'] ?? date('Y');
            $fuente = strtoupper($firstItem['fuente'] ?? 'EXTRACTO');

            $bitacora = Bitacora::create([
                'nombre_archivo' => $firstItem['nombre_archivo'] ?? 'excel_batch.xlsx',
                'tipo_archivo' => 'EXCEL',
                'proceso' => 'CONCILIACION',
                'estado' => 'PROCESANDO',
                'detalles' => 'Recibido lote completo de ' . count($items) . ' registros para ' . $banco . ' (' . $fuente . ')'
            ]);

            $conciliacion = Conciliacion::updateOrCreate(
                ['banco' => $banco, 'mes' => $mes, 'anio' => $anio],
                ['estado' => 'PROCESANDO']
            );

            // Clear ONLY the items from this source to allow re-uploading just one side
            if ($fuente === 'EXTRACTO') {
                $conciliacion->extractoItems()->delete();
            } else {
                $conciliacion->auxiliarItems()->delete();
            }

            foreach ($items as $item) {
                $fechaRaw = $item['fecha'] ?? now();
                try {
                    $fecha = (is_numeric($fechaRaw) && $fechaRaw > 30000) 
                        ? \Carbon\Carbon::createFromTimestamp(($fechaRaw - 25569) * 86400)
                        : \Carbon\Carbon::parse($fechaRaw);
                } catch (\Exception $e) {
                    $fecha = now();
                }

                if ($fuente === 'EXTRACTO') {
                    $conciliacion->extractoItems()->create([
                        'fecha' => $fecha,
                        'descripcion' => $item['descripcion'] ?? 'Sin descripción',
                        'referencia' => $item['referencia'] ?? null,
                        'valor' => $item['valor'] ?? 0,
                        'conciliado' => false
                    ]);
                } else {
                    $conciliacion->auxiliarItems()->create([
                        'fecha' => $fecha,
                        'identificacion' => $item['identificacion'] ?? 'N/A',
                        'descripcion' => $item['descripcion'] ?? 'Sin descripción',
                        'referencia' => $item['referencia'] ?? null,
                        'valor' => $item['valor'] ?? 0,
                        'conciliado' => false
                    ]);
                }
            }

            $conciliacion->recalculateBalances();
            
            $bitacora->update(['estado' => 'PROCESADO']);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Batch processed']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Batch processing error: ' . $e->getMessage());
            if (isset($bitacora)) {
                $bitacora->update(['estado' => 'ERROR', 'detalles' => $e->getMessage()]);
            }
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * List all reconciliations.
     */
    public function indexReconciliations()
    {
        $list = Conciliacion::latest()->get()->map(function($c) {
            $c->recalculateBalances();
            return $c;
        });
        return response()->json($list);
    }

    /**
     * Show single reconciliation with items.
     */
    public function showReconciliation($id)
    {
        return response()->json(Conciliacion::with(['extractoItems', 'auxiliarItems'])->findOrFail($id));
    }

    /**
     * Trigger manual reconciliation matching.
     */
    public function runReconciliation($id)
    {
        $conciliacion = Conciliacion::findOrFail($id);
        $conciliacion->reconcile();
        
        return response()->json([
            'status' => 'success', 
            'message' => 'Reconciliation completed',
            'data' => $conciliacion->load(['extractoItems', 'auxiliarItems'])
        ]);
    }

    /**
     * Proxy PDF to n8n.
     */
    public function forwardToN8nPdf(Request $request)
    {
        ini_set('max_execution_time', 300);
        set_time_limit(300);

        if (!$request->hasFile('data')) {
            return $this->corsResponse(['error' => 'No file uploaded'], 400);
        }

        try {
            $file = $request->file('data');
            
            // SAVE FILE LOCALLY - Explicitly use public disk
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('solicitudes', $fileName, 'public');
            $fileUrl = Storage::url($filePath);

            // Call n8n (Pure Extraction)
            $response = Http::timeout(300)->attach(
                'data', file_get_contents($file->getRealPath()), $file->getClientOriginalName()
            )->post('http://localhost:5678/webhook/credito-pdf', [
                'nombre_archivo' => $request->nombre_archivo
            ]);

            Log::info('Response from n8n:', ['body' => $response->body(), 'status' => $response->status()]);

            $extractedData = json_decode($response->body(), true);
            
            if (is_array($extractedData) && isset($extractedData[0]) && is_array($extractedData[0])) {
                $extractedData = $extractedData[0];
            }

            if ($response->status() >= 400 || (isset($extractedData['status']) && $extractedData['status'] === 'error')) {
                return $this->corsResponse(['error' => 'n8n failed', 'details' => $extractedData], 500);
            }

            $extractedData['url_archivo'] = $fileUrl;
            $extractedData['nombre_archivo'] = $request->nombre_archivo;
            $credito = $this->storeCreditRecord($extractedData);

            return $this->corsResponse($extractedData, 201);

        } catch (\Exception $e) {
            Log::error('Proxy PDF Error: ' . $e->getMessage());
            return $this->corsResponse([
                'status' => 'error',
                'message' => 'Error en el túnel de PDF hacia n8n',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Proxy Excel to n8n.
     */
    public function forwardToN8nExcel(Request $request)
    {
        ini_set('max_execution_time', 300);
        set_time_limit(300);

        if (!$request->hasFile('data')) {
            return $this->corsResponse(['error' => 'No file uploaded'], 400);
        }

        try {
            $file = $request->file('data');
            
            Log::info('Proxying Excel to n8n:', [
                'banco' => $request->banco,
                'mes' => $request->mes,
                'anio' => $request->anio,
                'fuente' => $request->fuente
            ]);

            // Call n8n (Pattern Batches)
            $response = Http::timeout(300)->attach(
                'data', file_get_contents($file->getRealPath()), $file->getClientOriginalName()
            )->post('http://localhost:5678/webhook/conciliacion-excel', [
                'nombre_archivo' => $request->nombre_archivo,
                'banco' => $request->banco,
                'mes' => $request->mes,
                'anio' => $request->anio,
                'fuente' => $request->fuente // EXTRACTO or AUXILIAR
            ]);

            $responseData = $response->json();
            
            // Handle cases where n8n returns a string instead of JSON on error
            if (is_null($responseData)) {
                $responseData = ['message' => 'Respuesta no válida de n8n', 'raw' => $response->body()];
            }

            return $this->corsResponse($responseData, $response->status());

        } catch (\Exception $e) {
            Log::error('Proxy Excel Error: ' . $e->getMessage());
            return $this->corsResponse([
                'status' => 'error',
                'message' => 'Error en el túnel de Excel hacia n8n',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper for consistent CORS headers.
     */
    private function corsResponse($data, $status = 200)
    {
        return response()->json($data, $status)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    }
}
