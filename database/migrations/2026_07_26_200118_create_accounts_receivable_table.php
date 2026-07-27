<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts_receivable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->unique()->constrained();
            // Fecha de emisión + 30 días.
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance', 12, 2);
            // Recalculado a diario por el scheduler (cobranzas:actualizar-semaforo).
            $table->enum('traffic_light', ['verde', 'amarillo', 'rojo'])->default('verde');
            $table->enum('status', ['pendiente', 'parcial', 'pagada', 'anulada'])->default('pendiente');
            $table->timestamps();

            $table->index('traffic_light');
            $table->index('due_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts_receivable');
    }
};
