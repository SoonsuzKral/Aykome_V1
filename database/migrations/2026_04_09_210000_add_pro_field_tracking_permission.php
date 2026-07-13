<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $perms = [
            'pro.field_tracking',   // Saha check-in / GPS takip PRO özelliği
            'pro.advanced_reports', // Gelişmiş Rapor Motoru PRO özelliği (seeder çalışmayan ortamlar için güvenli ekleme)
            'pro.evrak_tevdi',      // Evrak & Tevdi (E-Belge) PRO özelliği
        ];

        foreach ($perms as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', [
            'pro.field_tracking',
            'pro.advanced_reports',
            'pro.evrak_tevdi',
        ])->delete();
    }
};
