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
        Schema::create('extracto_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conciliacion_id')->constrained('conciliacions')->onDelete('cascade');
            $table->date('fecha');
            $table->string('descripcion');
            $table->string('referencia')->nullable();
            $table->decimal('valor', 15, 2);
            $table->boolean('conciliado')->default(false);
            $table->string('color')->nullable(); // For reconciliation tracking
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extracto_items');
    }
};
