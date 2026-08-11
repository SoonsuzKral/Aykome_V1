<?php

// a4ContainerInlineWidth'in neden çalışmadığını debug et
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Services\DocumentTemplateService;

$application = Application::find(1254);

// Tahakkuk blade HTML'i
$html = view('admin.pdf.tahakkuk', [
    'application' => $application,
    'belediye' => 'EYYÜBİYE BELEDİYESİ',
    'signatories' => [],
])->render();

echo "input uzunluk: " . strlen($html) . "\n";
echo "a4-container class var: " . (str_contains($html, 'a4-container') ? 'EVET' : 'HAYIR') . "\n";

$out = DocumentTemplateService::pdfCssEnjekte($html);
// inline width var mı?
echo "inline 'width: 100% !important' var: " . (str_contains($out, 'width: 100% !important') ? 'EVET' : 'HAYIR') . "\n";

// container div'ini göster
preg_match('/<div class="a4-container"[^>]*>/', $out, $m);
echo "container tag: " . ($m[0] ?? 'BULUNAMADI') . "\n";

// metraj renderFor yolunu da test et
$mapped = 'metraj';
$html2 = DocumentTemplateService::renderFor($mapped, $application, false, false);
echo "\nmetraj renderFor null mı: " . ($html2 === null ? 'EVET (blade yolu)' : 'HAYIR (şablon var, ' . strlen($html2) . 'bayt)') . "\n";
if ($html2) {
    echo "metraj 'a4-container' var: " . (str_contains($html2, 'a4-container') ? 'EVET' : 'HAYIR') . "\n";
    echo "metraj 'a4-landscape-container' var: " . (str_contains($html2, 'a4-landscape-container') ? 'EVET' : 'HAYIR') . "\n";
}