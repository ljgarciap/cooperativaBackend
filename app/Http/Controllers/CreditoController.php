<?php

namespace App\Http\Controllers;

use App\Models\Credito;
use App\Models\HistorialEstado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Barryvdh\DomPDF\Facade\Pdf;

class CreditoController extends Controller
{
    /**
     * Get all credits.
     */
    public function index()
    {
        return response()->json(Credito::orderBy('created_at', 'desc')->get());
    }
    /**
     * Update the specified credit.
     */
    public function update(Request $request, $id)
    {
        $credito = Credito::findOrFail($id);
        
        $request->validate([
            'nombre' => 'sometimes|string',
            'identificacion' => 'sometimes|string',
            'celular' => 'sometimes|string|nullable',
            'correo' => 'sometimes|string|nullable',
            'monto' => 'sometimes|numeric',
            'tipo' => 'sometimes|string',
            'estado' => 'sometimes|string',
            'observaciones' => 'sometimes|string',
        ]);

        $oldEstado = $credito->estado;
        $credito->update($request->all());

        if ($request->has('estado') && $request->estado !== $oldEstado) {
            HistorialEstado::create([
                'credito_id' => $credito->id,
                'estado' => $credito->estado,
                'usuario' => 'ADMIN_USER',
                'observaciones' => 'Estado actualizado manualmente',
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Crédito actualizado correctamente',
            'data' => $credito
        ]);
    }

    /**
     * Delete a credit record.
     */
    public function destroy($id)
    {
        $credito = Credito::findOrFail($id);
        $credito->delete();
        return response()->json(['status' => 'success', 'message' => 'Crédito eliminado']);
    }

    /**
     * Generate and download PDF documentation for a credit.
     */
    public function downloadPdf($id)
    {
        $credito = Credito::findOrFail($id);
        
        $data = [
            'credito' => $credito,
            'fecha_generacion' => now()->format('d/m/Y H:i'),
            'no_tramite' => 'TR-' . str_pad($credito->id, 6, '0', STR_PAD_LEFT)
        ];

        $pdf = Pdf::loadView('pdf.credito_aprobacion', $data);
        
        // Clean name for filename
        $safeName = str_replace(' ', '_', preg_replace('/[^A-Za-z0-9 ]/', '', $credito->nombre));
        $date = now()->format('Y-m-d');
        
        return $pdf->download("Documentacion_{$safeName}_{$date}.pdf");
    }

    /**
     * Quick approve action.
     */
    public function approve($id)
    {
        $credito = Credito::findOrFail($id);
        $credito->update(['estado' => 'APROBADO']);
        
        HistorialEstado::create([
            'credito_id' => $credito->id,
            'estado' => 'APROBADO',
            'usuario' => 'AGENTE_IA',
            'observaciones' => 'Aprobación rápida desde panel'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Crédito aprobado',
            'data' => $credito
        ]);
    }

    /**
     * Quick reject action.
     */
    public function reject($id)
    {
        $credito = Credito::findOrFail($id);
        $credito->update(['estado' => 'RECHAZADO']);
        
        HistorialEstado::create([
            'credito_id' => $credito->id,
            'estado' => 'RECHAZADO',
            'usuario' => 'AGENTE_IA',
            'observaciones' => 'Rechazo rápido desde panel'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Crédito rechazado',
            'data' => $credito
        ]);
    }
}
