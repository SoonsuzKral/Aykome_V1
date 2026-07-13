<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('applications', 'tc_no') || ! Schema::hasColumn('applications', 'identity_no')) {
            return;
        }

        DB::table('applications')
            ->whereNull('tc_no')
            ->orWhereNull('identity_no')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    $normalized = preg_replace('/\D+/', '', (string) ($row->applicant_national_id ?? '')) ?: null;

                    DB::table('applications')
                        ->where('id', $row->id)
                        ->update([
                            'tc_no' => $row->tc_no ?: $normalized,
                            'identity_no' => $row->identity_no ?: $normalized,
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('applications', 'tc_no') || ! Schema::hasColumn('applications', 'identity_no')) {
            return;
        }

        DB::table('applications')->update([
            'tc_no' => null,
            'identity_no' => null,
        ]);
    }
};
