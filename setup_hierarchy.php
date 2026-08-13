<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProcessStep;

echo "=== HİYERARŞİ SİSTEMİ KURULUMU ===\n\n";

$steps = ProcessStep::where('process_definition_id', 44)
    ->orderBy('step_order')
    ->get();

echo "📊 Mevcut Adımlar:\n";
foreach ($steps as $step) {
    echo "  {$step->step_order}. {$step->name} (role: {$step->role_key})\n";
}

echo "\n⚙️ Hiyerarşi Yapısı Kurgulanıyor:\n\n";

/*
 * HİYERARŞİ MANTIĞI:
 * 
 * municipality-makam (Başkan Yrd.) → TOP_ROLES'e ekle ama restricted
 * municipality-mudur (Müdür) → Alt adımları da onaylayabilir
 * municipality-sef (Şef) → Sadece kendi ve personel
 * municipality-buro (Personel) → Sadece kendi
 */

// 1. Personel Adımı - Sadece kendi rolü
$personelStep = $steps->where('step_order', 1)->first();
$personelStep->roles = ['municipality-buro'];
$personelStep->save();
echo "  ✓ Personel: Sadece municipality-buro\n";

// 2. Şef Adımı - Kendi + Personel
$sefStep = $steps->where('step_order', 2)->first();
$sefStep->roles = [
    'municipality-sef',
    'municipality-buro', // Şef personelin işini de yapabilir
];
$sefStep->save();
echo "  ✓ Şef: municipality-sef + municipality-buro (personel işini de yapar)\n";

// 3. Müdür Adımı - Kendi + Şef + Personel
$mudurStep = $steps->where('step_order', 3)->first();
$mudurStep->roles = [
    'municipality-mudur',
    'municipality-sef',  // Müdür şefin işini de yapabilir
    'municipality-buro', // Müdür personelin işini de yapabilir
];
$mudurStep->save();
echo "  ✓ Müdür: municipality-mudur + municipality-sef + municipality-buro (hepsini yapar)\n";

// 4. Başkan Yardımcısı - Sadece kendi
$baskanStep = $steps->where('step_order', 4)->first();
$baskanStep->roles = ['municipality-makam'];
$baskanStep->save();
echo "  ✓ Başkan Yardımcısı: Sadece municipality-makam (başka adım yapamaz)\n";

echo "\n=== HİYERARŞİ KURULDU ===\n\n";

echo "📋 Yetki Matrisi:\n";
echo "  • Personel → [1. Personel Onayı]\n";
echo "  • Şef → [1. Personel Onayı, 2. Şef Paraf]\n";
echo "  • Müdür → [1. Personel Onayı, 2. Şef Paraf, 3. Müdür Onayı]\n";
echo "  • Başkan Yrd. → [4. Başkan Onayı] (sadece kendi)\n";
