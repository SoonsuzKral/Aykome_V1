<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->float('last_seen_lat', 10, 7)->nullable()->after('current_lng');
            $table->float('last_seen_lng', 10, 7)->nullable()->after('last_seen_lat');
            $table->timestamp('last_seen_at')->nullable()->after('last_seen_lng');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_seen_lat', 'last_seen_lng', 'last_seen_at']);
        });
    }
};
