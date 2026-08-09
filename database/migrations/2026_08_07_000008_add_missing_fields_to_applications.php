<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Oracle'a eksik sütunları ekle
        Schema::connection('oracle')->table('applications', function (Blueprint $table) {
            if (!Schema::connection('oracle')->hasColumn('applications', 'tesis_sorumlusu_adi')) {
                $table->string('tesis_sorumlusu_adi', 100)->nullable()->after('tesis_sorumlusu');
            }
            if (!Schema::connection('oracle')->hasColumn('applications', 'duzenleyen_kisi')) {
                $table->string('duzenleyen_kisi', 100)->nullable()->after('tesis_sorumlusu_adi');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('oracle')->table('applications', function (Blueprint $table) {
            if (Schema::connection('oracle')->hasColumn('applications', 'duzenleyen_kisi')) {
                $table->dropColumn('duzenleyen_kisi');
            }
            if (Schema::connection('oracle')->hasColumn('applications', 'tesis_sorumlusu_adi')) {
                $table->dropColumn('tesis_sorumlusu_adi');
            }
        });
    }
};
