<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requirements', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('client_id')->constrained();
            $table->string('crew_chief');
            $table->string('district');
            $table->string('delivery_address')->nullable();
            $table->date('required_date');
            $table->enum('status', ['pendiente', 'despachado', 'anulado'])->default('pendiente');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'status']);
            $table->index('required_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requirements');
    }
};
