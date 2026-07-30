<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gis_basvuru_noktalar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('basvuru_id')->nullable();
            $table->enum('basvuru_tipi', ['kazi_ruhsat', 'ortak_kazi']);
            $table->decimal('lat', 15, 8);
            $table->decimal('lng', 15, 8);
            $table->string('ilce', 100)->nullable();
            $table->string('mahalle', 100)->nullable();
            $table->string('ada', 50)->nullable();
            $table->string('parsel', 50)->nullable();
            $table->json('wfs_response')->nullable();
            $table->timestamps();

            $table->foreign('basvuru_id')->references('id')->on('applications')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gis_basvuru_noktalar');
    }
};