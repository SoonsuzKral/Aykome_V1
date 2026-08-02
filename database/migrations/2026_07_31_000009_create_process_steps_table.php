<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Süreç & Onay Rotası — Adımlar (Silsile Adımları)
 * -------------------------------------------------
 * Her adım: bir yetkili rol seti + o adımın "karışabileceği" (onaylayabileceği)
 * modüller. role_key, legacy onay aşamasıdır (staff/director/vice_mayor) ve
 * applications.approval_stage değerini temsil eder; özel süreçlerde serbest
 * slug kullanılabilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('process_steps')) {
            Schema::create('process_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('process_definition_id')->constrained('process_definitions')->cascadeOnDelete();
                $table->string('name', 190);
                $table->string('role_key', 50);
                $table->longText('roles')->nullable();               // JSON: yetkili spatie rol adları
                $table->longText('approvable_modules')->nullable();  // JSON: onaylanabilir modüller
                $table->unsignedInteger('step_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('process_steps');
    }
};
