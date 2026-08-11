<?php

// tahakkuk blade yolundan geçen HTML'i yakala — squeeze CSS uygulandı mı?
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Services\EImzaService;

$application = Application::find(1254);
$service = app(EImzaService::class);

// Blade yolunu manuel taklit et (EImzaService::pdfOlustur'daki gibi)
$view = 'admin.pdf.tahakkuk';
$data = array_merge($application->toArray(), [
    'application' => $application,
    'appNo' => $application->application_no,
    'institution' => $application->institution,
    'signatories' => app(\App\Services\SignerPlacementService::class)->yerlesimHazirla($application, 'tahakkuk'),
]);
$html = view($view, $data)->render();
$html = \App\Services\DocumentTemplateService::pdfCssEnjekte($html);

// squeeze CSS var mı?
echo "a4-container width:98.5% CSS var mı: " . (str_contains($html, 'width: 98.5%') ? 'EVET' : 'HAYIR') . "\n";
echo "dejavu css var mı: " . (str_contains($html, 'DejaVu') ? 'EVET' : 'HAYIR') . "\n";
echo "table width 100% blade: " . (substr_count($html, 'width: 100%')) . " kez\n";
echo "toplamlar table: " . (str_contains($html, 'toplamlar') ? 'VAR' : 'YOK') . "\n";
// CSS bloklarını göster
preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $html, $m);
foreach ($m[1] as $i => $css) {
    echo "\n--- STYLE $i (" . strlen($css) . "bayt) ---\n";
    if (str_contains($css, '98.5') || str_contains($css, 'DejaVu')) {
        echo substr($css, 0, 800) . "\n";
    }
}