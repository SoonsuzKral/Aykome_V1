<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('tesis_sorumlusu_adi', 255)->nullable()->after('mudur_unvani');
            $table->string('duzenleyen_kisi', 255)->nullable()->after('tesis_sorumlusu_adi');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['tesis_sorumlusu_adi', 'duzenleyen_kisi']);
        });
    }
};
