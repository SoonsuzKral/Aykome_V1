<?php

namespace Database\Seeders;

use App\Models\ProcessDefinition;
use App\Models\ProcessStep;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Süreç & Onay Rotası — Hiyerarşi Rolleri + Varsayılan Silsile
 * -------------------------------------------------------------
 * "Sabit işleyiş" yerine rol hiyerarşisi kurar:
 *   municipality-buro  → Büro Personeli (ön kazıya onay verir)
 *   municipality-sef   → Aykome Şefi (metraj onaylar)
 *   municipality-mudur → Fen İşleri Müdürü (ruhsat iznine onay verir)
 *   municipality-makam → Başkan / Başkan Yrd. (en yetkili makam)
 *
 * Varsayılan süreç, legacy 3 aşamalı akışı (staff/director/vice_mayor)
 * birebir korur; böylece mevcut başvurular ve kolonlar kesintisiz çalışır.
 */
class ProcessFlowSeeder extends Seeder
{
    public function run(): void
    {
        $municipalityPerms = [
            'applications.view', 'applications.create', 'applications.edit',
            'applications.approve_pre_excavation', 'applications.approve_price', 'applications.approve_receipt',
            'tasks.transfer',
        ];
        $adminPerms = array_merge($municipalityPerms, [
            'applications.issue_license', 'applications.delete',
            'surface_types.manage', 'users.manage', 'institutions.manage',
            'users.view_all_scoped',
            'reports.view', 'reports.advanced',
            'document-settings.manage', 'document-templates.manage',
            'processes.manage', 'processes.blueprint',
            'makam.view', 'extra-permits.view',
            'deposits.view', 'deposits.manage',
            'faults.view', 'faults.manage',
            'pro.live_map', 'pro.work_orders', 'pro.advanced_reports',
            'pro.field_tracking', 'pro.field_reports', 'pro.evrak_tevdi',
        ]);

        // 1. Hiyerarşi rolleri
        $buro  = Role::query()->firstOrCreate(['name' => 'municipality-buro', 'guard_name' => 'web']);
        $sef   = Role::query()->firstOrCreate(['name' => 'municipality-sef', 'guard_name' => 'web']);
        $mudur = Role::query()->firstOrCreate(['name' => 'municipality-mudur', 'guard_name' => 'web']);
        $makam = Role::query()->firstOrCreate(['name' => 'municipality-makam', 'guard_name' => 'web']);

        $buro->syncPermissions($municipalityPerms);
        $sef->syncPermissions(array_merge($municipalityPerms, [
            'applications.issue_license', 'surface_types.manage',
            'users.view_all_scoped',
        ]));
        $mudur->syncPermissions($adminPerms);
        $makam->syncPermissions($adminPerms);

        // 2. Varsayılan süreç
        $process = ProcessDefinition::query()->firstOrCreate(
            ['slug' => 'on-kazi-onay-silsilesi'],
            [
                'name' => 'Ön Kazı Onay Silsilesi',
                'description' => 'Büro Personeli → Aykome Şefi → Fen İşleri Müdürü / Makam. Belediye merkez yönetimi tarafından tanımlanır.',
                'is_active' => true,
                'is_default' => true,
                'created_by' => null,
            ]
        );

        ProcessDefinition::query()
            ->where('slug', '!=', $process->slug)
            ->update(['is_default' => false]);
        $process->update(['is_active' => true, 'is_default' => true]);

        $steps = [
            [
                'name' => 'Büro Personeli Onayı',
                'role_key' => 'staff',
                'roles' => ['municipality-staff', 'municipality-buro'],
                'approvable_modules' => ['pre_excavation', 'metraj'],
            ],
            [
                'name' => 'Aykome Şefi Onayı',
                'role_key' => 'director',
                'roles' => ['municipality-sef', 'municipality-admin'],
                'approvable_modules' => ['pre_excavation', 'metraj', 'ruhsat'],
            ],
            [
                'name' => 'Fen İşleri Müdürü / Makam Onayı',
                'role_key' => 'vice_mayor',
                'roles' => ['municipality-mudur', 'municipality-makam', 'municipality-admin', 'super-admin'],
                'approvable_modules' => ['pre_excavation', 'ruhsat'],
            ],
        ];

        $process->steps()->delete();

        foreach ($steps as $order => $step) {
            ProcessStep::query()->create(array_merge($step, [
                'process_definition_id' => $process->id,
                'step_order' => $order + 1,
                'is_active' => true,
            ]));
        }
    }
}
