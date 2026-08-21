<?php

namespace Database\Seeders;

use App\Models\ProcessDefinition;
use App\Models\ProcessStep;
use Illuminate\Database\Seeder;

/**
 * Süreç & Onay Rotası — Varsayılan Silsile
 * -------------------------------------------------------------
 * Roller (municipality-buro/sef/mudur/makam) tamamen silindi —
 * sadece super-admin kurttu. Süreç adımları hâlâ role_name listesi
 * taşır (veri olarak), ama bu rolleri DB'de yaratmaz.
 */
class ProcessFlowSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Varsayılan süreç — roller silindi (sadece super-admin kurttu)
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

        // ─── 2. Ödeme Üst Yazı Onay Süreci ────────────────────────────────
        // CLAUDE.md: "var olan bir sürecin (örn. on_kazi) İÇİNE farklı bir
        // modülün (örn. odeme_ust_yazi) adımlarını EKLEMEYİN". Bu yüzden
        // tamamen ayrı bir ProcessDefinition. COZUM_11B / COZUM_12 GÖREV 3.
        $odemeProcess = ProcessDefinition::query()->firstOrCreate(
            ['slug' => 'odeme-ust-yazi-onay-mio6'],
            [
                'name' => 'Ödeme Üst Yazı Onay Süreci',
                'description' => 'Ödeme Üst Yazı (e-imza) aşaması için ayrı, bağımsız süreç. on_kazi/pre_permit süreciyle paylaşmaz.',
                'is_active' => true,
                'is_default' => false,
                'created_by' => null,
            ]
        );
        $odemeProcess->update(['is_active' => true, 'is_default' => false]);

        $odemeSteps = [
            [
                'name' => 'Ödeme Üst Yazı — Müdür E-İmza',
                'role_key' => 'mudur',
                'roles' => ['mudur'],
                'approvable_modules' => ['odeme_ust_yazi'],
                'action_type' => 'e_imza',
                'signature_config' => ['pdf_type' => 'odeme_ust_yazi'],
                'approval_config' => ['mode' => 'any'],
                'canvas_x' => 100,
                'canvas_y' => 100,
            ],
            [
                'name' => 'Ödeme Üst Yazı — Bşk Yrd. E-İmza',
                'role_key' => 'vice_mayor',
                'roles' => ['vice_mayor'],
                'approvable_modules' => ['odeme_ust_yazi'],
                'action_type' => 'e_imza',
                'signature_config' => ['pdf_type' => 'odeme_ust_yazi'],
                'approval_config' => ['mode' => 'any'],
                'canvas_x' => 100,
                'canvas_y' => 100,
            ],
        ];

        $odemeProcess->steps()->delete();
        foreach ($odemeSteps as $order => $step) {
            ProcessStep::query()->create(array_merge($step, [
                'process_definition_id' => $odemeProcess->id,
                'step_order' => $order + 1,
                'is_active' => true,
            ]));
        }
    }
}
