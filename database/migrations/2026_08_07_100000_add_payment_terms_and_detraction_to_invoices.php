<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Condiciones de pago por factura (contado/crédito con sus días) y detracción
 * del SPOT. Antes los días de crédito eran un ajuste global del servidor y la
 * detracción no existía.
 *
 * Las facturas ya emitidas conservan lo que se les aplicó: crédito con los
 * días que estaban configurados en ese momento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedSmallInteger('credit_days')->nullable()->after('payment_type');

            $table->boolean('has_detraction')->default(false)->after('currency');
            // Catálogo 54 de SUNAT: qué bien o servicio la origina.
            $table->char('detraction_code', 3)->nullable()->after('has_detraction');
            $table->decimal('detraction_percent', 5, 2)->nullable()->after('detraction_code');
            $table->decimal('detraction_amount', 12, 2)->nullable()->after('detraction_percent');
        });

        \Illuminate\Support\Facades\DB::table('invoices')
            ->where('payment_type', 'credito')
            ->update(['credit_days' => (int) config('facturacion.payment_due_days', 30)]);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'credit_days',
                'has_detraction',
                'detraction_code',
                'detraction_percent',
                'detraction_amount',
            ]);
        });
    }
};
