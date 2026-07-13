<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ExcavationArea;
use App\Models\FieldTask;
use App\Models\Institution;
use App\Models\SurfaceType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AykomeFullSeeder extends Seeder
{
    private const ADDRESSES = [
        'Mimar Sinan Cad. No:12, Merkez',
        'Atatürk Bulvarı No:45, Haliliye',
        'Cumhuriyet Mah. Bahçelievler Sok. No:3',
        'Sanayi Sit. 5. Blok, Eyyübiye',
        'Karakoyun Cad. No:88, Karaköprü',
        'Yeşiltepe Mah. Güneş Sok. No:21',
        'İstasyon Cad. No:67, Merkez',
        'Bağlar Mah. Meydan Sok. No:9',
        'Uludağ Cad. No:34, Haliliye',
        'Vatan Bulvarı No:56, Eyyübiye',
    ];

    private const REASONS = [
        'Doğalgaz boru hattı döşenmesi',
        'İçme suyu şebeke yenileme',
        'Fiber optik kablo döşenmesi',
        'Elektrik dağıtım hattı bakımı',
        'Kanalizasyon hattı onarımı',
        'Belediye su ana hattı yenileme',
        'Altyapı genişletme çalışması',
        'Drenaj sistemi kurulumu',
        'Telekomünikasyon kablosu döşenmesi',
        'Arıza onarım kazısı',
    ];

    public function run(): void
    {
        // ─── 1. Kurumlar ──────────────────────────────────────────────────────
        $tedas = Institution::firstOrCreate(
            ['slug' => 'tedas-sanliurfa'],
            [
                'name'              => 'TEDAŞ Şanlıurfa İl Müdürlüğü',
                'type'              => 'TEDAŞ',
                'authorized_person' => 'Ahmet Kaya',
                'tax_number'        => '1234567890',
                'phone'             => '0414 222 0000',
                'email'             => 'tedas@sanliurfa.gov.tr',
                'color_code'        => '#DC2626',
                'is_municipality'   => false,
            ]
        );

        $suski = Institution::firstOrCreate(
            ['slug' => 'suski-genel'],
            [
                'name'              => 'ŞUSKİ Genel Müdürlüğü',
                'type'              => 'ŞUSKİ',
                'authorized_person' => 'Mehmet Yılmaz',
                'tax_number'        => '9876543210',
                'phone'             => '0414 333 1111',
                'email'             => 'suski@sanliurfa.gov.tr',
                'color_code'        => '#2563EB',
                'is_municipality'   => false,
            ]
        );

        $telekom = Institution::firstOrCreate(
            ['slug' => 'turk-telekom-sanliurfa'],
            [
                'name'              => 'Türk Telekom Şanlıurfa',
                'type'              => 'Türk Telekom',
                'authorized_person' => 'Fatma Demir',
                'tax_number'        => '5555555555',
                'phone'             => '0414 444 2222',
                'email'             => 'telekom@sanliurfa.com',
                'color_code'        => '#7C3AED',
                'is_municipality'   => false,
            ]
        );

        $belediye = Institution::firstOrCreate(
            ['slug' => 'belediye'],
            ['name' => 'Merkez Belediye', 'color_code' => '#16A34A', 'is_municipality' => true]
        );

        // ─── 2. Kullanıcılar ──────────────────────────────────────────────────
        $admin = User::firstOrCreate(
            ['email' => 'admin@aykome.local'],
            ['name' => 'Süper Admin', 'password' => Hash::make('password'), 'institution_id' => $belediye->id, 'email_verified_at' => now()]
        );

        $field1 = User::firstOrCreate(
            ['email' => 'saha1@aykome.local'],
            ['name' => 'Kadir Şahin', 'password' => Hash::make('password'), 'institution_id' => $belediye->id, 'email_verified_at' => now()]
        );
        $field1->syncRoles(['field-team']);

        $field2 = User::firstOrCreate(
            ['email' => 'saha2@aykome.local'],
            ['name' => 'Ömer Polat', 'password' => Hash::make('password'), 'institution_id' => $belediye->id, 'email_verified_at' => now()]
        );
        $field2->syncRoles(['field-team']);

        // ─── 3. Surface Types ─────────────────────────────────────────────────
        $asfalt  = SurfaceType::firstOrCreate(['name' => 'Asfalt'], ['price_per_m2' => 100, 'active' => true]);
        $parke   = SurfaceType::firstOrCreate(['name' => 'Beton Parke'], ['price_per_m2' => 85, 'active' => true]);
        $toprak  = SurfaceType::firstOrCreate(['name' => 'Ham Toprak'], ['price_per_m2' => 40, 'active' => true]);

        // ─── 4. 30 Başvuru ────────────────────────────────────────────────────
        $institutions = [$tedas, $suski, $telekom];

        $statusBatches = [
            // [status, payment_status, approval_status, has_field_task, stage_scenario]
            [ApplicationStatus::Submitted,      'unpaid',  'pending',  false, null],
            [ApplicationStatus::Submitted,      'unpaid',  'pending',  false, null],
            [ApplicationStatus::AwaitingPayment,'unpaid',  'approved', false, null],
            [ApplicationStatus::ReceiptPending, 'unpaid',  'approved', false, null],
            [ApplicationStatus::Licensed,       'paid',    'approved', false, null],
            [ApplicationStatus::Licensed,       'paid',    'approved', true,  'stage1_only'],
            [ApplicationStatus::FieldWork,      'paid',    'approved', true,  'stage2_done'],
            [ApplicationStatus::FieldWork,      'paid',    'approved', true,  'stage1_done'],
            [ApplicationStatus::Completed,      'paid',    'approved', true,  'all_done'],
            [ApplicationStatus::Rejected,       'unpaid',  'rejected', false, null],
        ];

        $seq = 1;
        foreach ($institutions as $inst) {
            foreach ($statusBatches as $i => $scenario) {
                [$status, $payStatus, $approvalStatus, $hasTask, $stageScenario] = $scenario;

                $addrIdx    = ($seq - 1) % count(self::ADDRESSES);
                $reasonIdx  = ($seq - 1) % count(self::REASONS);
                $startDate  = now()->subDays(rand(10, 90));
                $endDate    = $startDate->copy()->addDays(rand(15, 45));
                $appNo      = 'AYK-' . now()->year . '-' . str_pad($seq + 100, 4, '0', STR_PAD_LEFT);
                $area       = rand(20, 200);
                $unitPrice  = rand(80, 120);
                $totalPrice = $area * $unitPrice;

                $nationalId = '1' . sprintf('%09d', rand(100000000, 999999999));
                // Make valid: sum of digits check (simplified)
                $nationalId = str_pad($nationalId, 11, '0', STR_PAD_LEFT);

                $app = Application::create([
                    'application_no'          => $appNo,
                    'institution_id'          => $inst->id,
                    'created_by'              => $admin->id,
                    'status'                  => $status->value,
                    'applicant_first_name'    => collect(['Ali','Veli','Hasan','Ayşe','Fatma','Mehmet','Zeynep','Mustafa'])->random(),
                    'applicant_last_name'     => collect(['Kaya','Yılmaz','Çelik','Demir','Öztürk','Arslan','Koç','Şahin'])->random(),
                    'applicant_national_id'   => $nationalId,
                    'tc_no'                   => $nationalId,
                    'identity_no'             => $nationalId,
                    'applicant_phone'         => '05' . rand(10, 59) . rand(1000000, 9999999),
                    'excavation_reason'       => self::REASONS[$reasonIdx],
                    'work_type'               => collect(['Altyapı', 'Onarım', 'Yenileme', 'Kurulum'])->random(),
                    'description'             => 'Mevcut altyapı durumu için zorunlu kazı çalışması.',
                    'start_date'              => $startDate,
                    'end_date'                => $endDate,
                    'total_area_m2'           => $area,
                    'total_price'             => $totalPrice,
                    'discovery_amount'        => round($totalPrice * 1.1, 2),
                    'payment_status'          => $payStatus,
                    'approval_status'         => $approvalStatus,
                    'address_text'            => self::ADDRESSES[$addrIdx],
                    'price_approved_at'       => in_array($approvalStatus, ['approved']) ? now()->subDays(rand(3, 20)) : null,
                    'price_approved_by'       => in_array($approvalStatus, ['approved']) ? $admin->id : null,
                    'receipt_approved_at'     => $payStatus === 'paid' ? now()->subDays(rand(1, 10)) : null,
                    'receipt_approved_by'     => $payStatus === 'paid' ? $admin->id : null,
                    'rejection_reason'        => $status === ApplicationStatus::Rejected ? 'Eksik belge nedeniyle reddedildi.' : null,
                ]);

                // ExcavationArea
                $lat = 37.1591 + (rand(-500, 500) / 10000);
                $lng = 38.7969 + (rand(-500, 500) / 10000);
                ExcavationArea::create([
                    'application_id' => $app->id,
                    'polygon_geojson' => json_encode([
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Polygon',
                            'coordinates' => [[
                                [$lng - 0.001, $lat - 0.001],
                                [$lng + 0.001, $lat - 0.001],
                                [$lng + 0.001, $lat + 0.001],
                                [$lng - 0.001, $lat + 0.001],
                                [$lng - 0.001, $lat - 0.001],
                            ]],
                        ],
                        'properties' => ['area_m2' => $area],
                    ]),
                    'total_area_m2'  => $area,
                    'center_lat'     => $lat,
                    'center_lng'     => $lng,
                ]);

                // FieldTask
                if ($hasTask) {
                    $assignee = $seq % 2 === 0 ? $field1 : $field2;

                    [$overallStatus, $s1, $s2, $s3] = match ($stageScenario) {
                        'stage1_only' => ['in_progress', 'done',    'pending', 'pending'],
                        'stage1_done' => ['in_progress', 'done',    'pending', 'pending'],
                        'stage2_done' => ['in_progress', 'done',    'done',    'pending'],
                        'all_done'    => ['completed',   'done',    'done',    'done'],
                        default       => ['pending',     'pending', 'pending', 'pending'],
                    };

                    FieldTask::create([
                        'application_id'        => $app->id,
                        'assigned_to'           => $assignee->id,
                        'assigned_by'           => $admin->id,
                        'status'                => $overallStatus,
                        'due_date'              => $endDate,
                        'notes'                 => 'Saha kontrolü zorunlu. Ekibin mutlaka fotoğraf yüklemesi gerekiyor.',
                        'stage_1_status'        => $s1,
                        'stage_1_notes'         => $s1 === 'done' ? 'Kazı öncesi kontrol tamamlandı, alan hazır.' : null,
                        'stage_1_inspected_at'  => $s1 === 'done' ? now()->subDays(rand(1, 5)) : null,
                        'stage_2_status'        => $s2,
                        'stage_2_notes'         => $s2 === 'done' ? 'Kazı sonrası kontrol yapıldı, boru döşendi.' : null,
                        'stage_2_inspected_at'  => $s2 === 'done' ? now()->subDays(rand(1, 3)) : null,
                        'stage_3_status'        => $s3,
                        'stage_3_notes'         => $s3 === 'done' ? 'Zemin onarımı tamamlandı, asfalt düzgün.' : null,
                        'stage_3_inspected_at'  => $s3 === 'done' ? now()->subDay() : null,
                    ]);
                }

                $seq++;
            }
        }

        $this->command->info("✓ AykomeFullSeeder: {$seq} başvuru, 3 kurum, 2 saha personeli oluşturuldu.");
    }
}
