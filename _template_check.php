<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\GlobalDocumentTemplate;
use App\Services\DocumentTemplateService;

foreach (['makbuz', 'ruhsat'] as $tip) {
    $row = GlobalDocumentTemplate::where('document_type', $tip)->first();
    echo "=== $tip ===\n";
    if (!$row) {
        echo "  (yok)\n";
        continue;
    }
    $c = $row->content_data ?? '';
    // toolbar / button / print-bar / no-print-bar geçen bölümleri özetle
    $lines = explode("\n", $c);
    $n = 0;
    foreach ($lines as $i => $l) {
        if (preg_match('/toolbar|print-bar|no-print|btn-|onclick|Yazd|Düzenle|button/i', $l)) {
            echo "  L$i: " . trim(substr($l, 0, 200)) . "\n";
            if (++$n > 25) break;
        }
    }
    if ($n === 0) echo "  (toolbar izi yok)\n";
    // font kullanımları
    preg_match_all('/font(?:-family)?\s*:\s*[^;}"]+/i', $c, $m);
    $fonts = array_unique(array_map('trim', $m[0]));
    echo "  FONT bildirimleri: " . implode(' | ', array_slice($fonts, 0, 12)) . "\n";
    echo "  İçerik boyutu: " . strlen($c) . " bayt\n\n";
}
