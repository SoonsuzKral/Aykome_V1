<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\License;
use App\Models\SurfaceType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AykomeSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Başvurular
            'applications.view',
            'applications.create',
            'applications.edit',
            'applications.delete',
            'applications.approve_price',
            'applications.approve_receipt',
            'applications.issue_license',
            'applications.approve_pre_excavation',
            'tasks.transfer',
            'licenses.manage',
            'surface_types.manage',
            // Kurumlar & Kullanıcılar
            'institutions.manage',
            'users.manage',
            // Sistem (Süper Admin)
            'system.license',
            'system.logs',
            'system.settings',
            // PRO Modüller — 6 satılabilir lisans kapısı
            'pro.live_map',
            'pro.field_tracking',
            'pro.work_orders',
            'pro.advanced_reports',
            'pro.field_reports',
            'pro.evrak_tevdi',
            // Saha
            'field.tasks_view',
            'field.upload_media',
            'field.upload',   // geriye dönük uyumluluk
        ];

        foreach ($permissions as $name) {
            Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // ─────────────────────────────────────────────────────────────────────
        // 3 KATMANLI ROL HİYERARŞİSİ
        //
        // KATMAN 1 — Platform (HGB Bilişim Super Admin)
        //   super-admin        → Tüm firmalara, lisanslara, kullanıcılara tam erişim.
        //                        Dashboard: dashboard-superadmin (lisans & firma özeti)
        //
        // KATMAN 2 — Admin (Belediye / Kurum Yöneticileri)
        //   municipality-admin → Kendi belediyesini tam yönetir. Fiyat/makbuz/ruhsat onayı.
        //   municipality-staff → Başvuru oluşturur & onaylar. Kullanıcı yönetemez.
        //   institution-manager→ TEDAŞ/ŞUSKİ yöneticisi. Kendi kurumunu tam yönetir.
        //
        // KATMAN 3 — Saha / Alt Kullanıcı
        //   institution-staff  → Sadece başvuru oluşturur & düzenler.
        //   field-team         → Atanmış görevleri görür, fotoğraf yükler.
        //                        Dashboard: dashboard-field (basitleştirilmiş widget)
        // ─────────────────────────────────────────────────────────────────────

        // KATMAN 1
        $superAdmin = Role::query()->firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        // KATMAN 2
        $municipalityAdmin = Role::query()->firstOrCreate(['name' => 'municipality-admin', 'guard_name' => 'web']);
        $municipalityStaff = Role::query()->firstOrCreate(['name' => 'municipality-staff', 'guard_name' => 'web']);
        $institutionManager = Role::query()->firstOrCreate(['name' => 'institution-manager', 'guard_name' => 'web']);

        // KATMAN 3
        $institutionStaff = Role::query()->firstOrCreate(['name' => 'institution-staff', 'guard_name' => 'web']);
        $fieldTeam = Role::query()->firstOrCreate(['name' => 'field-team', 'guard_name' => 'web']);

        $municipalityAdmin->syncPermissions([
            'applications.view', 'applications.create', 'applications.edit',
            'applications.approve_pre_excavation', 'applications.approve_price', 'applications.approve_receipt', 'applications.issue_license',
            'tasks.transfer', 'surface_types.manage', 'users.manage', 'institutions.manage',
            // PRO Modüller — belediye yöneticisi tam yetkili
            'pro.live_map', 'pro.work_orders', 'pro.advanced_reports',
        ]);

        $municipalityStaff->syncPermissions([
            'applications.view', 'applications.create', 'applications.edit',
            'applications.approve_pre_excavation', 'applications.approve_price', 'applications.approve_receipt',
            'tasks.transfer',
        ]);

        $institutionManager->syncPermissions([
            'applications.view', 'applications.create', 'applications.edit', 'applications.delete',
        ]);

        $institutionStaff->syncPermissions([
            'applications.view', 'applications.create', 'applications.edit',
        ]);

        $fieldTeam->syncPermissions([
            'applications.view',
            'field.tasks_view',
            'field.upload_media',
            'field.upload',   // geriye dönük uyumluluk
        ]);

        $superAdmin->syncPermissions(Permission::query()->pluck('name'));

        $belediye = Institution::query()->firstOrCreate(
            ['slug' => 'belediye'],
            [
                'name' => 'Merkez Belediye',
                'color_code' => '#16A34A',
                'is_municipality' => true,
            ]
        );

        $tedas = Institution::query()->firstOrCreate(
            ['slug' => 'tedas'],
            ['name' => 'TEDAŞ', 'color_code' => '#DC2626', 'is_municipality' => false]
        );

        $suski = Institution::query()->firstOrCreate(
            ['slug' => 'suski'],
            ['name' => 'ŞUSKİ', 'color_code' => '#2563EB', 'is_municipality' => false]
        );

        Institution::query()->firstOrCreate(
            ['slug' => 'aksa'],
            ['name' => 'AKSA', 'color_code' => '#EA580C', 'is_municipality' => false]
        );

        SurfaceType::query()->firstOrCreate(
            ['name' => 'Asfalt'],
            ['price_per_m2' => 100, 'active' => true, 'color_code' => '#374151']
        );

        SurfaceType::query()->firstOrCreate(
            ['name' => 'Beton'],
            ['price_per_m2' => 150, 'active' => true, 'color_code' => '#9CA3AF']
        );

        SurfaceType::query()->firstOrCreate(
            ['name' => 'Beton Parke'],
            ['price_per_m2' => 85, 'active' => true, 'color_code' => '#D97706']
        );

        SurfaceType::query()->firstOrCreate(
            ['name' => 'Ham Toprak'],
            ['price_per_m2' => 40, 'active' => true, 'color_code' => '#92400E']
        );

        SurfaceType::query()->firstOrCreate(
            ['name' => 'Kilit Taşı'],
            ['price_per_m2' => 70, 'active' => true, 'color_code' => '#6B7280']
        );

        License::query()->firstOrCreate(
            ['license_key' => 'AYKOME-DEMO-LICENSE'],
            [
                'owner_name' => 'HGB Bilişim Demo Kurum',
                'valid_from' => now()->subYear(),
                'valid_until' => now()->addYears(5),
                'is_active' => true,
            ]
        );

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@aykome.local'],
            [
                'name' => 'Süper Admin',
                'password' => Hash::make('password'),
                'institution_id' => $belediye->id,
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['super-admin']);

        $mAdmin = User::query()->firstOrCreate(
            ['email' => 'belediye@aykome.local'],
            [
                'name' => 'Belediye Yöneticisi',
                'password' => Hash::make('password'),
                'institution_id' => $belediye->id,
                'email_verified_at' => now(),
            ]
        );
        $mAdmin->syncRoles(['municipality-admin']);

        $kurum = User::query()->firstOrCreate(
            ['email' => 'tedas@aykome.local'],
            [
                'name' => 'TEDAŞ Personeli',
                'password' => Hash::make('password'),
                'institution_id' => $tedas->id,
                'email_verified_at' => now(),
            ]
        );
        $kurum->syncRoles(['institution-staff']);

        $saha = User::query()->firstOrCreate(
            ['email' => 'saha@aykome.local'],
            [
                'name' => 'Saha Kontrolörü',
                'password' => Hash::make('password'),
                'institution_id' => $belediye->id,
                'email_verified_at' => now(),
            ]
        );
        $saha->syncRoles(['field-team']);
    }
}
