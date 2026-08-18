<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Services\DocumentTemplateService as D;
use App\Support\AdresAyristirici;
use App\Support\AykomeMath;
use Tests\TestCase;

/**
 * ÇÖZÜM_11A regresyon testleri.
 *
 * §2 Boş alan → belgeye ham "{proje_kodu}" basılmaz (gerçek çıktı yolu strict).
 * §4 Metraj hiç yoksa Keşif Bedeli 0,00 (tek başına "361,00 TL" kalıntısı yok).
 * §6 Serbest metin adres MAHALLE / CADDE VE SOKAK sütunlarına ayrıştırılır.
 */
class DocumentTemplateCozum11ATest extends TestCase
{
    /** Kaydedilmemiş model: streetLines() DB'ye gitmesin (birim testi). */
    private function bosBasvuru(): Application
    {
        return new class extends Application
        {
            public function streetLines(): array
            {
                return [];
            }
        };
    }

    /* ── §2 — ham {token} basılmaz ───────────────────────────────────── */

    public function test_strict_modda_bos_katalog_alani_silinir(): void
    {
        $html = D::hydrateTemplateTokens(
            '<p>Proje: [{proje_kodu}] Kurum: [{kurum_adi}]</p>',
            $this->bosBasvuru(),
            'ruhsat',
            true
        );

        $this->assertStringNotContainsString('{proje_kodu}', $html);
        $this->assertStringNotContainsString('{kurum_adi}', $html);
        $this->assertStringContainsString('<p>Proje: [] Kurum: []</p>', $html);
    }

    public function test_strict_modda_katalogda_olmayan_token_korunur(): void
    {
        // Şablona elle yazılmış / yazım hatalı token'lar görünür kalmalı:
        // aksi hâlde şablon hatası sessizce yutulur.
        $html = D::hydrateTemplateTokens(
            '<p>{proje_kodlari}{bilinmeyen_alan}</p>',
            $this->bosBasvuru(),
            'ruhsat',
            true
        );

        $this->assertStringContainsString('{proje_kodlari}', $html);
        $this->assertStringContainsString('{bilinmeyen_alan}', $html);
    }

    public function test_editor_yolu_varsayilan_olarak_ham_tokeni_korur(): void
    {
        // strictEmpty varsayılanı false: editörde hangi alanın boş kaldığı görünür.
        $html = D::hydrateTemplateTokens('<p>{proje_kodu}</p>', $this->bosBasvuru(), 'ruhsat');

        $this->assertStringContainsString('{proje_kodu}', $html);
    }

    public function test_katalog_anahtarlari_strict_modda_taninir(): void
    {
        $m = (new \ReflectionClass(D::class))->getMethod('bilinenAlanMi');
        $m->setAccessible(true);

        foreach (D::fieldCatalog() as $grup) {
            foreach ($grup as $alan) {
                $this->assertTrue(
                    (bool) $m->invoke(null, $alan['key']),
                    "Katalog anahtarı strict modda tanınmıyor: {$alan['key']}"
                );
            }
        }

        $this->assertFalse((bool) $m->invoke(null, 'olmayan_alan_xyz'));
    }

    /* ── §4 — Keşif Bedeli kalıntısı ─────────────────────────────────── */

    public function test_metraj_yoksa_kesif_bedeli_sifir(): void
    {
        $fig = AykomeMath::compute([], []);

        $this->assertSame(0.0, $fig['discovery_fee']);
        $this->assertSame(0.0, $fig['ztb_total']);
        $this->assertSame(0.0, $fig['general_total']);
    }

    public function test_miktari_sifir_olan_satirlar_da_kesif_uretmez(): void
    {
        $fig = AykomeMath::compute([['quantity' => 0, 'price_per_m2' => 250]], []);

        $this->assertSame(0.0, $fig['discovery_fee']);
    }

    public function test_gercek_metrajda_kesif_formulu_degismedi(): void
    {
        $fig = AykomeMath::compute([['quantity' => 10, 'price_per_m2' => 100]], []);

        // 361 + (1000 * 0,01) = 371,00 → eski davranış birebir korunur.
        $this->assertSame(371.0, $fig['discovery_fee']);
        $this->assertSame(1000.0, $fig['ztb_amount']);
        $this->assertSame(1661.0, $fig['ztb_total']);
        $this->assertSame(2161.0, $fig['general_total']);
    }

    public function test_birim_fiyati_sifir_ama_metraji_olan_basvuruda_kesif_alinir(): void
    {
        // Zemin birim fiyatı tanımsız olsa bile keşif FİİLEN yapılmıştır.
        $fig = AykomeMath::compute([['quantity' => 25, 'price_per_m2' => 0]], []);

        $this->assertSame(361.0, $fig['discovery_fee']);
    }

    /* ── §6 — serbest metin adres ayrıştırma ─────────────────────────── */

    public function test_virgullu_serbest_adres_mahalle_ve_cadde_olarak_bolunur(): void
    {
        $p = AdresAyristirici::ayir('KADIKENDİ, 4151. SK, 41 FG, 63000 ŞANLIURFA M');

        $this->assertSame('KADIKENDİ', $p['mahalle']);
        $this->assertSame('4151. SK', $p['cadde']);
    }

    public function test_virgulsuz_adres_sokak_belirtecinden_bolunur(): void
    {
        $this->assertSame(
            ['mahalle' => 'KADIKENDİ', 'cadde' => '4151. SK'],
            AdresAyristirici::ayir('Kadıkendi 4151. Sk')
        );

        $this->assertSame(
            ['mahalle' => 'ATATÜRK MAH', 'cadde' => '1234. SOKAK'],
            AdresAyristirici::ayir('Atatürk Mah. 1234. Sokak No:5')
        );
    }

    public function test_kapi_bina_kodu_sutuna_yazilmaz(): void
    {
        // Gerçek saha verisi: kapı/bina kodu sokak adıyla AYNI parçada gelir.
        $this->assertSame(
            ['mahalle' => 'KADIKENDİ', 'cadde' => '4151. SK'],
            AdresAyristirici::ayir('Kadıkendi, 4151. Sk. 41 FG, 63000 Şanlıurfa M')
        );

        $this->assertSame(
            ['mahalle' => 'EYYÜBİYE', 'cadde' => '3112. SK'],
            AdresAyristirici::ayir('Eyyübiye, 3112. Sk. 32 A')
        );

        // Sokak adının ÖNÜNDEKİ numara korunur (Türkçe diziliş: "1234. SOKAK").
        $this->assertSame(
            ['mahalle' => 'ATATÜRK MAH', 'cadde' => '1234. SOKAK'],
            AdresAyristirici::ayir('Atatürk Mah. 1234. Sokak No:5')
        );
    }

    public function test_ayristirilamayan_adres_eski_davranista_kalir(): void
    {
        $p = AdresAyristirici::ayir('KIRSAL BÖLGE');

        // Uydurma veri yok: cadde boş kalır, metin mahallede durur.
        $this->assertSame('KIRSAL BÖLGE', $p['mahalle']);
        $this->assertSame('', $p['cadde']);
    }

    public function test_cok_satirli_adres_ozeti_birlestirir(): void
    {
        $p = AdresAyristirici::ozet("KADIKENDİ, 4151. SK\nBAĞLARBAŞI, 100. CAD\nKADIKENDİ, 4151. SK");

        $this->assertSame('KADIKENDİ, BAĞLARBAŞI', $p['mahalle']);
        $this->assertSame('4151. SK, 100. CAD', $p['cadde']);
    }

    public function test_bos_adres_bos_bilesenler_dondurur(): void
    {
        $this->assertSame(['mahalle' => '', 'cadde' => ''], AdresAyristirici::ozet(null));
        $this->assertSame(['mahalle' => '', 'cadde' => ''], AdresAyristirici::ayir('   '));
    }
}
