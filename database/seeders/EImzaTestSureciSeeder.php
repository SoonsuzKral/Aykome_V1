<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\GisCizim;
use App\Models\GisCizimYolIliskisi;
use App\Models\Institution;
use App\Models\ProcessDefinition;
use App\Models\ProcessStep;
use App\Models\SurfaceType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * COZUM_08 GOREV 4 — "E-Imza Test Sureci (Komple)" adinda 4 adimli bir surec
 * tanimlar ve Dicle Elektrik alt kurumu adina, bu surece bagli, 3. adimda
 * (Fen Isleri Muduru e-imza) bekleyen bir test basvurusu olusturur.
 *
 * Adimlar: Buro Personeli onayi (onay) -> Birim Sefi parafi (paraf)
 *          -> Fen Isleri Muduru e-imza (On Kazi Izni) -> Baskan Yrd. e-imza (Ruhsat)
 *
 * Kullanim:
 *   php artisan db:seed --class=Database\\Seeders\\EImzaTestSureciSeeder
 *
 * Test kullanicilari (zaten DB'de mevcut, sifre bilinmiyorsa admin panelden
 * sifre sifirlanabilir):
 *   buro@test.com   (Mehmet Yilmaz  | municipality-buro)
 *   sef@test.com    (Ali Kaya       | municipality-sef)
 *   mudur@test.com  (Ayse Demir     | municipality-mudur)  <- Kamu SM/E-Tugra ile e-imza testi BURADAN baslar
 *   baskan@test.com (Ahmet Kaan Karatas | municipality-makam)
 */
class EImzaTestSureciSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Surec tanimi (mevcut varsayilan "on-kazi-onay-silsilesi" sureci
        //    ETKILENMEZ — bu tamamen ayri, is_default=false bir test surecidir).
        $process = ProcessDefinition::query()->updateOrCreate(
            ['slug' => 'e-imza-test-sureci-komple'],
            [
                'name' => 'E-İmza Test Süreci (Komple)',
                'description' => 'COZUM_08 GÖREV 4 — Büro Personeli onayı -> Birim Şefi parafı -> '
                    . 'Fen İşleri Müdürü e-imza (Ön Kazı İzni) -> Başkan Yrd. e-imza (Ruhsat). '
                    . 'Kamu SM + E-Tuğra tokenleriyle uçtan uca e-imza testi için.',
                'is_active' => true,
                'is_default' => false,
                'created_by' => null,
            ]
        );

        $process->steps()->delete();

        $steps = [
            [
                'name' => 'Büro Personeli Onayı',
                'role_key' => 'staff',
                'roles' => ['municipality-staff', 'municipality-buro'],
                'approvable_modules' => ['pre_excavation', 'metraj'],
                'action_type' => 'onay',
            ],
            [
                'name' => 'Birim Şefi Parafı',
                'role_key' => 'sef',
                'roles' => ['municipality-sef'],
                'approvable_modules' => ['metraj', 'ruhsat'],
                'action_type' => 'paraf',
            ],
            [
                'name' => 'Fen İşleri Müdürü E-İmza (Ön Kazı İzni)',
                'role_key' => 'mudur',
                'roles' => ['municipality-mudur'],
                'approvable_modules' => ['pre_excavation'],
                'action_type' => 'e_imza',
                'signature_config' => ['pdf_type' => 'pre_permit'],
            ],
            [
                'name' => 'Başkan Yrd. E-İmza (Ruhsat)',
                'role_key' => 'vice_mayor',
                'roles' => ['municipality-makam'],
                'approvable_modules' => ['ruhsat'],
                'action_type' => 'e_imza',
                'signature_config' => ['pdf_type' => 'ruhsat'],
            ],
        ];

        foreach ($steps as $order => $step) {
            ProcessStep::query()->create(array_merge($step, [
                'process_definition_id' => $process->id,
                'step_order' => $order + 1,
                'is_active' => true,
            ]));
        }

        // 2) Dicle Elektrik alt kurumu (DicleMuhtelifSeeder ile ayni kurum, phone eslesir)
        $dicle = Institution::query()
            ->where('phone', '0850 255 0 186')
            ->first()
            ?? Institution::query()->firstOrCreate(
                ['slug' => 'dicle-elektrik'],
                [
                    'name' => 'Dicle Elektrik Dagitim A.S.',
                    'color_code' => '#DC2626',
                    'is_municipality' => false,
                    'phone' => '0850 255 0 186',
                    'tax_number' => '2950368442',
                ]
            );

        $buroUser = User::query()->where('email', 'buro@test.com')->first();
        $sefUser = User::query()->where('email', 'sef@test.com')->first();
        $makamUser = User::query()->where('email', 'baskan@test.com')->first();
        $creator = $buroUser ?? User::query()->first();

        $createdAt = Carbon::now()->subDays(3);

        // 3) Test basvurusu — process_id bu yeni surece bagli, approval_stage='mudur'
        //    (staff+sef adimlari "gecilmis" gosterilir, 3. adimda e-imza bekliyor).
        $application = Application::query()->create([
            'application_no' => null,
            'institution_id' => $dicle->id,
            'created_by' => $creator->id,
            'status' => ApplicationStatus::PreApproved,
            'applicant_first_name' => 'Dicle',
            'applicant_last_name' => 'Elektrik (E-Imza Test)',
            'applicant_national_id' => '2950368442',
            'tc_no' => '2950368442',
            'identity_no' => '2950368442',
            'applicant_phone' => '0541 111 29 57',
            'excavation_reason' => 'E-Imza Test Sureci — Kamu SM + E-Tugra uctan uca dogrulama',
            'work_type' => 'ENH tesis yapim isi (test)',
            'description' => 'COZUM_08 GOREV 4 test basvurusu: Fen Isleri Muduru adiminda e-imza (On Kazi '
                . 'Izni), Baskan Yrd. adiminda e-imza (Ruhsat) test edilecek.',
            'start_date' => $createdAt->copy()->addDays(2),
            'end_date' => $createdAt->copy()->addDays(40),
            'total_area_m2' => 120.00,
            'deposit_amount' => 5000,
            'discovery_amount' => 500,
            'kdv_amount' => 900,
            'ruhsat_harci' => 800,
            'kesif_bedeli' => 500,
            'ztb_toplam' => 6000,
            'genel_toplam' => 7300,
            'teminat_tutari' => 2500,
            'address_text' => 'Ataturk Mah. Cumhuriyet Cad. (E-Imza Test)',
            'address_components' => [
                ['mahalle' => 'Ataturk Mah.', 'streets' => ['Cumhuriyet Cad.']],
            ],
            'payment_status' => 'paid',
            'approval_status' => 'pending',
            'process_id' => $process->id,
            'approval_stage' => 'mudur',
            'staff_approved_by' => $buroUser?->id,
            'staff_approved_at' => $createdAt->copy()->addHours(2),
            // GOREV 4 notu: vice_mayor_name testte gercek bir isimle dolduruluyor
            // (Baskan Yrd. adimindaki municipality-makam test kullanicisinin adi).
            'vice_mayor_name' => $makamUser?->name ?: 'Ahmet Kaan Karatas',
            'approval_log' => [
                [
                    'step_id' => null,
                    'step_name' => 'Birim Sefi Parafi',
                    'action_type' => 'paraf',
                    'user_id' => $sefUser?->id,
                    'user_name' => $sefUser?->name ?: 'Ali Kaya',
                    'paraf_at' => $createdAt->copy()->addHours(5)->toIso8601String(),
                ],
            ],
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $application->update(['application_no' => sprintf('EIMZA-TEST-%s-%04d', $createdAt->year, $application->id)]);

        // 4) GIS cizimi (metraj/uzt yazi icin en az 1 sokak kaydi gerekli)
        $cizim = GisCizim::query()->create([
            'user_id' => $creator->id,
            'tip' => 'cizgi',
            'basvuru_id' => $application->id,
            'lat' => 37.1500,
            'lng' => 38.7900,
            'aciklama' => 'Cumhuriyet Cad. (E-Imza Test)',
        ]);
        GisCizimYolIliskisi::query()->create([
            'cizim_id' => $cizim->id,
            'hat_kimligi' => 1,
            'yol_adi' => 'Cumhuriyet Cad.',
            'yol_turu' => 'cadde_sokak',
            'mahalle' => 'Ataturk Mah.',
            'genislik' => 0.8,
            'uzunluk' => 150,
            'sorumluluk' => 'Dicle Elektrik',
        ]);

        // 5) Zemin satiri (tahakkuk/ruhsat PDF'lerinin bos gorunmemesi icin)
        $st = SurfaceType::query()->where('name', 'ASFALT (SICAK KARISIM)')->first()
            ?? SurfaceType::query()->create(['name' => 'ASFALT (SICAK KARISIM)', 'price_per_m2' => 90, 'active' => true]);
        $application->surfaceLines()->create([
            'surface_type_id' => $st->id,
            'width_m' => 0.8,
            'length_m' => 150,
            'quantity' => 120,
            'amount' => round(120 * 90, 2),
        ]);

        $this->command->info("E-Imza Test Sureci olusturuldu: surec #{$process->id} ({$process->slug})");
        $this->command->info("Test basvurusu: {$application->application_no} (id: {$application->id}) — approval_stage=mudur");
        $this->command->info('Giris: mudur@test.com (Ayse Demir) ile e-imza testine baslayin (On Kazi Izni).');
        $this->command->info('Sonraki adim: baskan@test.com (' . ($makamUser?->name ?: 'Ahmet Kaan Karatas') . ') ile Ruhsat e-imza.');
    }
}
