<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained();
            $table->foreignId('purchase_order_id')->nullable()->constrained();
            // Una factura referencia a lo sumo UNA orden de compra: si las
            // guías consolidadas traen OCs distintas, InvoiceService lo rechaza.
            // Serie y número se asignan al Emitir; los borradores no consumen correlativo.
            $table->foreignId('series_id')->nullable()->constrained();
            $table->unsignedInteger('number')->nullable();
            $table->string('full_number', 20)->nullable()->unique();
            $table->enum('status', ['borrador', 'pendiente_envio', 'aceptada', 'rechazada', 'anulada'])->default('borrador');
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();
            // SUNAT exige declarar FormaPago (contado/crédito) y, al crédito,
            // las cuotas; el negocio factura al crédito a 30 días (cuota única
            // derivada de due_date y total en el servicio de emisión).
            $table->enum('payment_type', ['contado', 'credito'])->default('credito');
            $table->char('currency', 3)->default('PEN');
            $table->decimal('taxable_amount', 12, 2)->default(0);
            $table->decimal('igv', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('annulled_at')->nullable();
            $table->foreignId('annulled_by')->nullable()->constrained('users');
            $table->foreignId('issued_by')->nullable()->constrained('users');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['series_id', 'number']);
            $table->index(['client_id', 'status']);
            $table->index('issue_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
