<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained();
            $table->foreignId('requirement_id')->nullable()->constrained();
            $table->foreignId('purchase_order_id')->nullable()->constrained();
            // Serie y número se asignan al Emitir; los borradores no consumen correlativo.
            $table->foreignId('series_id')->nullable()->constrained();
            $table->unsignedInteger('number')->nullable();
            $table->string('full_number', 20)->nullable()->unique();
            $table->enum('status', ['borrador', 'emitida', 'anulada'])->default('borrador');
            $table->date('issue_date')->nullable();
            $table->date('transfer_date')->nullable();
            // Catálogo SUNAT 20 (motivo de traslado): 01 = venta.
            $table->char('transfer_reason_code', 2)->default('01');
            // Catálogo SUNAT 18: publico = 01, privado = 02.
            $table->enum('transport_mode', ['publico', 'privado'])->nullable();
            $table->char('carrier_ruc', 11)->nullable();
            $table->string('carrier_name')->nullable();
            $table->string('vehicle_plate', 10)->nullable();
            // El XML de la GRE exige nombres y apellidos por separado y el
            // tipo de documento del conductor (catálogo 06: 1=DNI, 4=CE).
            $table->string('driver_first_names')->nullable();
            $table->string('driver_last_names')->nullable();
            $table->char('driver_document_type', 1)->nullable()->default('1');
            $table->string('driver_document', 15)->nullable();
            $table->string('driver_license', 15)->nullable();
            $table->string('origin_address');
            $table->char('origin_ubigeo', 6);
            $table->string('delivery_address')->nullable();
            $table->char('delivery_ubigeo', 6)->nullable();
            $table->string('district')->nullable();
            $table->string('crew_chief')->nullable();
            $table->decimal('total_weight', 10, 2)->nullable();
            $table->char('weight_unit', 3)->default('KGM');
            $table->unsignedInteger('packages_count')->nullable();
            $table->text('notes')->nullable();
            $table->string('annulment_reason')->nullable();
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
        Schema::dropIfExists('dispatch_guides');
    }
};
