<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('applicant_national_id', 11)->nullable()->change();
            $table->string('tc_no', 11)->nullable()->change();
            $table->string('identity_no', 11)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('applicant_national_id', 11)->nullable(false)->change();
            $table->string('tc_no', 11)->nullable(false)->change();
            $table->string('identity_no', 11)->nullable(false)->change();
        });
    }
};
