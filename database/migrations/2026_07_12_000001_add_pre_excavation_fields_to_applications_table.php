<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->timestamp('pre_excavation_approved_at')->nullable()->after('receipt_approved_by');
            $table->foreignId('pre_excavation_approved_by')->nullable()->constrained('users')->nullOnDelete()->after('pre_excavation_approved_at');
            $table->string('pre_excavation_document_path', 512)->nullable()->after('pre_excavation_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['pre_excavation_approved_at', 'pre_excavation_approved_by', 'pre_excavation_document_path']);
        });
    }
};
