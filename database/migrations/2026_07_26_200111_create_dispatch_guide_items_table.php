<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_guide_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_guide_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            // Snapshot al momento de crear la línea: el comprobante emitido no
            // debe cambiar si luego se renombra el producto.
            $table->string('description');
            $table->char('unit_code', 3)->default('NIU');
            $table->decimal('quantity_requested', 12, 2);
            $table->decimal('quantity_dispatched', 12, 2);
            $table->timestamps();

            $table->unique(['dispatch_guide_id', 'product_id']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE dispatch_guide_items ADD CONSTRAINT chk_guide_items_quantities CHECK (quantity_dispatched >= 0 AND quantity_requested > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_guide_items');
    }
};
