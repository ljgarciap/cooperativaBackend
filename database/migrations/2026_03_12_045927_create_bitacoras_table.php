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
        Schema::create('bitacoras', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_archivo');
            $table->string('tipo_archivo'); // 'PDF' or 'EXCEL'
            $table->string('proceso'); // 'CREDITOS' or 'CONCILIACION'
            $table->string('estado')->default('PROCESANDO');
            $table->string('usuario')->default('DEMO_USER');
            $table->unsignedBigInteger('entidad_id')->nullable(); // ID of Credito or Conciliacion
            $table->text('detalles')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacoras');
    }
};
