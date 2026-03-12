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
use Illuminate\Support\Facades\Log;

class SystemController extends Controller
{
    /**
     * Reset the transactional data of the system.
     */
    public function reset(Request $request)
    {
        Log::info('System reset requested');

        try {
            // Disable foreign key checks to allow truncation
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            Bitacora::truncate();
            HistorialEstado::truncate();
            Credito::truncate();
            ExtractoItem::truncate();
            AuxiliarItem::truncate();
            Conciliacion::truncate();

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            Log::info('System reset successful');

            return response()->json([
                'status' => 'success',
                'message' => 'El sistema ha sido reiniciado correctamente. Todos los datos transaccionales han sido eliminados.'
            ], 200);

        } catch (\Exception $e) {
            // Ensure foreign key checks are re-enabled even on failure
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            Log::error('System reset failed: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Error al reiniciar el sistema: ' . $e->getMessage()
            ], 500);
        }
    }
}
