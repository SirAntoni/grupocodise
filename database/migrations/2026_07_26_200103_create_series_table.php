<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('series', function (Blueprint $table) {
            $table->id();
            $table->enum('document_type', ['guia_remision', 'factura', 'nota_credito']);
            // Formato SUNAT: T001 para guías, F001 para facturas, FC01/F001 para NC.
            $table->string('code', 4);
            $table->unsignedInteger('next_number')->default(1);
            // La numeración de beta y producción es independiente ante SUNAT;
            // cada serie pertenece a un ambiente y la emisión usa solo las del
            // ambiente activo (config facturacion.environment).
            $table->enum('environment', ['beta', 'produccion'])->default('beta');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['document_type', 'code', 'environment']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('series');
    }
};
