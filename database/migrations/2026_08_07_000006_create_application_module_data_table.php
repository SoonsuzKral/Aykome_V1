<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_module_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_module_id')->constrained()->cascadeOnDelete();
            $table->string('field_name', 100);
            $table->text('field_value')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->string('original_name', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->integer('file_size')->nullable();
            $table->timestamps();

            $table->unique(['application_id', 'application_module_id', 'field_name']);
            $table->index(['application_id', 'application_module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_module_data');
    }
};
