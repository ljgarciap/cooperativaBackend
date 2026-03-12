<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conciliacion extends Model
{
    protected $fillable = [
        'banco',
        'mes',
        'anio',
        'saldo_banco',
        'saldo_contable',
        'estado', // PENDIENTE, CARGADO, CON_DISCREPANCIAS, CONCILIADO
    ];

    public function extractoItems(): HasMany
    {
        return $this->hasMany(ExtractoItem::class);
    }

    public function auxiliarItems(): HasMany
    {
        return $this->hasMany(AuxiliarItem::class);
    }

    /**
     * Automated matching logic.
     */
    public function reconcile(): void
    {
        $extractos = $this->extractoItems()->where('conciliado', false)->get();
        $auxiliares = $this->auxiliarItems()->where('conciliado', false)->get();

        // PHASE 1: Match by Value + Reference (Strongest Match)
        foreach ($extractos as $ext) {
            foreach ($auxiliares as $aux) {
                if ($aux->conciliado) continue;

                $refExt = trim(strtolower($ext->referencia ?? ''));
                $refAux = trim(strtolower($aux->referencia ?? $aux->identificacion ?? ''));

                if ($ext->valor == $aux->valor && !empty($refExt) && $refExt === $refAux) {
                    $ext->update(['conciliado' => true]);
                    $aux->update(['conciliado' => true]);
                    continue 2;
                }
            }
        }

        // Refresh pending lists for Phase 2
        $extractos = $this->extractoItems()->where('conciliado', false)->get();
        $auxiliares = $this->auxiliarItems()->where('conciliado', false)->get();

        // PHASE 2: Match by Value + Date Margin (+/- 3 days)
        foreach ($extractos as $ext) {
            foreach ($auxiliares as $aux) {
                if ($aux->conciliado) continue;

                if ($ext->valor == $aux->valor) {
                    $fechaExt = \Carbon\Carbon::parse($ext->fecha);
                    $fechaAux = \Carbon\Carbon::parse($aux->fecha);
                    
                    if ($fechaExt->diffInDays($fechaAux) <= 3) {
                        $ext->update(['conciliado' => true]);
                        $aux->update(['conciliado' => true]);
                        continue 2;
                    }
                }
            }
        }

        $this->recalculateBalances();
        
        // Update status if everything is reconciled
        $pending = $this->extractoItems()->where('conciliado', false)->count() + 
                  $this->auxiliarItems()->where('conciliado', false)->count();
        
        $this->estado = ($pending == 0) ? 'CONCILIADO' : 'CON_DISCREPANCIAS';
        $this->save();
    }

    /**
     * Recalculate balances based on items.
     */
    public function recalculateBalances(): void
    {
        $this->saldo_banco = $this->extractoItems()->sum('valor');
        $this->saldo_contable = $this->auxiliarItems()->sum('valor');
        
        if ($this->estado === 'PROCESANDO' || $this->estado === 'PENDIENTE') {
            if ($this->extractoItems()->count() > 0 || $this->auxiliarItems()->count() > 0) {
                $this->estado = 'CARGADO';
            }
        }
        
        $this->save();
    }
}
