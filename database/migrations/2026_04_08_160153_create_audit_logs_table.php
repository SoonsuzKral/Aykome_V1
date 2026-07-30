<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name', 191)->nullable();        // denormalized — survives user deletion
            $table->string('user_role', 64)->nullable();         // role at time of action
            $table->string('action', 128)->index();              // tckn.query, receipt.approve, auth.login …
            $table->string('subject_type', 128)->nullable();     // Application, User, License …
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description', 512);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();       // immutable — no updated_at
            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
