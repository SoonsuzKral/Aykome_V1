<?php

namespace Tests\Unit;

use App\Services\DocumentTemplateService as D;
use ReflectionClass;
use Tests\TestCase;

/**
 * ÇÖZÜM_09 §1 + §2 regresyon testleri.
 *
 * §1: Bilgi Alanları'na eklenen belge/yazdırma tarihi anahtarları.
 * §2: Tarayıcı görüntülemesinin A4 konteyner geometrisi editördeki
 *     #doc-editor ile aynı olmalı; dompdf (withUi=false) yolu ise ASLA
 *     bu bloktan etkilenmemeli (imzalı PDF çıktısı sabit kalır).
 */
class DocumentTemplateGeometryTest extends TestCase
{
    private function svc(string $method, array $args = []): mixed
    {
        $m = (new ReflectionClass(D::class))->getMethod($method);
        $m->setAccessible(true);

        return $m->invokeArgs(null, $args);
    }

    public function test_tarih_alanlari_katalogda_var(): void
    {
        $keys = array_column($this->svc('fieldCatalog')['Tarihler'] ?? [], 'key');

        $this->assertContains('belge_tarihi', $keys);
        $this->assertContains('yazdirma_tarihi', $keys);
    }

    public function test_dikey_konteyner_editor_geometrisiyle_ayni(): void
    {
        $css = $this->svc('browserA4ContainerCss', [false]);

        // Serbest konum bloklarının (data-aykome-free-position) çapası
        $this->assertStringContainsString('position:relative !important', $css);
        $this->assertStringContainsString('width:210mm !important', $css);
        $this->assertStringContainsString('min-height:297mm !important', $css);
        // Padding TEK kaynaktan gelir; editör de aynı sabiti kullanır
        $this->assertStringContainsString('padding:' . D::A4_CONTAINER_PADDING . ' !important', $css);
        // Ekran ve yazdırma aynı koordinat sistemini kullanır
        $this->assertStringContainsString('@media print', $css);
    }

    public function test_yatay_konteyner_297mm(): void
    {
        $css = $this->svc('browserA4ContainerCss', [true]);

        $this->assertStringContainsString('width:297mm !important', $css);
        $this->assertStringContainsString('min-height:210mm !important', $css);
        $this->assertStringContainsString('size:A4 landscape', $css);
    }

    public function test_tarayici_cikisi_blogu_icerir_pdf_cikisi_icermez(): void
    {
        $ui = $this->svc('wrapStandalone', ['on_kazi', '', '<p>x</p>', true]);
        $pdf = $this->svc('wrapStandalone', ['on_kazi', '', '<p>x</p>', false]);

        $this->assertStringContainsString('position:relative !important', $ui);
        // dompdf yolunda inline width:174mm'i ezecek hiçbir !important kural olmamalı
        $this->assertStringNotContainsString('position:relative !important', $pdf);
        $this->assertStringNotContainsString('width:210mm !important', $pdf);
    }
}
