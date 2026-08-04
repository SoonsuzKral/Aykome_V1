<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\GisCizim;
use App\Models\GisCizimYolIliskisi;
use App\Models\Institution;
use App\Models\SurfaceType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * "MUHTELİF CADDE VE SOKAK" iş kuralını test etmek için Dicle Elektrik
 * alt kurumu adına, 3 farklı mahalleden toplam 6+ cadde/sokak içeren bir
 * başvuru üretir (streetCount() > 3 → isMuhtelif() = true).
 */
class DicleMuhtelifSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Dicle Elektrik alt kurumu (is_municipality = false) — telefon '0850 255 0 186'
        $dicle = Institution::query()
            ->where('phone', '0850 255 0 186')
            ->first()
            ?? Institution::query()->firstOrCreate(
                ['slug' => 'dicle-elektrik'],
                [
                    'name' => 'Dicle Elektrik Dağıtım A.Ş.',
                    'color_code' => '#DC2626',
                    'is_municipality' => false,
                    'phone' => '0850 255 0 186',
                    'tax_number' => '2950368442',
                ]
            );

        $user = User::query()->first() ?? User::query()->create([
            'name' => 'Dicle Mühendis',
            'email' => 'dicle-muhendis@aykome.test',
            'password' => bcrypt('password'),
        ]);

        // 2) 3 mahalleden toplam 7 sokak
        $mahalleler = [
            'Atatürk Mah.' => ['Cumhuriyet Cad.', 'İnönü Cad.', 'Barış Sok.'],
            'Kahramanlar Mah.' => ['Kahramanlar Cad.', 'Gençlik Sok.'],
            'Yeni Mahalle' => ['Lale Sok.', 'Akasya Sok.'],
        ];

        $addressComponents = [];
        $streetList = [];
        foreach ($mahalleler as $mahalle => $sokaklar) {
            $addressComponents[] = ['mahalle' => $mahalle, 'streets' => array_values($sokaklar)];
            foreach ($sokaklar as $sokak) {
                $streetList[] = $sokak;
            }
        }

        $createdAt = Carbon::now()->subDays(rand(3, 10));

        // 3) Başvuru oluştur
        $application = Application::query()->create([
            'application_no' => null,
            'institution_id' => $dicle->id,
            'created_by' => $user->id,
            'status' => ApplicationStatus::Licensed,
            'applicant_first_name' => 'Dicle',
            'applicant_last_name' => 'Elektrik',
            'applicant_national_id' => '2950368442',
            'tc_no' => '2950368442',
            'identity_no' => '2950368442',
            'applicant_phone' => '0541 111 29 57',
            'excavation_reason' => 'Orta gerilim enerji hattı ve trafo tesis çalışması',
            'work_type' => 'ENH tesis yapım işi',
            'description' => 'Dicle Elektrik 2026 yılı altyapı yatırım programı kapsamında muhtelif cadde ve sokaklarda kazı izni talebi',
            'start_date' => $createdAt->copy()->addDays(2),
            'end_date' => $createdAt->copy()->addDays(40),
            'total_area_m2' => 640.50,
            'deposit_amount' => 12500,
            'discovery_amount' => 1250,
            'kdv_amount' => 2500,
            'ruhsat_harci' => 2250,
            'kesif_bedeli' => 1250,
            'ztb_toplam' => 15000,
            'genel_toplam' => 18500,
            'teminat_tutari' => 6250,
            'address_text' => implode("\n", array_map(fn ($s) => "Atatürk Mah. $s", $streetList)),
            'address_components' => $addressComponents,
            'payment_status' => 'paid',
            'approval_status' => 'licensed',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
            'tesis_sorumlusu' => $dicle->tesis_sorumlusu_adi ?? null,
        ]);

        $application->update(['application_no' => sprintf('%s-%04d', $createdAt->year, $application->id)]);

        // 4) GIS çizimleri + yol ilişkileri (metraj + üst yazı sokak listesi için)
        $i = 0;
        foreach ($mahalleler as $mahalle => $sokaklar) {
            foreach ($sokaklar as $sokak) {
                $i++;
                $cizim = GisCizim::query()->create([
                    'user_id' => $user->id,
                    'tip' => 'cizgi',
                    'basvuru_id' => $application->id,
                    'lat' => 37.1000 + $i * 0.001,
                    'lng' => 38.8000 + $i * 0.001,
                    'aciklama' => $sokak,
                ]);
                GisCizimYolIliskisi::query()->create([
                    'cizim_id' => $cizim->id,
                    'hat_kimligi' => $i,
                    'yol_adi' => $sokak,
                    'yol_turu' => 'cadde_sokak',
                    'mahalle' => $mahalle,
                    'genislik' => 0.8,
                    'uzunluk' => rand(40, 200),
                    'sorumluluk' => 'Dicle Elektrik',
                ]);
            }
        }

        // 5) Zemin satırları (surfaceLines) — Asfalt, Parke, Beton
        $zeminler = [
            ['ASFALT (SICAK KARIŞIM)', 90, 1265],
            ['PARKE', 45, 904],
            ['BETON', 10.5, 813],
        ];
        foreach ($zeminler as [$name, $qty, $price]) {
            $st = SurfaceType::query()->where('name', $name)->first()
                ?? SurfaceType::query()->create(['name' => $name, 'price_per_m2' => $price, 'active' => true]);
            $application->surfaceLines()->create([
                'surface_type_id' => $st->id,
                'width_m' => 0.8,
                'length_m' => $qty / 0.8,
                'quantity' => $qty,
                'amount' => round($qty * $price, 2),
            ]);
        }

        $this->command->info("Dicle Elektrik muhtelif başvurusu oluşturuldu: {$application->application_no} (id: {$application->id})");
        $this->command->info("Sokak sayısı: {$application->streetCount()} | Muhtelif: " . ($application->isMuhtelif() ? 'EVET' : 'HAYIR'));
    }
}