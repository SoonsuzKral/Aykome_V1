<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EBYS Taslak Motoru — Başvuru Bazlı (Override) Belge Şablonları
 * --------------------------------------------------------------
 * "Bu Başvuruya Özel Taslağı Düzenle" butonu bu tabloya yazar.
 * (application_id, document_type) çifti benzersizdir; başvuru silinince
 * override kayıtları da silinir (cascade).
 * Final PDF çiziminde override varsa ondan çizilir, yoksa global'e düşer.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('application_document_overrides')) {
            Schema::create('application_document_overrides', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
                $table->string('document_type', 50);
                $table->longText('content_data')->nullable();
                $table->string('editor_type', 10)->default('word');
                $table->timestamps();

                $table->unique(['application_id', 'document_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('application_document_overrides');
    }
};
