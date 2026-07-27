<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->char('ruc', 11)->unique();
            $table->string('address');
            $table->char('ubigeo', 6)->nullable();
            $table->string('district')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('contact_name')->nullable();
            // La baja es lógica vía is_active (la spec pide desactivar, no
            // eliminar); sin soft deletes para no bloquear el unique de RUC.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
