<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bitácora de cada intento de comunicación con SUNAT, para soporte.
        Schema::create('sunat_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('electronic_document_id')->constrained()->cascadeOnDelete();
            $table->enum('action', ['enviar', 'consultar_ticket', 'descargar_cdr']);
            $table->boolean('success');
            $table->string('response_code', 20)->nullable();
            $table->text('response_message')->nullable();
            $table->json('payload')->nullable();
            // Nulo cuando el envío lo hizo la cola; con valor en reintentos manuales.
            $table->foreignId('user_id')->nullable()->constrained();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sunat_logs');
    }
};
