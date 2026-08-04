<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El sondeo del ticket GRE vuelve a entrar al job cada 30 segundos, así que un
 * solo comprobante puede acumular cientos de intentos y desbordar el tinyint
 * (tope 255): MySQL corta el envío con "Out of range value" y el comprobante
 * queda trabado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('electronic_documents', function (Blueprint $table) {
            $table->unsignedInteger('attempts')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('electronic_documents', function (Blueprint $table) {
            $table->unsignedTinyInteger('attempts')->default(0)->change();
        });
    }
};
