<?php

namespace App\Http\Controllers;

use App\Models\Credito;
use App\Models\HistorialEstado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CreditoController extends Controller
{
    /**
     * Update the specified credit.
     */
    public function update(Request $request, $id)
    {
        $credito = Credito::findOrFail($id);
        
        $request->validate([
            'nombre' => 'sometimes|string',
            'identificacion' => 'sometimes|string',
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
}
