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
            $table->string('deposit_status', 20)->default('pending')->after('is_deposit_refunded');
            $table->text('deposit_refund_notes')->nullable()->after('deposit_status');
        });

        // Mevcut iade edilmiş kayıtları yeni durum alanına backfill et.
        DB::table('applications')
            ->where('is_deposit_refunded', true)
            ->update(['deposit_status' => 'refunded']);
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['deposit_status', 'deposit_refund_notes']);
        });
    }
};
