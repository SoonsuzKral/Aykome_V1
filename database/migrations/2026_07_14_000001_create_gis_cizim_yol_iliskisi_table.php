<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gis_cizim_yol_iliskisi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cizim_id');
            $table->unsignedBigInteger('hat_kimligi');
            $table->string('yol_adi', 200)->nullable();
            $table->string('yol_turu', 50)->nullable();
            $table->string('mahalle', 100)->nullable();
            $table->string('ilce', 100)->nullable();
            $table->decimal('genislik', 10, 2)->nullable();
            $table->decimal('uzunluk', 15, 4)->nullable();
            $table->string('sorumluluk', 100)->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->foreign('cizim_id')->references('id')->on('gis_cizimler')->onDelete('cascade');
            $table->unique(['cizim_id', 'hat_kimligi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gis_cizim_yol_iliskisi');
    }
};
