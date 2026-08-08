<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_modules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug', 100)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('icon', 100)->default('📋');
            $table->string('color', 20)->default('#6366F1');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('slug');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_modules');
    }
};
