<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->string('tesis_sorumlusu_adi', 255)->nullable()->after('manager_name');
            $table->string('mudur_adi', 255)->nullable()->after('tesis_sorumlusu_adi');
            $table->string('mudur_unvani', 255)->nullable()->after('mudur_adi');
        });

        // Mevcut engineer_name → tesis_sorumlusu_adi, manager_name → mudur_adi veri taşıma
        DB::table('institutions')->update([
            'tesis_sorumlusu_adi' => DB::raw('engineer_name'),
            'mudur_adi' => DB::raw('manager_name'),
        ]);
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn(['tesis_sorumlusu_adi', 'mudur_adi', 'mudur_unvani']);
        });
    }
};
