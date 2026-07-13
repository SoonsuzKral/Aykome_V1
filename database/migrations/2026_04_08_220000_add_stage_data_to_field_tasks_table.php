<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('field_tasks', function (Blueprint $table) {
            // Aşama 1 — Kazı Öncesi Kontrol
            $table->enum('stage_1_status', ['pending', 'done'])->default('pending')->after('notes');
            $table->text('stage_1_notes')->nullable()->after('stage_1_status');
            $table->timestamp('stage_1_inspected_at')->nullable()->after('stage_1_notes');

            // Aşama 2 — Kazı Tamamlandı Kontrol
            $table->enum('stage_2_status', ['pending', 'done'])->default('pending')->after('stage_1_inspected_at');
            $table->text('stage_2_notes')->nullable()->after('stage_2_status');
            $table->timestamp('stage_2_inspected_at')->nullable()->after('stage_2_notes');

            // Aşama 3 — Zemin Onarım Sonrası Kontrol
            $table->enum('stage_3_status', ['pending', 'done'])->default('pending')->after('stage_2_inspected_at');
            $table->text('stage_3_notes')->nullable()->after('stage_3_status');
            $table->timestamp('stage_3_inspected_at')->nullable()->after('stage_3_notes');
        });
    }

    public function down(): void
    {
        Schema::table('field_tasks', function (Blueprint $table) {
            $table->dropColumn([
                'stage_1_status', 'stage_1_notes', 'stage_1_inspected_at',
                'stage_2_status', 'stage_2_notes', 'stage_2_inspected_at',
                'stage_3_status', 'stage_3_notes', 'stage_3_inspected_at',
            ]);
        });
    }
};
