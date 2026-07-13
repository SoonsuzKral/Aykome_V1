<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pre_excavation_permit_settings', function (Blueprint $table) {
            $table->id();
            $table->string('title')->default('ÖN KAZI İZİN BELGESİ');
            $table->text('header_text')->nullable();
            $table->text('footer_text')->nullable();
            $table->json('sections')->nullable();
            $table->string('logo_path', 512)->nullable();
            $table->string('signature_path', 512)->nullable();
            $table->string('stamp_path', 512)->nullable();
            $table->string('approver_name', 191)->nullable();
            $table->string('approver_title', 191)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_excavation_permit_settings');
    }
};
