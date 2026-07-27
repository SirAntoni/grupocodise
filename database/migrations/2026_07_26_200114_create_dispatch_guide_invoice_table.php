<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Histórico de qué guías respaldaron cada factura. La regla "una guía
        // solo puede estar en UNA factura activa" se valida en InvoiceService
        // contra el estado de la factura, conservando el histórico tras anular.
        Schema::create('dispatch_guide_invoice', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_guide_id')->constrained();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            // true mientras la factura está activa; NULL al anularse por NC.
            // MySQL permite múltiples NULL en el unique, así que el histórico
            // se conserva y la BD garantiza una sola factura activa por guía.
            $table->boolean('active')->nullable()->default(true);
            $table->timestamps();

            $table->unique(['dispatch_guide_id', 'invoice_id']);
            $table->unique(['dispatch_guide_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_guide_invoice');
    }
};
