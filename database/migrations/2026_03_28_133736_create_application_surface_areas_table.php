<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_surface_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('surface_type_id')->constrained()->restrictOnDelete();
            $table->decimal('width_m', 12, 4)->nullable();
            $table->decimal('length_m', 12, 4)->nullable();
            $table->decimal('quantity', 12, 4)->default(1);
            $table->decimal('multiplier', 12, 4)->default(1);
            $table->decimal('amount', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_surface_areas');
    }
};
