<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('color_code');
            $table->string('engineer_name')->nullable()->after('logo_path');
            $table->string('manager_name')->nullable()->after('engineer_name');
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'engineer_name', 'manager_name']);
        });
    }
};
