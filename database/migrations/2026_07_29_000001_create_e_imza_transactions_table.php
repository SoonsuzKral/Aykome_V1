<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('e_imza_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('pdf_type', 50);
            $table->enum('status', ['pending', 'completed', 'expired', 'cancelled'])->default('pending');
            $table->string('transaction_id', 100)->unique();
            $table->string('token', 255);
            $table->string('orijinal_pdf', 255)->nullable();
            $table->string('imzali_pdf', 255)->nullable();
            $table->json('imzalayan_info')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('e_imza_istemcileri', function (Blueprint $table) {
            $table->id();
            $table->string('api_key', 100)->unique();
            $table->string('label', 255)->nullable();
            $table->timestamp('son_erisim')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_imza_istemcileri');
        Schema::dropIfExists('e_imza_transactions');
    }
};
