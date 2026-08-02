<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * applications.approval_log
 * --------------------------
 * Süreç & Onay Rotası motorunun adım-adım onay kaydı. Her onaylanan adım,
 * hangi rol/adım tarafından ne zaman onaylandığı buraya (JSON/CLOB) yazılır.
 * Legacy kolonlar (staff_approved_by vb.) default süreç için eşzamanlı
 * doldurulmaya devam eder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (! Schema::hasColumn('applications', 'approval_log')) {
                $table->longText('approval_log')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'approval_log')) {
                $table->dropColumn('approval_log');
            }
        });
    }
};
