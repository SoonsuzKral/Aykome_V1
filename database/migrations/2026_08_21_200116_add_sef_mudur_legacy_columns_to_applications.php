<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->unsignedBigInteger('sef_approved_by')->nullable()->after('vice_mayor_approved_at');
            $table->timestamp('sef_approved_at')->nullable()->after('sef_approved_by');
            $table->unsignedBigInteger('mudur_approved_by')->nullable()->after('sef_approved_at');
            $table->timestamp('mudur_approved_at')->nullable()->after('mudur_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'sef_approved_by',
                'sef_approved_at',
                'mudur_approved_by',
                'mudur_approved_at',
            ]);
        });
    }
};
