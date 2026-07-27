<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained();
            // Snapshot congelado al emitir: la referencia con la que se firmó
            // el XML no debe depender de cambios posteriores en la factura.
            $table->string('affected_full_number', 20)->nullable();
            $table->char('currency', 3)->default('PEN');
            $table->foreignId('series_id')->constrained();
            $table->unsignedInteger('number');
            $table->string('full_number', 20)->unique();
            $table->date('issue_date');
            // Catálogo SUNAT 09: 01 = anulación de la operación.
            $table->char('motive_code', 2)->default('01');
            $table->string('motive_description');
            $table->decimal('taxable_amount', 12, 2);
            $table->decimal('igv_rate', 5, 2)->default(18.00);
            $table->decimal('igv', 12, 2);
            $table->decimal('total', 12, 2);
            $table->enum('status', ['pendiente_envio', 'aceptada', 'rechazada'])->default('pendiente_envio');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['series_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
