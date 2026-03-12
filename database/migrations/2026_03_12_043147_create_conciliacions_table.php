<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conciliacions', function (Blueprint $table) {
            $table->id();
            $table->string('banco');
            $table->string('mes');
            $table->string('anio');
            $table->decimal('saldo_banco', 15, 2)->default(0);
            $table->decimal('saldo_contable', 15, 2)->default(0);
            $table->string('estado')->default('PENDIENTE');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conciliacions');
    }
};
