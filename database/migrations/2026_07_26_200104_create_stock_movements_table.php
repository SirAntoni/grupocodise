<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->enum('type', ['entrada', 'salida_guia', 'restitucion_anulacion', 'ajuste']);
            // Cantidad siempre positiva; el sentido lo da el tipo (los ajustes pueden ser negativos).
            $table->decimal('quantity', 12, 2);
            $table->decimal('stock_before', 12, 2);
            $table->decimal('stock_after', 12, 2);
            $table->nullableMorphs('reference');
            $table->string('notes')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['product_id', 'created_at']);
        });

        // Los ajustes llevan signo (pueden ser negativos); el resto siempre positivo.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock_movements ADD CONSTRAINT chk_stock_movements_quantity CHECK ((type = 'ajuste' AND quantity <> 0) OR (type <> 'ajuste' AND quantity > 0))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
