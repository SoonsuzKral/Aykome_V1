<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EK RUHSAT (Additional Permit) Mimarisi
 * -------------------------------------
 * Asıl başvurudan KLON üretilen ek ruhsat için:
 *   parent_id            → kendisiyle ilişkili asıl başvuru (self FK, nullable)
 *   is_additional_permit → boolean; true ise TEMİNAT kesilmez (daima 0)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('applications', 'parent_id')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('id');
                $table->boolean('is_additional_permit')->default(false)->after('parent_id');

                $table->foreign('parent_id')
                    ->references('id')
                    ->on('applications')
                    ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('applications', 'parent_id')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn(['parent_id', 'is_additional_permit']);
            });
        }
    }
};
