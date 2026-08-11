<?php

// makbuz 5070 metni neden eklenmiyor? — imzaYasalMetinEkle'nin çıktısını kontrol et
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Services\EImzaService;

$application = Application::find(1254);
$service = app(EImzaService::class);
$imzaTarihi = now();

// makbuz blade HTML'i üret
$view = 'admin.pdf.tahsilat_makbuzu';
$data = array_merge($application->toArray(), [
    'application' => $application,
    'appNo' => $application->application_no,
    'institution' => $application->institution,
    'signatories' => [],
]);
$html = view($view, $data)->render();
$html = \App\Services\DocumentTemplateService::pdfCssEnjekte($html);

echo "1. pdfCssEnjekte sonrası '5070' içeriyor mu: " . (str_contains($html, '5070') ? 'EVET' : 'HAYIR') . "\n";
echo "   a4-container var: " . (str_contains($html, 'a4-container') ? 'EVET' : 'HAYIR') . "\n";

// imzaYasalMetinEkle'yi çağır (protected, Reflection ile)
$ref = new ReflectionMethod(EImzaService::class, 'imzaYasalMetinEkle');
$ref->setAccessible(true);
$out = $ref->invoke($service, $html, $imzaTarihi);

echo "2. imzaYasalMetinEkle sonrası '5070' içeriyor mu: " . (str_contains($out, '5070') ? 'EVET' : 'HAYIR') . "\n";
echo "   'güvenli elektronik imza' içeriyor mu: " . (str_contains($out, 'güvenli elektronik imza') ? 'EVET' : 'HAYIR') . "\n";

// 5070 bloğunun nereye gittiğini bul
$pos = strpos($out, '5070');
if ($pos !== false) {
    echo "   5070 konumu: karakter $pos\n";
    echo "   Etrafı: ..." . substr($out, max(0, $pos - 120), 300) . "...\n";
}

// PDF üret ve metni kontrol et
try {
    $pdf = $service->pdfOlustur($application, 'makbuz', null, $imzaTarihi);
    $pdf->save(storage_path('app/test_makbuz_debug.pdf'));
    echo "\n3. PDF üretildi\n";
} catch (\Throwable $e) {
    echo "\n3. PDF HATA: " . substr($e->getMessage(), 0, 200) . "\n";
}
