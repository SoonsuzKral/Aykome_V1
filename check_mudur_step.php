<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProcessStep;

echo "=== MÜDÜR ADIMI KONTROLÜ ===\n\n";

$mudurStep = ProcessStep::where('process_definition_id', 44)
    ->where('step_order', 3)
    ->first();

if (!$mudurStep) {
    echo "❌ Müdür adımı bulunamadı!\n";
    exit(1);
}

echo "📝 Müdür Adımı:\n";
echo "  - Adı: {$mudurStep->name}\n";
echo "  - role_key: {$mudurStep->role_key}\n";
echo "  - action_type: {$mudurStep->action_type}\n";
echo "  - roles: " . json_encode($mudurStep->roles) . "\n";
echo "\n";

if ($mudurStep->action_type === 'e_imza') {
    echo "⚠️  SORUN: action_type 'e_imza' olduğu için ONAY butonu yerine E-İMZA butonu çıkıyor!\n";
    echo "✅ ÇÖZÜM: Simülasyon için action_type'ı 'onay' yap, sonra e-imza testinde 'e_imza' yap\n\n";
    
    echo "Düzeltiliyor...\n";
    $mudurStep->action_type = 'onay'; // Test için
    $mudurStep->save();
    echo "✅ Müdür adımı 'onay' olarak güncellendi\n";
}

echo "\n=== TAMAMLANDI ===\n";
