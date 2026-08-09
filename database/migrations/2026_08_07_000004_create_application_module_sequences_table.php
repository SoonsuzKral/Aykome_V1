<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('application_module_sequences')) {
            return;
        }

        Schema::create('application_module_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_module_id')->constrained()->cascadeOnDelete();
            $table->string('application_type', 100);
            $table->integer('sort_order')->default(0);
            $table->json('config')->nullable();
            $table->timestamps();

            $table->unique(['application_module_id', 'application_type']);
            $table->index(['application_type', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_module_sequences');
    }
};
