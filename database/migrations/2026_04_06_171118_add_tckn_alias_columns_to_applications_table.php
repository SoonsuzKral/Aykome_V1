<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (! Schema::hasColumn('applications', 'tc_no')) {
                $table->string('tc_no', 11)->nullable()->after('applicant_national_id');
            }

            if (! Schema::hasColumn('applications', 'identity_no')) {
                $table->string('identity_no', 11)->nullable()->after('tc_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('applications', 'identity_no')) {
                $dropColumns[] = 'identity_no';
            }

            if (Schema::hasColumn('applications', 'tc_no')) {
                $dropColumns[] = 'tc_no';
            }

            if (! empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
