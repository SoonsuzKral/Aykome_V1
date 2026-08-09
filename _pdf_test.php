<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Services\EImzaService;

$app1 = Application::first();
if (!$app1) {
    echo "UYGULAMA YOK\n";
    exit(1);
}
echo "Uygulama: {$app1->id} {$app1->application_no}\n";

$svc = app(EImzaService::class);

$damga = [
    'tarih' => date('d.m.Y H:i'),
    'imzalayan' => 'Ahmet YILMAZ',
    'unvan' => 'Belediye Başkan Yardımcısı',
    'ad_yazilsin' => true,
];

$outDir = storage_path('app/public/e-imza/test-dogrulama');
if (!is_dir($outDir)) mkdir($outDir, 0775, true);

foreach (['ruhsat', 'makbuz'] as $tip) {
    try {
        $pdf = $svc->pdfOlustur($app1, $tip, $damga);
        $path = "$outDir/{$tip}-damgali.pdf";
        file_put_contents($path, $pdf->output());
        echo "OK {$tip}: " . filesize($path) . " bayt\n";
    } catch (Throwable $e) {
        echo "HATA {$tip}: " . $e->getMessage() . "\n";
    }
}

try {
    $pdf2 = $svc->pdfOlustur($app1, 'makbuz');
    $path2 = "$outDir/makbuz-damgasiz.pdf";
    file_put_contents($path2, $pdf2->output());
    echo "OK makbuz-damgasiz: " . filesize($path2) . " bayt\n";
} catch (Throwable $e) {
    echo "HATA makbuz-damgasiz: " . $e->getMessage() . "\n";
}
