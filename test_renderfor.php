<?php

// Hangi belgeler renderFor (şablon) yolundan, hangileri blade yolundan?
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Services\DocumentTemplateService;

$application = Application::find(1254);

$tipler = ['ruhsat', 'on_kazi', 'cover_letter', 'tahakkuk', 'metraj', 'makbuz', 'taahhutname'];
foreach ($tipler as $tip) {
    $html = DocumentTemplateService::renderFor($tip, $application, false, false);
    if ($html === null) {
        echo "$tip: BLADE YOLU (renderFor NULL)\n";
        continue;
    }
    $editor = DocumentTemplateService::editor($tip);
    $looksJson = str_starts_with(trim($html), '[') || str_starts_with(trim($html), '{');
    $landscape = !empty(DocumentTemplateService::TYPES[$tip]['landscape']);
    $a4cls = str_contains($html, 'a4-container') ? 'a4-container' : (str_contains($html, 'a4-landscape-container') ? 'a4-landscape-container' : 'container YOK');
    echo sprintf("%s: ŞABLON VAR (%s, editor=%s, %s) | %d bayt | %s\n",
        $tip, $looksJson ? 'JSON-GRID' : 'HTML', $editor, $landscape ? 'landscape' : 'portrait',
        strlen($html), $a4cls);
}
