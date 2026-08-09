<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('process_steps', 'action_type')) {
            Schema::table('process_steps', function (Blueprint $table) {
                $table->string('action_type', 20)->default('onay')->after('signature_config');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('process_steps', 'action_type')) {
            Schema::table('process_steps', function (Blueprint $table) {
                $table->dropColumn('action_type');
            });
        }
    }
};
