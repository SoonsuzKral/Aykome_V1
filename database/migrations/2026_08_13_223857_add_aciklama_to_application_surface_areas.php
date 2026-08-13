<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AYKOME 2 YIL KATI FİYAT KURALI: aynı adrese 2 yıl içinde tekrar kazı
 * yapılırsa "multiplier" (katı) alanı ile miktar çarpılır (örn. 5 katı).
 * 'multiplier' kolonu zaten tablo oluşturulurken vardı ama hiç kullanılmıyordu
 * ve kayıt bazlı açıklama (kurul kararı) alanı yoktu — bu migration ekliyor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_surface_areas', function (Blueprint $table) {
            if (! Schema::hasColumn('application_surface_areas', 'aciklama')) {
                $table->text('aciklama')->nullable()->after('multiplier');
            }
        });
    }

    public function down(): void
    {
        Schema::table('application_surface_areas', function (Blueprint $table) {
            if (Schema::hasColumn('application_surface_areas', 'aciklama')) {
                $table->dropColumn('aciklama');
            }
        });
    }
};
