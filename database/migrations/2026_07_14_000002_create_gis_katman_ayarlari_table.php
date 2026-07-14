<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gis_katman_ayarlari', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('katman_adi', 100);
            $table->boolean('gorunur')->default(true);
            $table->decimal('opacity', 3, 2)->default(0.70);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'katman_adi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gis_katman_ayarlari');
    }
};
