<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hasta ahora todo cliente era una empresa con RUC de 11 dígitos, porque solo
 * se emitían facturas. Con la boleta de venta hay que poder registrar personas
 * con DNI —y ventas de mostrador sin documento—, así que la columna deja de
 * llamarse `ruc` y se acompaña del tipo de documento (catálogo 06 de SUNAT).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // 6 = RUC, 1 = DNI, 4 = carné de extranjería, 7 = pasaporte,
            // 0 = sin documento (boleta de mostrador).
            $table->char('document_type', 1)->default('6')->after('id');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique('clients_ruc_unique');
            $table->renameColumn('ruc', 'document_number');
        });

        Schema::table('clients', function (Blueprint $table) {
            // Un DNI tiene 8 dígitos y un pasaporte hasta 12: ya no son 11 fijos.
            $table->string('document_number', 15)->change();
            $table->unique(['document_type', 'document_number']);
        });

        // Lo que ya existe son empresas: todas con RUC.
        DB::table('clients')->update(['document_type' => '6']);
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique(['document_type', 'document_number']);
            $table->dropColumn('document_type');
            $table->renameColumn('document_number', 'ruc');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->char('ruc', 11)->change();
            $table->unique('ruc');
        });
    }
};
