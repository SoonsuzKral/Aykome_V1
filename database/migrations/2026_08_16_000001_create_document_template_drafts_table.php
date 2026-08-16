<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TAM_WORLD_YAPISI.md — Taslak Kütüphanesi (Şablon Kasası)
 * ---------------------------------------------------------
 * Kullanıcı isteği (16.08, 5. tur): "Yeni Word yüklediğimde eskisi neden
 * silinsin ki" — global/institution/application_module_templates tabloları
 * TEK bir satırı günceller (updateOrCreate); bu tablo ONLARIN YANINA, adı
 * verilebilen, birden fazla saklanabilen ("WORLD_PC" gibi) şablon SÜRÜMLERİNİ
 * tutar. Editördeki "📂 Taslak Kütüphanesi" panelinden seçilip AKTİF editöre
 * yüklenir; kullanıcı 💾 Kaydet'e basmadıkça asıl (aktif) şablon değişmez.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_template_drafts')) {
            Schema::create('document_template_drafts', function (Blueprint $table) {
                $table->id();
                // 'global' | 'institution' | 'application'
                $table->string('scope', 20);
                // institution_id / application_id — global için NULL
                $table->unsignedBigInteger('scope_id')->nullable();
                $table->string('document_type', 50);
                $table->string('name', 150);
                $table->longText('content_data');
                // 'manual' (elle/düzenlemeden kaydedildi) | 'word_import' (.docx'ten geldi)
                $table->string('source', 20)->default('manual');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['scope', 'scope_id', 'document_type'], 'doc_tpl_drafts_scope_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_template_drafts');
    }
};
