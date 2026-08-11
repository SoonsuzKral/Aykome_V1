<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kurum & İmza Yetkili Bilgileri — iş hiyerarşisine uygun yeni kolonlar.
     * Eski kolonlar (tesis_sorumlusu_adi, duzenleyen_kisi, mudur_adi vb.) korunur;
     * yeni form bu kolonlara yazar, PDF hidrasyonu fallback sırasıyla eski tokenları
     * da besler. (Veritabanı tamamen Oracle'dır; after() desteklenmediği için kullanılmaz.)
     */
    public function up(): void
    {
        $fields = [
            'kazi_sorumlusu_ad_soyad',
            'kazi_sorumlusu_unvan',
            'kazi_sorumlusu_telefon',
            'kurum_ust_yoneticisi_ad_soyad',
            'kurum_ust_yoneticisi_unvan',
            'yaziyi_duzenleyen_ad_soyad',
            'yaziyi_duzenleyen_iletisim',
        ];

        Schema::table('applications', function (Blueprint $table) use ($fields) {
            foreach ($fields as $column) {
                if (! Schema::hasColumn('applications', $column)) {
                    $table->string($column, 255)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        $fields = [
            'kazi_sorumlusu_ad_soyad',
            'kazi_sorumlusu_unvan',
            'kazi_sorumlusu_telefon',
            'kurum_ust_yoneticisi_ad_soyad',
            'kurum_ust_yoneticisi_unvan',
            'yaziyi_duzenleyen_ad_soyad',
            'yaziyi_duzenleyen_iletisim',
        ];

        Schema::table('applications', function (Blueprint $table) use ($fields) {
            foreach ($fields as $column) {
                if (Schema::hasColumn('applications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
