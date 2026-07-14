<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ExcavationArea;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ApplicationSeeder extends Seeder
{
    private array $turkishNames = [
        ['Ahmet', 'Yılmaz'], ['Mehmet', 'Kaya'], ['Mustafa', 'Demir'], ['Ali', 'Çelik'],
        ['Hasan', 'Şahin'], ['Hüseyin', 'Yıldız'], ['İbrahim', 'Öztürk'], ['Ömer', 'Arslan'],
        ['Fatma', 'Çetin'], ['Ayşe', 'Koç'], ['Zeynep', 'Kurt'], ['Emine', 'Aydın'],
        ['Meryem', 'Özdemir'], ['Hatice', 'Doğan'], ['Ramazan', 'Kılıç'], ['Recep', 'Aslan'],
        ['Yusuf', 'Tekin'], ['Murat', 'Polat'], ['Serkan', 'Güneş'], ['Burak', 'Erdoğan'],
        ['Caner', 'Çelik'], ['Deniz', 'Bayrak'], ['Emre', 'Solmaz'], ['Furkan', 'Karakaş'],
        ['Gizem', 'Yaman'], ['Hacer', 'Bulut'], ['Ilgın', 'Demirci'], ['Jale', 'Güler'],
        ['Kerem', 'Aktaş'], ['Leyla', 'Karahan'],
    ];

    private array $addresses = [
        'Atatürk Mah. Cumhuriyet Cad. No:12', 'İstiklal Mah. Barış Sok. No:5',
        'Çarşı Mah. Atatürk Bulv. No:88', 'Yeni Mah. Lale Sok. No:3',
        'Kızılay Mah. Mevlana Cad. No:45', 'Bağlar Mah. Dicle Sok. No:7',
        'Sanayi Mah. İnönü Cad. No:22', 'Merkez Mah. Fevzi Çakmak Bulv. No:101',
        'Gazi Mah. Selçuk Cad. No:66', 'Bahçelievler Mah. Akasya Sok. No:14',
        'Yenişehir Mah. Süleyman Demirel Cad. No:9', 'Kale Mah. Osmanlı Sok. No:31',
        'Fatih Mah. Millet Cad. No:57', 'Çankaya Mah. Kazım Karabekir Cad. No:11',
        'Emek Mah. Ziya Gökalp Sok. No:8', 'Hacettepe Mah. Fevzipaşa Cad. No:42',
        'Telsizler Mah. İsmet Paşa Bulv. No:19', 'Kavaklıdere Mah. Tunalı Hilmi Cad. No:73',
        'Çukurambar Mah. Eskişehir Yolu No:5', 'Söğütözü Mah. Mustafa Kemal Cad. No:28',
    ];

    private array $excavationReasons = [
        'Doğalgaz boru hattı döşenmesi', 'Su şebekesi yenileme çalışması',
        'Elektrik kablosu döşenmesi', 'Fiber optik altyapı çalışması',
        'Kanalizasyon bağlantı hattı', 'Yağmur suyu drenaj hattı',
        'Telefon kablosu yenileme', 'İçme suyu ana hattı tamiri',
        'Isıtma boru hattı döşenmesi', 'Elektrik trafo bağlantısı',
    ];

    public function run(): void
    {
        $institutions = Institution::query()->pluck('id', 'slug')->toArray();
        $users = User::query()->pluck('id')->toArray();

        if (empty($institutions) || empty($users)) {
            $this->command->warn('Seeder requires institutions and users. Run AykomeSeeder first.');
            return;
        }

        $institutionIds = array_values($institutions);
        $statusDistribution = [
            ApplicationStatus::Draft,
            ApplicationStatus::Draft,
            ApplicationStatus::Submitted,
            ApplicationStatus::Submitted,
            ApplicationStatus::Priced,
            ApplicationStatus::AwaitingPayment,
            ApplicationStatus::AwaitingPayment,
            ApplicationStatus::ReceiptPending,
            ApplicationStatus::ReceiptPending,
            ApplicationStatus::Approved,
            ApplicationStatus::Licensed,
            ApplicationStatus::Licensed,
            ApplicationStatus::FieldWork,
            ApplicationStatus::FieldWork,
            ApplicationStatus::Completed,
            ApplicationStatus::Completed,
            ApplicationStatus::Completed,
            ApplicationStatus::Rejected,
            ApplicationStatus::Archived,
        ];

        // Ankara bounding box for fake coordinates
        $latMin = 39.75; $latMax = 40.10;
        $lngMin = 32.55; $lngMax = 33.10;

        $baseDate = Carbon::parse('2025-10-01');

        DB::transaction(function () use ($institutionIds, $users, $statusDistribution, $latMin, $latMax, $lngMin, $lngMax, $baseDate) {
            for ($i = 1; $i <= 50; $i++) {
                $nameData = $this->turkishNames[array_rand($this->turkishNames)];
                $status   = $statusDistribution[array_rand($statusDistribution)];
                $instId   = $institutionIds[array_rand($institutionIds)];
                $creatorId = $users[array_rand($users)];
                $createdAt = $baseDate->copy()->addDays(rand(0, 170))->setTime(rand(7, 18), rand(0, 59));

                $totalArea = round(rand(15, 800) + (rand(0, 99) / 100), 2);
                $totalPrice = $status->value !== ApplicationStatus::Draft->value
                    ? round($totalArea * rand(80, 150), 3)
                    : null;

                $discoveryAmount = $totalPrice !== null ? round($totalPrice * (1 + rand(-10, 10) / 100), 3) : null;

                $paymentStatus = match ($status) {
                    ApplicationStatus::Licensed, ApplicationStatus::FieldWork, ApplicationStatus::Completed, ApplicationStatus::Approved => 'paid',
                    ApplicationStatus::ReceiptPending => 'receipt_uploaded',
                    ApplicationStatus::AwaitingPayment => 'unpaid',
                    default => 'unpaid',
                };

                $approvalStatus = match ($status) {
                    ApplicationStatus::Licensed, ApplicationStatus::FieldWork, ApplicationStatus::Completed => 'licensed',
                    ApplicationStatus::Approved => 'price_approved',
                    ApplicationStatus::Rejected => 'rejected',
                    default => 'pending',
                };

                $application = Application::query()->create([
                    'application_no'        => null,
                    'institution_id'        => $instId,
                    'created_by'            => $creatorId,
                    'status'                => $status,
                    'applicant_first_name'  => $nameData[0],
                    'applicant_last_name'   => $nameData[1],
                    'applicant_national_id' => $this->fakeTckn(),
                    'tc_no'                 => $this->fakeTckn(),
                    'identity_no'           => $this->fakeTckn(),
                    'applicant_phone'       => '05' . rand(30, 59) . rand(100, 999) . rand(1000, 9999),
                    'excavation_reason'     => $this->excavationReasons[array_rand($this->excavationReasons)],
                    'work_type'             => ['kazı', 'tamir', 'altyapı', 'döşeme'][rand(0, 3)],
                    'description'           => 'Seeder ile oluşturulmuş demo başvuru #' . $i,
                    'start_date'            => $createdAt->copy()->addDays(rand(3, 15)),
                    'end_date'              => $createdAt->copy()->addDays(rand(16, 60)),
                    'total_area_m2'         => $totalArea,
                    'total_price'           => $totalPrice,
                    'discovery_amount'      => $discoveryAmount,
                    'payment_status'        => $paymentStatus,
                    'approval_status'       => $approvalStatus,
                    'address_text'          => $this->addresses[array_rand($this->addresses)],
                    'price_approved_at'     => in_array($status, [ApplicationStatus::Licensed, ApplicationStatus::FieldWork, ApplicationStatus::Completed, ApplicationStatus::AwaitingPayment, ApplicationStatus::ReceiptPending, ApplicationStatus::Approved], true)
                        ? $createdAt->copy()->addDays(rand(1, 5)) : null,
                    'receipt_approved_at'   => in_array($status, [ApplicationStatus::Licensed, ApplicationStatus::FieldWork, ApplicationStatus::Completed], true)
                        ? $createdAt->copy()->addDays(rand(6, 20)) : null,
                    'created_at'            => $createdAt,
                    'updated_at'            => $createdAt->copy()->addDays(rand(0, 30)),
                ]);

                $application->update([
                    'application_no' => sprintf('AYK-%s-%06d', $createdAt->year, $application->id),
                ]);

                // Add fake excavation area with GeoJSON polygon
                $centerLat = round($latMin + ($latMax - $latMin) * (rand(0, 100) / 100), 6);
                $centerLng = round($lngMin + ($lngMax - $lngMin) * (rand(0, 100) / 100), 6);
                $delta = round(0.001 + 0.003 * (rand(0, 100) / 100), 5);

                $polygon = [
                    [$centerLng - $delta, $centerLat - $delta],
                    [$centerLng + $delta, $centerLat - $delta],
                    [$centerLng + $delta, $centerLat + $delta],
                    [$centerLng - $delta, $centerLat + $delta],
                    [$centerLng - $delta, $centerLat - $delta],
                ];

                $geoJson = json_encode([
                    'type'     => 'Feature',
                    'geometry' => ['type' => 'Polygon', 'coordinates' => [$polygon]],
                    'properties' => ['application_no' => $application->application_no],
                ]);

                ExcavationArea::query()->create([
                    'application_id'  => $application->id,
                    'polygon_geojson' => $geoJson,
                    'total_area_m2'   => $totalArea,
                    'center_lat'      => $centerLat,
                    'center_lng'      => $centerLng,
                    'address_text'    => $application->address_text,
                    'created_at'      => $createdAt,
                    'updated_at'      => $createdAt,
                ]);
            }
        });

        $this->command->info('50 fake application records created successfully.');
    }

    private function fakeTckn(): string
    {
        // Luhn-style fake TCKN starting with non-zero
        $digits = [];
        $digits[] = rand(1, 9);
        for ($i = 1; $i < 11; $i++) {
            $digits[] = rand(0, 9);
        }
        return implode('', $digits);
    }
}
