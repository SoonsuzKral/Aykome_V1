<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kurum bazlı (institution) şablon desteği.
 * ------------------------------------------------------
 * Alt kurumlar (AKSA, Dicle Elektrik vb.) kendi "Üst Yazı" şablonunu düzenler;
 * her kurumun düzenlemesi yalnızca kendi kurumunun başvurularında geçerli olur.
 * global_document_templates (global) tablosuna dokunmaz — ayrı tablo güvenlidir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('institution_document_templates')) {
            Schema::create('institution_document_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
                $table->string('document_type', 50);
                $table->longText('content_data')->nullable();
                $table->string('editor_type', 10)->default('word');
                $table->timestamps();

                $table->unique(['institution_id', 'document_type'], 'inst_doc_tpl_inst_type_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_document_templates');
    }
};
