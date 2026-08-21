<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // ─── ESKİ İNGİLİZCE ROLLERİ SİL ──────────────────────────────────
        Role::whereIn('name', ['staff', 'vice_mayor'])->delete();

        // ─── YENİ TÜRKÇE ROLLER ─────────────────────────────────────────
        // NOT: mudur, sef, alt_kurum zaten Türkçe — sadece staff ve vice_mayor değişiyor
        $mudur    = Role::firstOrCreate(['name' => 'mudur',            'guard_name' => 'web']);
        $sef      = Role::firstOrCreate(['name' => 'sef',              'guard_name' => 'web']);
        $buro     = Role::firstOrCreate(['name' => 'buro_personeli',   'guard_name' => 'web']);
        $baskanYr = Role::firstOrCreate(['name' => 'baskan_yardimcisi','guard_name' => 'web']);
        $altKurum = Role::firstOrCreate(['name' => 'alt_kurum',        'guard_name' => 'web']);

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
        $buro->syncPermissions([
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
        $baskanYr->syncPermissions([
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

        // ─── PROCESS_STEP role_key'lerini güncelle ────────────────────────
        DB::table('process_steps')
            ->where('role_key', 'staff')
            ->update(['role_key' => 'buro_personeli']);
        DB::table('process_steps')
            ->where('role_key', 'vice_mayor')
            ->update(['role_key' => 'baskan_yardimcisi']);
    }

    public function down(): void
    {
        // Geri al: Türkçe isimleri İngilizceye çevir
        DB::table('process_steps')
            ->where('role_key', 'buro_personeli')
            ->update(['role_key' => 'staff']);
        DB::table('process_steps')
            ->where('role_key', 'baskan_yardimcisi')
            ->update(['role_key' => 'vice_mayor']);

        Role::whereIn('name', ['buro_personeli', 'baskan_yardimcisi'])->delete();

        // Eski isimleri geri oluştur
        Role::firstOrCreate(['name' => 'staff',        'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'vice_mayor',   'guard_name' => 'web']);
    }
};
