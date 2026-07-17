<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE application_surface_areas MODIFY amount DECIMAL(18,2) DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE application_surface_areas MODIFY amount DECIMAL(14,2) DEFAULT 0');
    }
};
