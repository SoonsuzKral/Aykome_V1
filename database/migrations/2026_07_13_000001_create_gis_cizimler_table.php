<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gis_cizimler', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('tip', ['nokta', 'cizgi', 'alan']);
            $table->json('geometri')->nullable();
            $table->unsignedBigInteger('basvuru_id')->nullable();
            $table->decimal('lat', 15, 8)->nullable();
            $table->decimal('lng', 15, 8)->nullable();
            $table->text('aciklama')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('basvuru_id')->references('id')->on('applications')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gis_cizimler');
    }
};
