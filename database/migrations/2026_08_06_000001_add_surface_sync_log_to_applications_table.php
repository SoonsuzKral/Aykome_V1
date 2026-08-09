<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('applications', 'surface_sync_log')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->json('surface_sync_log')->nullable()->after('genel_toplam');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('applications', 'surface_sync_log')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->dropColumn('surface_sync_log');
            });
        }
    }
};
