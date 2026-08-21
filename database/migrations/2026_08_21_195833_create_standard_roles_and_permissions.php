<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Süper Admin zaten mevcut (AykomeSeeder)

        // ─── ROLLER ─────────────────────────────────────────────────────
        $mudur = Role::firstOrCreate(['name' => 'mudur', 'guard_name' => 'web']);
        $sef = Role::firstOrCreate(['name' => 'sef', 'guard_name' => 'web']);
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $viceMayor = Role::firstOrCreate(['name' => 'vice_mayor', 'guard_name' => 'web']);
        $altKurum = Role::firstOrCreate(['name' => 'alt_kurum', 'guard_name' => 'web']);

        // ─── MÜDÜR (Fen İşleri Müdürü) ──────────────────────────────────
        $mudur->syncPermissions([
            'applications.view',
            'applications.edit',
            'applications.approve_pre_excavation',
            'applications.approve_price',
            'applications.issue_license',
            'reports.view',
            'reports.advanced',
            'processes.manage',
            'maps.view',
            'field.tasks_view',
        ]);

        // ─── ŞEF (Aykome Birim Şefi) ────────────────────────────────────
        $sef->syncPermissions([
            'applications.view',
            'applications.edit',
            'applications.approve_pre_excavation',
            'reports.view',
            'maps.view',
            'field.tasks_view',
            'field.upload_media',
        ]);

        // ─── BÜRO PERSONELİ ─────────────────────────────────────────────
        $staff->syncPermissions([
            'applications.view',
            'applications.create',
            'applications.edit',
            'applications.approve_pre_excavation',
            'reports.view',
            'maps.view',
            'field.tasks_view',
            'field.upload_media',
            'extra-permits.view',
        ]);

        // ─── BAŞKAN YARDIMCISI ──────────────────────────────────────────
        $viceMayor->syncPermissions([
            'applications.view',
            'makam.view',
            'reports.view',
            'maps.view',
        ]);

        // ─── ALT KURUM (Dış kurum kullanıcıları) ─────────────────────────
        $altKurum->syncPermissions([
            'applications.view',
            'maps.view',
        ]);
    }

    public function down(): void
    {
        Role::whereIn('name', ['mudur', 'sef', 'staff', 'vice_mayor', 'alt_kurum'])->delete();
    }
};
