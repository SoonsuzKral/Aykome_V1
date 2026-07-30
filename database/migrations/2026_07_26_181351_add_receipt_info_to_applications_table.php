<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('ztb_receipt_info')->nullable()->after('receipt_approved_by');
            $table->string('deposit_receipt_info')->nullable()->after('ztb_receipt_info');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['ztb_receipt_info', 'deposit_receipt_info']);
        });
    }
};
