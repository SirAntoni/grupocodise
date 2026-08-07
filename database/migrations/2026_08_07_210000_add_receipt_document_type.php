<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Boleta de venta (tipo 03). Comparte tabla con la factura porque es el mismo
 * documento de venta con otro tipo: cambian la serie, el comprobante que se
 * manda a SUNAT y el título impreso.
 */
return new class extends Migration
{
    public function up(): void
    {
        // El enum de series estaba cerrado a tres tipos.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE series MODIFY document_type ENUM('guia_remision', 'factura', 'boleta', 'nota_credito') NOT NULL");
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('document_type', ['factura', 'boleta'])->default('factura')->after('client_id');
        });

        // Todo lo emitido hasta ahora fue factura.
        DB::table('invoices')->update(['document_type' => 'factura']);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('document_type');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE series MODIFY document_type ENUM('guia_remision', 'factura', 'nota_credito') NOT NULL");
        }
    }
};
