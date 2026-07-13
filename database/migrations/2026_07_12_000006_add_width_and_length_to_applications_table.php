<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->decimal('width_m', 12, 2)->nullable()->after('total_area_m2');
            $table->decimal('length_m', 12, 2)->nullable()->after('width_m');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['width_m', 'length_m']);
        });
    }
};
