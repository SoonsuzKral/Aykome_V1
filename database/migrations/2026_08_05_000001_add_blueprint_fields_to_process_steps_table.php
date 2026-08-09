<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_steps', function (Blueprint $table) {
            // Personnel assigned to this step (user IDs)
            $table->longText('personnel_ids')->nullable()->after('approvable_modules');

            // Which modules this step can VIEW (not just approve)
            $table->longText('visibility_config')->nullable()->after('personnel_ids');

            // Approval constraints: who can approve, approval mode
            $table->longText('approval_config')->nullable()->after('visibility_config');

            // Canvas position for blueprint editor
            $table->unsignedInteger('canvas_x')->default(100)->after('is_active');
            $table->unsignedInteger('canvas_y')->default(100)->after('canvas_x');
        });
    }

    public function down(): void
    {
        Schema::table('process_steps', function (Blueprint $table) {
            $table->dropColumn(['personnel_ids', 'visibility_config', 'approval_config', 'canvas_x', 'canvas_y']);
        });
    }
};
