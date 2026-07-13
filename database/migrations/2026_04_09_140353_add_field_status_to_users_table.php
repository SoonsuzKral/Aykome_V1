<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_on_field')->default(false)->after('is_active');
            $table->float('current_lat', 10, 6)->nullable()->after('is_on_field');
            $table->float('current_lng', 10, 6)->nullable()->after('current_lat');
            $table->timestamp('field_started_at')->nullable()->after('current_lng');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_on_field', 'current_lat', 'current_lng', 'field_started_at']);
        });
    }
};
