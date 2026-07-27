<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            // Catálogo SUNAT 03: NIU (unidad), KGM (kilo), MTR (metro), etc.
            $table->char('unit_code', 3)->default('NIU');
            $table->decimal('stock', 12, 2)->default(0);
            // La baja es lógica vía is_active; sin soft deletes para no
            // bloquear el unique de código.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Última barrera contra stock negativo; la validación de negocio con
        // alerta vive en StockService (lockForUpdate + InsufficientStockException).
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE products ADD CONSTRAINT chk_products_stock_non_negative CHECK (stock >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
