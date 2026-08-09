<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dateTime('licensed_at')->nullable()->after('license_document_path');
        });

        // Mevcut ruhsatlandırılmış kayıtlara geriye dönük backfill.
        // Ruhsatlandırma sırasında receipt_approved_at aynı anda set edildiği
        // için teminat 100 gün sayacı bu tarihten başlatılabilir.
        DB::table('applications')
            ->where('status', 'licensed')
            ->whereNull('licensed_at')
            ->update(['licensed_at' => DB::raw('COALESCE(receipt_approved_at, updated_at, created_at)')]);
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('licensed_at');
        });
    }
};
