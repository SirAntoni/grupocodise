<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            // Nulo para líneas que no son producto (p. ej. "zona lejana").
            $table->foreignId('product_id')->nullable()->constrained();
            $table->enum('type', ['producto', 'zona_lejana'])->default('producto');
            $table->string('description');
            $table->char('unit_code', 3)->default('NIU');
            $table->decimal('quantity', 12, 2);
            // Valor unitario sin IGV, editable al facturar.
            $table->decimal('unit_value', 12, 4);
            // Catálogo SUNAT 07 (10 = gravado) y tasa vigente al emitir, para
            // no depender de la constante del código en comprobantes históricos.
            $table->char('igv_affectation_code', 2)->default('10');
            $table->decimal('igv_rate', 5, 2)->default(18.00);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('igv', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
