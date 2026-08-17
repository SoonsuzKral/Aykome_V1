<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Services\DocumentTemplateService as D;
use ReflectionClass;
use Tests\TestCase;

/**
 * ÇÖZÜM_10 (Ön Kazı üç hata) regresyon testleri.
 *
 * §1 Başkan Yardımcısı token'ları (Bilgi Alanları panelinden eklenebilir olmalı,
 *    değeri boşsa belgeye ham "{token}" basılmamalı).
 * §2 Doğrulama kodu satırı garantisi (her belgede olmalı, ASLA iki kez olmamalı,
 *    serbest konumlu Word şablonlarında alt boş banda absolute olarak oturmalı).
 * §3 print-bar artık position:sticky — akışta yer ayırır, A4'ün üst anteti/logosu
 *    barın altında kalmaz; yazdırma/PDF yolunda hiç yer kaplamaz.
 */
class DocumentTemplateCozum10Test extends TestCase
{
    private function svc(string $method, array $args = []): mixed
    {
        $m = (new ReflectionClass(D::class))->getMethod($method);
        $m->setAccessible(true);

        return $m->invokeArgs(null, $args);
    }

    /** Serbest konumlu (Word içe aktarımı) Ön Kazı düzeninin sadeleştirilmiş hâli. */
    private function serbestKonumHtml(): string
    {
        return '<img data-aykome-free-position="1" style="position:absolute;left:60px;top:7.234px;">'
            . '<p data-aykome-free-position="1" style="position:absolute;left:60px;top:400px;">Gövde</p>'
            . '<p data-aykome-free-position="1" style="position:absolute;left:513px;top:858.469px;">Başkan a.</p>'
            . '<p data-aykome-free-position="1" style="position:absolute;left:60px;top:1071.12px;">Bilgi için</p>';
    }

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

    /* ── §1 ─────────────────────────────────────────────────────────── */

    public function test_baskan_yardimcisi_alanlari_katalogda_var(): void
    {
        $keys = [];
        foreach (D::fieldCatalog() as $grup) {
            $keys = array_merge($keys, array_column($grup, 'key'));
        }

        $this->assertContains('baskan_yardimcisi_adi', $keys);
        $this->assertContains('baskan_yardimcisi_unvani', $keys);
    }

    public function test_imza_ayar_tipi_on_kazi_pre_permit_eslemesi(): void
    {
        // DocumentSignatorySetting kayıtları 'pre_permit' tipindedir.
        $this->assertSame('pre_permit', $this->svc('imzaAyarTipi', ['on_kazi']));
        $this->assertSame('pre_permit', $this->svc('imzaAyarTipi', [null]));
        $this->assertSame('ruhsat', $this->svc('imzaAyarTipi', ['ruhsat']));
    }

    public function test_imzalanmamis_baslangicta_ad_bos_unvan_dolu(): void
    {
        $app = $this->bosBasvuru();

        // İmza yok + tanımlı yetkili yok → uydurma isim ('Yetkili') ASLA basılmaz.
        $this->assertSame('', D::fieldValue($app, 'baskan_yardimcisi_adi', 'on_kazi'));
        $this->assertSame(
            'Belediye Başkan Yardımcısı',
            D::fieldValue($app, 'baskan_yardimcisi_unvani', 'on_kazi')
        );
    }

    public function test_bos_imza_tokeni_ham_basilmaz_bilinmeyen_token_korunur(): void
    {
        $html = D::hydrateTemplateTokens(
            '<p>[{baskan_yardimcisi_adi}]{bilinmeyen_alan}</p>',
            $this->bosBasvuru(),
            'on_kazi'
        );

        $this->assertStringContainsString('<p>[]', $html);
        $this->assertStringNotContainsString('{baskan_yardimcisi_adi}', $html);
        // Diğer bilinmeyen/boş token'ların eski davranışı korunur (dokunulmaz).
        $this->assertStringContainsString('{bilinmeyen_alan}', $html);
    }

    /* ── §2 ─────────────────────────────────────────────────────────── */

    public function test_parcalanmis_word_metninde_dogrulama_satiri_yakalanir(): void
    {
        $this->assertTrue(D::dogrulamaSatiriVar(
            '<td><span>BELGE&nbsp;</span><span>DOĞRULAMA</span><br>KODU: ABC123</td>'
        ));
        $this->assertFalse(D::dogrulamaSatiriVar('<p>Ön Kazı İzni Onayı</p>'));
    }

    public function test_dogrulama_satiri_eklenir_ve_tekrar_eklenmez(): void
    {
        $bir = D::belgeDogrulamaSatiriGaranti('<p>Ön Kazı İzni Onayı</p>');
        $iki = D::belgeDogrulamaSatiriGaranti($bir);

        $this->assertSame($bir, $iki, 'Garanti idempotent olmalı (yinelenen blok yok)');
        $this->assertSame(1, substr_count($bir, 'BELGE DOĞRULAMA KODU'));
        // Kod, hidrasyonda dolacak token olarak gömülür (sabit metin değil).
        $this->assertStringContainsString(D::DOGRULAMA_TOKEN, $bir);
    }

    public function test_akis_belgesinde_satir_normal_paragraf_olarak_eklenir(): void
    {
        $html = D::belgeDogrulamaSatiriGaranti('<p>Metin</p>');

        $this->assertStringNotContainsString('position:absolute', $html);
        $this->assertStringContainsString('<p contenteditable="true"', $html);
    }

    public function test_serbest_konumlu_sablonda_alt_bos_banda_yerlesir(): void
    {
        $html = D::belgeDogrulamaSatiriGaranti($this->serbestKonumHtml());

        // Boş bant 858.469 → 1071.12 (212.65px); %40'ı kadar aşağı = 943.53px.
        $this->assertStringContainsString('top:943.53px', $html);
        $this->assertStringContainsString('position:absolute', $html);
        // Blok, editörde de taşınabilir olsun (serbest konum çapası korunur).
        $this->assertStringContainsString('data-aykome-free-position="1"', $html);
        // Sarmalayıcı div KASITLI: 5070 kırmızı metni iç <p>'nin ÜSTÜNE girer.
        $this->assertStringContainsString('class="aykome-dogrulama-blok"', $html);
    }

    public function test_eimza_5070_metni_dogrulama_blogunun_icine_kodun_ustune_girer(): void
    {
        $html = D::belgeDogrulamaSatiriGaranti($this->serbestKonumHtml());

        $svc = new \App\Services\EImzaService();
        $m = (new ReflectionClass($svc))->getMethod('imzaYasalMetinEkle');
        $m->setAccessible(true);
        $imzali = (string) $m->invoke($svc, $html, new \DateTimeImmutable('2026-08-18 10:00'), 'pre_permit');
        // DOMDocument::saveHTML ASCII dışı harfleri entity'ye çevirir (Ğ → &#286;);
        // konum karşılaştırması için düz metne döndürülür (çıktı dompdf'te doğrudur).
        $imzali = html_entity_decode($imzali, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $p5070 = mb_strpos($imzali, '5070');
        $pKod = mb_strpos($imzali, 'BELGE DOĞRULAMA KODU');

        $this->assertNotFalse($p5070, '5070 yasal metni eklenmeli');
        $this->assertNotFalse($pKod);
        $this->assertLessThan($pKod, $p5070, '5070 metni doğrulama kodunun ÜSTÜNDE olmalı');
        // Aynı absolute blokta kalmalı (akışa/sayfa dışına düşmemeli).
        $this->assertMatchesRegularExpression('/aykome-dogrulama-blok.*5070/s', $imzali);
    }

    /* ── §3 ─────────────────────────────────────────────────────────── */

    public function test_print_bar_sticky_ve_yazdirmada_gizli(): void
    {
        $ui = (string) $this->svc('wrapStandalone', ['on_kazi', '', '<p>x</p>', true]);

        // Barın CSS kuralları: fixed DEĞİL (akıştan çıkıp anteti ezmesin), sticky.
        preg_match_all('/\.print-bar\s*\{[^}]*\}/', $ui, $kurallar);
        $this->assertNotEmpty($kurallar[0], '.print-bar kuralı bulunamadı');
        foreach ($kurallar[0] as $kural) {
            $this->assertStringNotContainsString('fixed', $kural);
        }
        $this->assertMatchesRegularExpression('/\.print-bar\s*\{[^}]*position:\s*sticky/', $ui);
        // Bar yazdırma/PDF'te hiç yer kaplamaz.
        $this->assertStringContainsString('.print-bar,.no-print,.no-print-bar,.toolbar{display:none !important;}', $ui);
    }

    public function test_pdf_yolunda_ui_bar_hic_uretilmez(): void
    {
        $pdf = (string) $this->svc('wrapStandalone', ['on_kazi', '', '<p>x</p>', false]);

        $this->assertStringNotContainsString('class="print-bar', $pdf);
        $this->assertStringNotContainsString('window.print()', $pdf);
    }
}
