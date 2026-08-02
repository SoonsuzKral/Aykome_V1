<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_signatory_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institution_id')->nullable()->index();
            $table->string('document_type', 50)->index();
            $table->string('role_key', 50)->nullable()->index();
            $table->string('unvan', 255)->nullable();
            $table->string('ad_soyad', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->foreign('institution_id')->references('id')->on('institutions')->onDelete('cascade');
        });

        // Varsayılan imzacılar (Merkez/Global) — hard-coded isimler buradan yönetilir
        $now = now();
        $defaults = [
            ['document_type' => 'ruhsat', 'role_key' => 'aykome_sorumlusu', 'unvan' => 'AYKOME Birim Sorumlusu', 'ad_soyad' => 'Mahmut DOĞAN', 'sort' => 10],
            ['document_type' => 'ruhsat', 'role_key' => 'fen_isleri_muduru', 'unvan' => 'Fen İşleri Müdürü', 'ad_soyad' => 'Burak Bakır YÜCETEPE', 'sort' => 20],
            ['document_type' => 'metraj', 'role_key' => 'aykome_sorumlusu', 'unvan' => 'Aykome Birim Sorumlusu', 'ad_soyad' => 'Mahmut DOĞAN', 'sort' => 10],
            ['document_type' => 'pre_permit', 'role_key' => 'belediye_baskan_yardimcisi', 'unvan' => 'Belediye Başkan Yardımcısı', 'ad_soyad' => 'Mehmet ELĞÜN', 'sort' => 10],
        ];

        foreach ($defaults as $row) {
            DB::table('document_signatory_settings')->insert(array_merge($row, [
                'institution_id' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_signatory_settings');
    }
};
