<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kilitli Onay Akışı (Approval Engine) Kolonları
 * ----------------------------------------------
 * Alt kurum başvuruyu gönderir → Belediye personeli (staff) onaylar →
 * Müdür (director) onaylar → Başkan Yrd. (vice_mayor) onaylar.
 * Aşama bilgisi ve her aşamanın onaylayan kullanıcı/tarih bilgileri
 * applications tablosunda tutulur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (! Schema::hasColumn('applications', 'approval_stage')) {
                $table->string('approval_stage', 20)->nullable();
            }
            if (! Schema::hasColumn('applications', 'staff_approved_by')) {
                $table->unsignedBigInteger('staff_approved_by')->nullable();
            }
            if (! Schema::hasColumn('applications', 'staff_approved_at')) {
                $table->timestamp('staff_approved_at')->nullable();
            }
            if (! Schema::hasColumn('applications', 'director_approved_by')) {
                $table->unsignedBigInteger('director_approved_by')->nullable();
            }
            if (! Schema::hasColumn('applications', 'director_approved_at')) {
                $table->timestamp('director_approved_at')->nullable();
            }
            if (! Schema::hasColumn('applications', 'vice_mayor_approved_by')) {
                $table->unsignedBigInteger('vice_mayor_approved_by')->nullable();
            }
            if (! Schema::hasColumn('applications', 'vice_mayor_approved_at')) {
                $table->timestamp('vice_mayor_approved_at')->nullable();
            }
        });

        // Mevcut kayıtlar için başlangıç aşaması
        \Illuminate\Support\Facades\DB::table('applications')
            ->whereIn('status', ['submitted', 'pending', 'draft'])
            ->update(['approval_stage' => 'staff']);

        \Illuminate\Support\Facades\DB::table('applications')
            ->whereIn('status', [
                'pre_excavation_approved', 'pre_approved', 'measurement_done',
                'accrued', 'priced', 'awaiting_payment', 'receipt_pending',
                'approved', 'licensed', 'field_work', 'completed',
            ])
            ->update(['approval_stage' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            foreach (['approval_stage', 'staff_approved_by', 'staff_approved_at', 'director_approved_by', 'director_approved_at', 'vice_mayor_approved_by', 'vice_mayor_approved_at'] as $col) {
                if (Schema::hasColumn('applications', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
