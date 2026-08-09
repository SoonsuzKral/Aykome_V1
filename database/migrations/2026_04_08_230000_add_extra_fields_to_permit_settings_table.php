<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permit_settings', function (Blueprint $table) {
            // Ana daire başkanlığı adı (Fen İşleri vb.)
            $table->string('department_name', 255)->nullable()->after('institution_address');

            // Tanzim eden (belgeyi hazırlayan)
            $table->string('preparer_name',  191)->nullable()->after('director_title');
            $table->string('preparer_title', 191)->nullable()->after('preparer_name');
            $table->string('preparer_signature_path', 512)->nullable()->after('preparer_title');

            // Onaylayan yetkili
            $table->string('approver_name',  191)->nullable()->after('preparer_signature_path');
            $table->string('approver_title', 191)->nullable()->after('approver_name');

            // Alt onay / ikinci imza
            $table->string('secondary_approver_name',  191)->nullable()->after('approver_title');
            $table->string('secondary_approver_title', 191)->nullable()->after('secondary_approver_name');
        });
    }

    public function down(): void
    {
        Schema::table('permit_settings', function (Blueprint $table) {
            $table->dropColumn([
                'department_name',
                'preparer_name', 'preparer_title', 'preparer_signature_path',
                'approver_name',  'approver_title',
                'secondary_approver_name', 'secondary_approver_title',
            ]);
        });
    }
};
