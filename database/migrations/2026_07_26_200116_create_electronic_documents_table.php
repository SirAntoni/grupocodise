<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Estado del ciclo de emisión ante SUNAT, 1:1 con el comprobante
        // (dispatch_guides, invoices o credit_notes).
        Schema::create('electronic_documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable');
            $table->enum('environment', ['beta', 'produccion']);
            $table->enum('sunat_status', [
                'pendiente', 'enviado', 'aceptado', 'aceptado_con_observaciones', 'rechazado', 'error',
            ])->default('pendiente');
            // Nº de ticket que devuelve el API REST de GRE para consultar el resultado.
            $table->string('ticket', 60)->nullable();
            $table->string('error_code', 20)->nullable();
            $table->text('error_message')->nullable();
            $table->string('xml_path')->nullable();
            $table->string('cdr_path')->nullable();
            $table->string('pdf_path')->nullable();
            // Valor resumen (DigestValue) del XML firmado, para la representación impresa.
            $table->string('digest_hash')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->unique(['documentable_type', 'documentable_id']);
            $table->index('sunat_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electronic_documents');
    }
};
