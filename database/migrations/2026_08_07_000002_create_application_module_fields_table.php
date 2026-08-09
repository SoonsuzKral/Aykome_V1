<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_module_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_module_id')->constrained()->cascadeOnDelete();
            $table->string('field_name', 100);
            $table->enum('field_type', [
                'text', 'textarea', 'number', 'decimal', 'select', 'multiselect',
                'checkbox', 'radio', 'file', 'date', 'datetime', 'email', 'phone', 'address'
            ]);
            $table->string('label', 255);
            $table->string('placeholder', 255)->nullable();
            $table->text('default_value')->nullable();
            $table->text('help_text')->nullable();
            $table->json('field_options')->nullable();
            $table->json('validation_rules')->nullable();
            $table->enum('width', ['full', 'half', 'third'])->default('full');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['application_module_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_module_fields');
    }
};
