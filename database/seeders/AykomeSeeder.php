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
            // Teminat & İadeler
            'deposits.view',
            'deposits.manage',
            // Toplu Arıza Yönetimi (Acil Kazı)
            'faults.view',
            'faults.manage',
            // Ek İzinler (Ekstra Kazı İzinleri)
            'extra-permits.view',
            // Kurumlar & Kullanıcılar
            'institutions.manage',
            'users.manage',
            // Sistem (Süper Admin)
            'system.license',
            'system.logs',
            'system.settings',
            // Raporlar
            'reports.view',
            'reports.advanced',
            // Belge & Şablon Yönetimi
            'document-settings.manage',
            'document-templates.manage',
            // Süreç & Onay Rotası
            'processes.manage',
            'processes.blueprint',
            // Makam Masası
            'makam.view',
            // PRO Modüller — 6 satılabilir lisans kapısı
            'pro.live_map',
            'pro.field_tracking',
            'pro.work_orders',
            'pro.advanced_reports',
            'pro.field_reports',
            'pro.evrak_tevdi',
            // Kullanıcı Görünürlük
            'users.view_all_scoped',
            // Saha
            'field.tasks_view',
            'field.upload_media',
            'field.upload',   // geriye dönük uyumluluk
            // Aykome Maps
            'maps.view',
            // Oracle Veritabanı (Super Admin)
            'oracle.manage',
        ];

        foreach ($permissions as $name) {
            Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // ─────────────────────────────────────────────────────────────────────
        // TEK ROL — super-admin (tüm permission'lar)
        // Diğer roller (municipality-admin, municipality-staff, institution-*,
        // field-team, municipality-buro/sef/mudur/makam) tamamen silindi.
        // ─────────────────────────────────────────────────────────────────────

        $superAdmin = Role::query()->firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
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
        $mAdmin->syncRoles(['super-admin']);

        $kurum = User::query()->firstOrCreate(
            ['email' => 'tedas@aykome.local'],
            [
                'name' => 'TEDAŞ Personeli',
                'password' => Hash::make('password'),
                'institution_id' => $tedas->id,
                'email_verified_at' => now(),
            ]
        );
        $kurum->syncRoles(['super-admin']);

        $saha = User::query()->firstOrCreate(
            ['email' => 'saha@aykome.local'],
            [
                'name' => 'Saha Kontrolörü',
                'password' => Hash::make('password'),
                'institution_id' => $belediye->id,
                'email_verified_at' => now(),
            ]
        );
        $saha->syncRoles(['super-admin']);
    }
}
