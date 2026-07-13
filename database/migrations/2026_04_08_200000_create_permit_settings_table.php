<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permit_settings', function (Blueprint $table) {
            $table->id();

            // Kurum kimliği
            $table->string('institution_name',   255)->nullable()->comment('Belediye / kurum tam adı');
            $table->string('institution_address',512)->nullable()->comment('Kurum adresi (PDF altlık)');
            $table->string('institution_logo_path', 512)->nullable()->comment('Logo — storage/app/public/permit/*');

            // Yetkili kişi
            $table->string('director_name',  191)->nullable()->comment('Yetkili müdür ad soyad');
            $table->string('director_title', 191)->nullable()->comment('Unvan (ör. Belediye Başkanı)');
            $table->string('director_signature_path', 512)->nullable()->comment('İmza resmi dosya yolu');
            $table->string('municipality_stamp_path',  512)->nullable()->comment('Mühür / kaşe dosya yolu');

            // Belge metni
            $table->text('validity_agreement')->nullable()->comment('Ruhsat geçerlilik şartları metni');
            $table->string('footer_note', 512)->nullable()->comment('Alt bilgi notu');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permit_settings');
    }
};
