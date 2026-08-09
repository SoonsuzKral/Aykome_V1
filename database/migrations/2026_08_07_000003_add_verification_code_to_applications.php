<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('verification_code', 20)->unique()->nullable();
        });

        // Eski kayıtları benzersiz doğrulama kodlarıyla backfill et (Migration patlamasın).
        $ids = DB::table('applications')->whereNull('verification_code')->pluck('id');
        foreach ($ids as $id) {
            DB::table('applications')
                ->where('id', $id)
                ->whereNull('verification_code')
                ->update([
                    'verification_code' => 'EYYB-' . Str::upper(Str::random(10)),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropUnique(['verification_code']);
            $table->dropColumn('verification_code');
        });
    }
};
