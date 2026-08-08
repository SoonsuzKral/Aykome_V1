<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_module_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_module_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 100);
            $table->string('template_name', 255);
            $table->longText('content_data')->nullable();
            $table->enum('editor_type', ['word', 'excel', 'contenteditable'])->default('contenteditable');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['application_module_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_module_templates');
    }
};
