<?php

// cover_letter şablonunun taşan bölümlerini bul
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Services\DocumentTemplateService;

$application = Application::find(1254);
$html = DocumentTemplateService::renderFor('cover_letter', $application, false, false);

// justify içeren paragrafları ve genişlik CSS'ini ara
preg_match_all('/<p[^>]*style="[^"]*text-align\s*:\s*justify[^"]*"[^>]*>/', $html, $justify);
echo "Justify <p> sayısı: " . count($justify[0]) . "\n";

// a4-container CSS tanımı
preg_match('/\.a4-container\s*{[^}]*}/', $html, $ac);
echo "\nContainer CSS:\n" . ($ac[0] ?? 'YOK') . "\n";

// Genişlik taşması olan blok: en geniş sabit genişlikler
preg_match_all('/width\s*:\s*(\d+(?:\.\d+)?)(px|mm|pt|%|cm)/i', $html, $w);
$uniq = [];
foreach ($w[0] as $i => $v) { $uniq["$v"] = true; }
echo "\nSabit genişlikler: " . implode(', ', array_keys($uniq)) . "\n";

// Paragrafları bas
preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $html, $ps);
echo "\nParagraf sayısı: " . count($ps[1]) . "\n";
foreach ($ps[1] as $i => $p) {
    $txt = trim(strip_tags($p));
    if (strlen($txt) > 5) echo "  P$i (" . strlen($txt) . "): " . substr($txt, 0, 60) . "\n";
}