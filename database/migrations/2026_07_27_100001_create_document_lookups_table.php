<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Padrón local de consultas RUC/DNI EXITOSAS: cada documento válido se
        // consulta al API una sola vez y queda aquí; los errores y los "no
        // encontrado" no se guardan. Solo se refresca cuando envejece.
        Schema::create('document_lookups', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['dni', 'ruc']);
            $table->string('number', 11);
            $table->json('payload');
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->unique(['type', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_lookups');
    }
};
