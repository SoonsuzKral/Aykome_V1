<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('excavation_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->longText('polygon_geojson')->nullable();
            $table->decimal('total_area_m2', 14, 4)->default(0);
            $table->decimal('center_lat', 12, 8)->nullable();
            $table->decimal('center_lng', 12, 8)->nullable();
            $table->string('address_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excavation_areas');
    }
};
