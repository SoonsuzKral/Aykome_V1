<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('tesis_sorumlusu', 255)->nullable()->after('vice_mayor_name');
            $table->string('mudur_adi', 255)->nullable()->after('tesis_sorumlusu');
            $table->string('mudur_unvani', 255)->nullable()->after('mudur_adi');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['tesis_sorumlusu', 'mudur_adi', 'mudur_unvani']);
        });
    }
};
