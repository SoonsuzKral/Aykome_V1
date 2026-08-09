<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('process_steps', 'signature_config')) {
            Schema::table('process_steps', function (Blueprint $table) {
                $table->longText('signature_config')->nullable()->after('approval_config');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('process_steps', 'signature_config')) {
            Schema::table('process_steps', function (Blueprint $table) {
                $table->dropColumn('signature_config');
            });
        }
    }
};
