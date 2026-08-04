<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Vendedor" es quien hizo la venta, que no siempre es quien digita el
 * documento: se propone con el usuario conectado pero se puede cambiar.
 * Los documentos ya existentes toman como vendedor a quien los registró.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['invoices', 'quotations', 'purchase_orders'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->foreignId('seller_id')->nullable()->after('created_by')->constrained('users');
            });

            \Illuminate\Support\Facades\DB::table($tabla)->update(['seller_id' => \Illuminate\Support\Facades\DB::raw('created_by')]);
        }
    }

    public function down(): void
    {
        foreach (['invoices', 'quotations', 'purchase_orders'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['seller_id']);
                $table->dropColumn('seller_id');
            });
        }
    }
};
