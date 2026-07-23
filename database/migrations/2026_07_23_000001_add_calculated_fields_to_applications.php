<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->decimal('kdv_amount', 14, 2)->nullable()->after('excavation_amount');
            $table->decimal('ruhsat_harci', 14, 2)->nullable()->after('kdv_amount');
            $table->decimal('kesif_bedeli', 14, 2)->nullable()->after('ruhsat_harci');
            $table->decimal('ztb_toplam', 14, 2)->nullable()->after('kesif_bedeli');
            $table->decimal('teminat_tutari', 14, 2)->nullable()->after('ztb_toplam');
            $table->decimal('genel_toplam', 14, 2)->nullable()->after('teminat_tutari');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['kdv_amount', 'ruhsat_harci', 'kesif_bedeli', 'ztb_toplam', 'teminat_tutari', 'genel_toplam']);
        });
    }
};
