<?php

// Test: TÜM belge tipleri için PDF üret + 5070 enjeksiyonu doğrula
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Services\EImzaService;

$application = Application::find(1254);
if (!$application) {
    fwrite(STDERR, "Başvuru 1254 bulunamadı\n");
    exit(1);
}
echo "Başvuru: {$application->application_no} | {$application->status->value}\n\n";

$service = app(EImzaService::class);
$imzaTarihi = now();

$belgeTipleri = [
    'ruhsat',       // Altyapı Tesisi Açım Ruhsatı
    'pre_permit',   // Ön Kazı İzni
    'cover_letter', // Üst Yazı
    'tahakkuk',     // Tahakkuk Fişi
    'metraj',       // Kazı Metraj (landscape)
    'makbuz',       // Tahsilat Makbuzu
    'taahhutname',  // Taahhütname
];

foreach ($belgeTipleri as $tip) {
    try {
        $pdf = $service->pdfOlustur($application, $tip, null, $imzaTarihi);
        $path = storage_path("app/test_{$tip}_5070.pdf");
        $pdf->save($path);
        printf("  %-14s → %5d bayt ✓\n", $tip, filesize($path));
    } catch (\Throwable $e) {
        printf("  %-14s → HATA: %s\n", $tip, substr($e->getMessage(), 0, 90));
    }
}