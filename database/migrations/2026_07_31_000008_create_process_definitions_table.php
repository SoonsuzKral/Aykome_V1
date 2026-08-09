<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Süreç & Onay Rotası — Süreç Tanımları
 * -------------------------------------
 * Belediye merkez yönetimi (super-admin + municipality-admin) tarafından
 * tanımlanan onay silsileleridir. Alt kurumlar bu modüle ERİŞEMEZ.
 * Her süreç N adımlı bir rota barındırır (process_steps tablosu).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('process_definitions')) {
            Schema::create('process_definitions', function (Blueprint $table) {
                $table->id();
                $table->string('name', 190);
                $table->string('slug', 190)->unique();
                $table->string('description', 500)->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('process_definitions');
    }
};
