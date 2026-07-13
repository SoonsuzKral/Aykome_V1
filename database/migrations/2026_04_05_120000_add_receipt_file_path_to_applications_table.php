<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('applications', 'receipt_file_path')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->string('receipt_file_path')->nullable()->after('license_document_path');
            });
        }

        if (! Schema::hasColumn('applications', 'payment_status')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->string('payment_status')->default('unpaid')->after('discovery_amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('applications', 'receipt_file_path')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->dropColumn('receipt_file_path');
            });
        }
    }
};
