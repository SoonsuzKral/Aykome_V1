<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('process_steps', 'module_permissions')) {
            Schema::table('process_steps', function (Blueprint $table) {
                $table->longText('module_permissions')->nullable()->after('approvable_modules');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('process_steps', 'module_permissions')) {
            Schema::table('process_steps', function (Blueprint $table) {
                $table->dropColumn('module_permissions');
            });
        }
    }
};
