<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->decimal('deposit_amount', 12, 2)->nullable()->after('discovery_amount');
            $table->decimal('excavation_amount', 12, 2)->nullable()->after('deposit_amount');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['deposit_amount', 'excavation_amount']);
        });
    }
};
