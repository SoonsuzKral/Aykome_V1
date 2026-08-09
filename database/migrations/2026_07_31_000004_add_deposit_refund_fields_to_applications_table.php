<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dateTime('deposit_refunded_at')->nullable()->after('deposit_receipt_info');
            $table->string('deposit_refund_doc')->nullable()->after('deposit_refunded_at');
            $table->boolean('is_deposit_refunded')->default(false)->after('deposit_refund_doc');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['deposit_refunded_at', 'deposit_refund_doc', 'is_deposit_refunded']);
        });
    }
};
