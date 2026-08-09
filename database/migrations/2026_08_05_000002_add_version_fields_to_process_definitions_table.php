<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_definitions', function (Blueprint $table) {
            // Versioning
            $table->unsignedInteger('version')->default(1)->after('is_default');
            $table->string('status', 20)->default('draft')->after('version');
            // draft | published | archived

            // When this version was published
            $table->timestamp('published_at')->nullable()->after('status');

            // Canvas connection data (edges as JSON)
            $table->longText('canvas_connections')->nullable()->after('published_at');
            // Structure: [{ "from_step_id": 1, "to_step_id": 2 }, ...]

            // Who can initiate this workflow (role/personnel constraints)
            $table->longText('initiator_config')->nullable()->after('canvas_connections');
        });
    }

    public function down(): void
    {
        Schema::table('process_definitions', function (Blueprint $table) {
            $table->dropColumn(['version', 'status', 'published_at', 'canvas_connections', 'initiator_config']);
        });
    }
};
