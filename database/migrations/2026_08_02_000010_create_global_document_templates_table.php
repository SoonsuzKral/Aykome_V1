<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EBYS Taslak Motoru — Global (Master) Belge Şablonları
 * -----------------------------------------------------
 * Yönetici sidebar'daki "Taslak / Şablon Yönetimi" modülünden düzenler.
 * - cover_letter / on_kazi  => editor_type = word  (TinyMCE/CKEditor benzeri A4 editör)
 * - ruhsat / tahakkuk       => editor_type = excel (Jexcel hücre tablosu)
 * content_data: word için HTML fragment, excel için JSON (2D hücre matrisi).
 * Final PDF çiziminde öncelik: override > global > normal blade.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('global_document_templates')) {
            Schema::create('global_document_templates', function (Blueprint $table) {
                $table->id();
                $table->string('document_type', 50)->unique();
                $table->longText('content_data')->nullable();
                $table->string('editor_type', 10)->default('word');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('global_document_templates');
    }
};
