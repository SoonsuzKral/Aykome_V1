<?php

namespace App\Services;

use App\Models\Application;
use App\Models\EImzaTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EImzaService
{
    /**
     * GÖREV 6 — Giriş yapmış kullanıcıdan imzalayan bilgisini otomatik türetir.
     * UI'dan imzalayan formu sorulmaz; ad/soyad users.name, unvan ise kullanıcının
     * Spatie rolünden Türkçe karşılığıyla alınır. Ad her imzada belgeye yazılır.
     */
    public static function kullanicidanImzalayan(\App\Models\User $user): array
    {
        $tamAd = trim((string) $user->name);
        $boluk = mb_strrpos($tamAd, ' ');
        if ($boluk !== false) {
            $ad = mb_substr($tamAd, 0, $boluk);
            $soyad = mb_substr($tamAd, $boluk + 1);
        } else {
            $ad = $tamAd;
            $soyad = '';
        }

        $unvanMap = [
            'municipality-makam'    => 'Belediye Başkan Yardımcısı',
            'municipality-admin'    => 'Belediye Başkanı',
            'municipality-mudur'    => 'Fen İşleri Müdürü',
            'municipality-sef'      => 'Aykome Birim Şefi',
            'municipality-buro'     => 'Büro Personeli (Paraf)',
            'municipality-staff'    => 'Belediye Personeli',
            'institution-admin'     => 'Kurum Yöneticisi',
            'institution-manager'   => 'Kurum Yöneticisi',
            'institution-staff'     => 'Kurum Personeli',
            'field-team'            => 'Saha Personeli',
            'super-admin'           => 'Super Admin',
        ];

        $unvan = 'Personel';
        foreach (array_keys($unvanMap) as $rol) {
            if ($user->hasRole($rol)) {
                $unvan = $unvanMap[$rol];
                break;
            }
        }

        return [
            'ad' => $ad,
            'soyad' => $soyad,
            'unvan' => $unvan,
            'ad_yazilsin' => true,
        ];
    }

    public function baslat(Application $application, string $pdfType, ?array $imzalayan = null): EImzaTransaction
    {
        $transactionId = 'txn_' . Str::uuid();
        $token = hash_hmac('sha256', $transactionId, config('app.key'));

        // GÖREV 1 — Görsel EBYS damga bloğu kaldırıldı. E-imza belgeye görsel bir
        // müdahale yapmaz; belge şablondan nasıl üretildiyse birebir korunur. İmzaya
        // dair imzalayan bilgisi belge ALTINA basılmaz, güvenlik backend token-CN
        // eşleşmesiyle sağlanır (bkz. verifyCertificateOwner).
        //
        // GÖREV B — 5070 sayılı yasal metin: imza ANI sabitlenir ($imzaTarihi = now()).
        // Metin render'ın İÇİNDE (dompdf'e gitmeden önce) tek geçişli enjekte edilir;
        // imza sonrası hiçbir görsel ekleme yapılmaz.
        $imzaTarihi = now();
        $pdf = $this->pdfOlustur($application, $pdfType, null, $imzaTarihi);
        $dizin = "e-imza/{$transactionId}";
        Storage::disk('public')->makeDirectory($dizin);
        $pdfPath = "{$dizin}/orijinal.pdf";
        Storage::disk('public')->put($pdfPath, $pdf->output());

        return EImzaTransaction::create([
            'application_id' => $application->id,
            'pdf_type' => $pdfType,
            'status' => 'pending',
            'transaction_id' => $transactionId,
            'token' => $token,
            'orijinal_pdf' => $pdfPath,
            'expires_at' => now()->addMinutes(10),
            // GÖREV 2 — Token-CN güvenlik kilidi için baslatan kullanıcı transaction'a
            // bağlanır. tamamla route'u auth'suz (api-key) olduğundan auth()->user()
            // orada boş döner; bu yüzden imzayı BAŞLATAN kullanıcı burada sabitlenir.
            'imzalayan_info' => array_merge(
                $imzalayan ?? [],
                ['baslatan_user_id' => auth()->id(), 'baslatan_user_name' => auth()->user()?->name ?? '']
            ),
        ]);
    }

    /**
     * GÖREV B — 5070 sayılı yasal metni render INDE, dompdf'e gitmeden önce
     * tek geçişli enjekte eder.
     *
     * Bu metod çağrıldığında imzalanacak PDF HENÜZ üretilmedi: HTML henüz
     * dompdf'e verilmedi. 5070 metnini e-imza akışı dışındaki önizleme/inceleme
     * render'larına enjekte ETMEZ (imzalama zamanlaması imza_tarihi == now()).
     *
     * Yerleşim (pdfType'a göre):
     *   GRUP A (ruhsat, pre_permit, cover_letter) — BELGE DOĞRULAMA kodunun
     *   ÜSTÜNE. font-size VERİLMEZ → container/squeeze boyutunu (10.5px) miras
     *   alır, doğrulama satırıyla AYNI font boyutunda basılır.
     *   GRUP B (metraj, makbuz, tahakkuk, taahhutname) — belgenin EN ALTINA:
     *   1) a4-container / a4-landscape-container içine (en sona),
     *   2) yoksa .footer-note SONRASINA (makbuz gibi container'sız belgeler),
     *   3) son çare gövde sonu. Metin asla kaybolmaz.
     *
     * @param string                $html       dompdf'e verilecek HTML
     * @param \DateTimeInterface|null $imzaTarihi İmza anı (now()); null ise enjeksiyon yapılmaz
     * @param string                $pdfType    Belge tipi (yerleşim grubunu belirler)
     * @return string
     */
    protected function imzaYasalMetinEkle(string $html, ?\DateTimeInterface $imzaTarihi, string $pdfType = 'ruhsat'): string
    {
        if ($imzaTarihi === null) {
            return $html;
        }

        $metin = sprintf(
            'Bu çıktı, 5070 sayılı elektronik imza kanununa göre imzalanan belgenin %s tarihli kağıt kopyasıdır. Bu belge güvenli elektronik imza ile imzalanmıştır.',
            $imzaTarihi->format('d.m.Y H:i')
        );

        // font-size VERİLMEZ: kırmızı 5070 metni belgenin font boyutunu (squeeze
        // sonrası 10.5px) miras alır → doğrulama satırıyla birebir aynı boyut.
        $blok = '<p style="color:#c0392b; text-align:center; font-weight:bold; margin:6px 0 0; line-height:1.15;">'
            . e($metin)
            . '</p>';

        // GRUP A: doğrulama kodu taşıyan belgelerde 5070 kodun HEMEN ÜSTÜNE düşer.
        $grupA = in_array($pdfType, ['ruhsat', 'pre_permit', 'cover_letter'], true);

        // POST işleme KESİNLİKLE değil: metin dompdf'e verilecek HTML'e, render
        // öncesi aşılanır. DOMDocument + XPath ile tutarlı yerleşim kurulur.
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);

        if ($loaded) {
            $xpath = new \DOMXPath($doc);
            $fragment = $doc->createDocumentFragment();
            $fragment->appendXML($blok);

            if ($grupA) {
                // GRUP A — doğrulama kodunun HEMEN üstü.
                // DİKKAT: XPath translate() yalnızca ASCII harfleri küçültür; Türkçe
                // "DOĞRULAMA"daki Ğ büyük kalır ve eşleşme ASLA olmaz (5070 yanlış
                // yere, container sonuna düşerdi). Çözüm: XPath yalnızca hızlı filtre
                // yapar; kesin eşleşme PHP mb_stripos ile yapılır. Son eşleşme =
                // doküman sırasında en derin eleman = doğrulama satırının kendisi.
                $nodeList = $xpath->query('//*[contains(., "DOĞRULAMA") or contains(., "doğrulama") or contains(., "Doğrulama")]');
                $hedef = null;
                foreach ($nodeList as $el) {
                    if (mb_stripos($el->textContent, 'belge doğrulama kodu') !== false) {
                        $hedef = $el;
                    }
                }
                if ($hedef) {
                    $hedef->parentNode->insertBefore($fragment, $hedef);
                    return $doc->saveHTML();
                }
                // Doğrulama yoksa Grup B yerleşimine düş — metin asla kaybolmaz.
            }

            // GRUP B — belgenin EN ALTINA:
            // 1) a4-container / a4-landscape-container İÇİNE (en sona) — son
            //    tablonun ÜSTÜNE değil, BELGESİN SONUNA eklenir (metraj imza
            //    tablosunun ALTINDA kalır). Metin ASLA body'ye doğrudan eklenmez:
            //    belgenin dışına düşer ve ayrı bir PDF sayfası yaratır.
            $container = null;
            foreach ($doc->getElementsByTagName('div') as $div) {
                $cls = (string) $div->getAttribute('class');
                if (str_contains($cls, 'a4-container') || str_contains($cls, 'a4-landscape-container')) {
                    $container = $div;
                    break;
                }
            }
            if ($container) {
                // ÇÖZÜM_06: istenen sıra (tüm tiplerde) — kırmızı 5070 metni ÜSTTE,
                // BELGE DOĞRULAMA KODU satırı hemen ALTINDA. Container içinde
                // doğrulama satırı varsa (tahakkuk/metraj) 5070 onun ÜSTÜNE
                // yerleştirilir; yoksa (makbuz/taahhutname) container sonuna
                // eklenir (eski davranış korunur).
                $dogrulamaElemani = null;
                foreach ($container->getElementsByTagName('*') as $el) {
                    if (mb_stripos($el->textContent, 'belge doğrulama kodu') !== false) {
                        $dogrulamaElemani = $el;
                    }
                }
                if ($dogrulamaElemani) {
                    $dogrulamaElemani->parentNode->insertBefore($fragment, $dogrulamaElemani);
                } else {
                    $container->appendChild($fragment);
                }
            } else {
                // Container'sız belge (örn. tahsilat makbuzu): .footer-note
                // SONRASINA ekle → yasal metin en altta, nota göre bile altta kalır.
                $footerNote = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " footer-note ")]');
                if ($footerNote && $footerNote->length > 0) {
                    $note = $footerNote->item(0);
                    $note->parentNode->insertBefore($fragment, $note->nextSibling);
                } else {
                    $body = $doc->getElementsByTagName('body')->item(0);
                    if ($body) {
                        $body->appendChild($fragment);
                    }
                }
            }

            $html = $doc->saveHTML();
        } else {
            // DOM çözümleme başarısız olursa metin asla kaybolmasın — fallback string ekleme.
            $html = str_ireplace('</body>', $blok . '</body>', $html);
        }

        return $html;
    }

    /**
     * Kurum logosunu base64 data URI olarak döndürür (dompdf enable_remote=false
     * olduğu için uzak URL yüklenmez; data URI her zaman basılır). Logo yoksa null.
     */
    private static function institutionLogoBase64(Application $application): ?string
    {
        if (! $application->institution || ! $application->institution->logo_path) {
            return null;
        }
        try {
            $fileContent = \Illuminate\Support\Facades\Storage::disk('public')->get($application->institution->logo_path);
            if (! $fileContent) {
                return null;
            }
            $mime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($application->institution->logo_path);

            return 'data:' . $mime . ';base64,' . base64_encode($fileContent);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Belediye kurum logosunu base64 data URI olarak döndürür. Ön Kazı İzin
     * belgesi belediye adına düzenlendiği için başvurunun kurumunun logosu
     * değil, belediyenin kendi logosu basılır (is_municipality=true kurum).
     * public: ApplicationsController::downloadPrePermit de aynı zinciri kullanır.
     */
    public static function belediyeLogoBase64(): ?string
    {
        $belediye = \App\Models\Institution::query()
            ->where('is_municipality', true)
            ->whereNotNull('logo_path')
            ->orderBy('id')
            ->first();

        if (! $belediye || ! $belediye->logo_path) {
            return null;
        }
        try {
            $fileContent = \Illuminate\Support\Facades\Storage::disk('public')->get($belediye->logo_path);
            if (! $fileContent) {
                return null;
            }
            $mime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($belediye->logo_path);

            return 'data:' . $mime . ';base64,' . base64_encode($fileContent);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Belge PDF'ini üretir. GÖREV 1: Görsel imza damgası kaldırıldığı için belge
     * şablondan nasıl render edildiyse birebir korunur (görsel müdahale YOK).
     *
     * @param array|null $imzaDamgasi @deprecated İmza damgası kaldırıldı; parametre
     *                                geriye dönük uyumluluk (BC) için tutulur, hiç basılmaz.
     */
    public function pdfOlustur(
        Application $application,
        string $pdfType,
        ?array $imzaDamgasi = null,
        ?\DateTimeInterface $imzaTarihi = null
    ): \Barryvdh\DomPDF\PDF
    {
        $application->load([
            'institution', 'creator',
            'surfaceLines.surfaceType',
            'gisNoktalari',
            'gisCizimleri.yolIliskileri',
        ]);

        // Önce editörde düzenlenen şablonu (override → global) kullan; yoksa blade akışına düş
        $map = [
            'ruhsat'       => 'ruhsat',
            'pre_permit'   => 'on_kazi',
            'taahhutname'  => 'taahhutname',
            'metraj'       => 'metraj',
            'tahakkuk'     => 'tahakkuk',
            'makbuz'       => 'makbuz',
            'cover_letter' => 'cover_letter',
        ];
        $mapped = $map[$pdfType] ?? null;
        if ($mapped !== null) {
            // GÖREV 2: PDF render'da print-bar/butonlar üretilmez ($withUi=false).
            $html = DocumentTemplateService::renderFor($mapped, $application, false, false);
            if ($html !== null) {
                // SORUN B: Şablon yolunda cover_letter logosu hiç basılmıyordu —
                // önizleme (downloadCoverLetter) ile birebir olması için aynı
                // desenle enjekte edilir (logo data URI → dompdf'te yüklenir).
                // pre_permit BELEDİYE belgesidir → belediye logo önceliği uygulanır.
                // 16.08 13. tur: içerikte ZATEN bir <img> varsa (Word'den logo/QR ile
                // birlikte içe aktarılmış bir taslak — artık resimler korunuyor,
                // bkz. DocumentTemplateService::sanitizeWordImportHtml) otomatik
                // enjeksiyon ATLANIR, aksi halde iki logo üst üste binerdi.
                if (in_array($pdfType, ['cover_letter', 'pre_permit'], true)
                    && str_contains($html, '<div class="a4-container">')
                    && stripos($html, '<img') === false) {
                    $logoBase64 = null;
                    if ($pdfType === 'pre_permit') {
                        $logoBase64 = \App\Models\PreExcavationPermitSetting::toBase64DataUri(
                            \App\Models\PreExcavationPermitSetting::first()?->logo_path
                        );
                    }
                    if (! $logoBase64) {
                        $logoBase64 = self::belediyeLogoBase64();
                    }
                    // ÖN KAZI HER ZAMAN MERKEZ BELEDİYE adına çıkar → başvuran
                    // kurumun logosuna (institution) ASLA düşülmez; belediye logosu
                    // da yoksa blade "Eyyübiye Belediyesi" yazı fallback'i kullanır.
                    if (! $logoBase64 && $pdfType !== 'pre_permit') {
                        $logoBase64 = self::institutionLogoBase64($application);
                    }
                    if ($logoBase64) {
                        $logoBlock = '<div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">'
                            . '<img src="' . $logoBase64 . '" alt="Kurum Logosu" style="max-height:85px;width:auto;">'
                            . '</div>';
                        $html = str_replace('<div class="a4-container">', '<div class="a4-container">' . $logoBlock, $html);
                    }
                }

                $paper = ! empty(DocumentTemplateService::TYPES[$mapped]['landscape']) ? 'landscape' : 'portrait';

                // GÖREV 1+2: UI kalıntıları temizlenir + font DejaVu'ya sabitlenir.
                // GÖREV 1: Görsel EBYS imza damgası kaldırıldı — belge birebir korunur.
                $html = DocumentTemplateService::pdfCssEnjekte($html);
                $html = self::pdfTipineGoreEkCss($html, $pdfType);

                // GÖREV B — 5070 yasal metni: imzalama akışında render sonu tek geçişli.
                $html = $this->imzaYasalMetinEkle($html, $imzaTarihi, $pdfType);

                return Pdf::loadHTML($html)->setPaper('a4', $paper);
            }
        }

        $view = match ($pdfType) {
            'ruhsat' => 'admin.pdf.ruhsat',
            'pre_permit' => 'admin.pdf.pre_permit',
            'taahhutname' => 'admin.pdf.taahhutname',
            'metraj' => 'admin.pdf.metraj',
            'tahakkuk' => 'admin.pdf.tahakkuk',
            'makbuz' => 'admin.pdf.tahsilat_makbuzu',
            'cover_letter' => 'admin.pdf.cover_letter',
            default => throw new \InvalidArgumentException("Geçersiz PDF türü: {$pdfType}"),
        };

        $data = array_merge($application->toArray(), [
            'application' => $application,
            'appNo' => $application->application_no,
            'institution' => $application->institution,
            // GÖREV 5 — İmza yerleşimi süreç adımlarına göre: tamamlanan adımın
            // onaylayanının adı dolar, tamamlanmamış adımların adı BOŞ kalır
            // ("Başkan Yardımcısı" gibi dinamik pozisyonlar imzalayana kadar boş).
            'signatories' => app(\App\Services\SignerPlacementService::class)
                ->yerlesimHazirla($application, $pdfType),
        ]);

        // Logo: cover_letter + pre_permit — remote URL dompdf'te YÜKLENMEZ
        // (config/dompdf.php enable_remote=false) → logo base64 data URI olarak
        // verilir. Ön Kazı İzin belgesi BELEDİYE adına düzenlenir → logo önceliği:
        // 1) PreExcavationPermitSetting.logo_path (belediyenin ön kazı özel logosu)
        // 2) Belediye kurum logosu (is_municipality=true olan kurum — başvurunun
        //    kurumu DEĞİL; ör. Dicle başvurusunda Merkez Belediye logosu basılır)
        // 3) Başvuru kurumunun logosu (fallback).
        if (in_array($pdfType, ['cover_letter', 'pre_permit'], true)) {
            $logoBase64 = null;
            if ($pdfType === 'pre_permit') {
                $logoBase64 = \App\Models\PreExcavationPermitSetting::toBase64DataUri(
                    \App\Models\PreExcavationPermitSetting::first()?->logo_path
                );
            }
            if (! $logoBase64) {
                $logoBase64 = self::belediyeLogoBase64();
            }
            // ÖN KAZI HER ZAMAN MERKEZ BELEDİYE adına çıkar → başvuran kurumun
            // logosuna (institution) ASLA düşülmez; belediye logosu da yoksa
            // blade "Eyyübiye Belediyesi" yazı fallback'i kullanır.
            if (! $logoBase64 && $pdfType !== 'pre_permit') {
                $logoBase64 = self::institutionLogoBase64($application);
            }
            $data['logo_base64'] = $logoBase64;
        }

        if ($pdfType === 'pre_permit') {
            $data['metin'] = DocumentRenderer::prePermitMetin($application);
        }

        // TAHARRUK / matbu form: TÜM zemin tipleri listelenir (başvuruda olmayanlar
        // sıfır satırı olarak görünür). buildMetrajSatirlari SurfaceType::all() ile
        // tam liste üretir; verilmezse blade yalnızca başvurunun kendi satırlarını
        // gösterir (zemin tiplerinin tamamı çıkmaz).
        if ($pdfType === 'tahakkuk') {
            $data['metraj_satirlari'] = \App\Http\Controllers\Admin\ApplicationsController::buildMetrajSatirlari($application);
        }

        if ($pdfType === 'metraj') {
            $rows = $this->buildMetrajRows($application);
            $toplamM2 = 0;
            foreach ($rows as $r) {
                $toplamM2 += (float) str_replace(['.', ','], ['', '.'], $r['m2']);
            }
            $data = array_merge($data, [
                'rows' => $rows,
                'toplam_m2' => number_format($toplamM2, 2, ',', '.'),
                'kurum' => mb_strtoupper($application->institution?->name ?? 'KURUM', 'UTF-8'),
                'alici' => 'EYYÜBİYE BELEDİYE BAŞKANLIĞI FEN İŞLERİ MÜDÜRLÜĞÜ AYKOME BİRİMİ',
                'proje_kodu' => $application->project_code ?? '',
                'tarih' => now()->format('d.m.Y'),
                'ilce' => $application->district ?? '',
                'firma' => mb_strtoupper($application->institution?->name ?? 'KURUM', 'UTF-8'),
                'is_cinsi' => $application->description ?? '',
                'talep_sahibi' => mb_strtoupper(
                    trim($application->institution?->engineer_name ?? 'Yetkili Görevli'),
                    'UTF-8'
                ),
            ]);
        }

        $html = view($view, $data)->render();

        // GÖREV 1+2: blade içindeki print-bar/toolbar kalıntıları + Latin-1 fontlar temizlenir.
        // GÖREV 1: Görsel EBYS imza damgası kaldırıldı — belge birebir korunur.
        $html = DocumentTemplateService::pdfCssEnjekte($html);
        $html = self::pdfTipineGoreEkCss($html, $pdfType);

        // GÖREV B — 5070 yasal metni: imzalama akışında render sonu tek geçişli.
        $html = $this->imzaYasalMetinEkle($html, $imzaTarihi, $pdfType);

        return Pdf::loadHTML($html);
    }

    /**
     * Tip bazlı ek CSS (pdfCssEnjekte'den SONRA, onun kurallarını ezmek için).
     *
     * cover_letter: alt blok (imza + doğrulama) `.a4-footer` ABSOLUTE'dir
     * (container dibine sabit). Squeeze container'ı min-height:0 yapınca
     * container = içerik yüksekliği olur ve footer içeriğin ÜSTÜNE biner.
     * Container tam sayfa yüksekliğine (@page 6mm kenar → 285mm) döndürülür,
     * genişlik padding toplamıyla iç alana sığacak şekilde daraltılır.
     *
     * taahhutname: 20 maddelik liste uzun — tek sayfa için satır aralığı ve
     * punto squeeze edilir (içerik hâlâ okunaklı).
     *
     * tahakkuk: ÇÖZÜM_02 — sayfa sonu ~72mm boş kalıyordu (zemin tipleri
     * tablosu kısa). Yalnızca bu tipe özel dolgu: punto 10.5→11px ve
     * hücre padding 1×3→2×4px → tablo ~18mm yükselir, alt boşluk azalır.
     * ruhsat gibi dikey payı dar olan tipler ETKİLENMEZ (dal tip bazlı).
     */
    protected static function pdfTipineGoreEkCss(string $html, string $pdfType): string
    {
        if ($pdfType === 'cover_letter') {
            // dompdf ABSOLUTE elemanlarda `bottom` konumlandırmayı UYGULAMAZ:
            // footer'ı flow'daki statik konuma (container sonu) yerleştirir.
            // Container min-height:285mm yapılınca statik konum sayfa 2'ye düştü.
            // Doğru çözüm: container'ı zorlamamak (min-height:0) + footer'ı
            // STATIC yapmak → footer içeriğin hemen altına, SAME SAYFADA düşer.
            // Punto/satır aralığı ve üst blok boşlukları da tek sayfa için daraltılır.
            return str_ireplace(
                '</head>',
                '<style>.a4-container { width: 174mm !important; }'
                . '.a4-container .a4-footer { position: static !important; margin-top: 14mm !important; }'
                . '.a4-container p { font-size: 10px !important; line-height: 1.3 !important; }'
                . '.a4-container .sayi-konu-tablo { margin-top: 25px !important; margin-bottom: 25px !important; }'
                . '.a4-container .text-center.font-bold { margin-bottom: 20px !important; }'
                . '.a4-container .list-table { margin-bottom: 10px !important; }</style></head>',
                $html
            );
        }

        if ($pdfType === 'pre_permit') {
            // pdfCssEnjekte 10.5px global font + 6mm @page uygular — pre_permit
            // kendi 12pt/15mm değerlerini geri alır (ezme). İmza+altbilgi+footer
            // .sayfa-alti-wrapper position:absolute;bottom:0 ile sayfanın
            // dibine sabitlenir — dompdf bu konumlandırmayı destekler.
            return str_ireplace(
                '</head>',
                '<style>'
                . '@page { margin: 15mm !important; }'
                . 'body { font-size: 11pt !important; font-family: "DejaVu Sans", sans-serif !important; }'
                . '.header .tc { font-size: 13pt !important; }'
                . '.header .belediye { font-size: 14pt !important; }'
                . '.header .mudurluk { font-size: 12pt !important; }'
                . '.info-row { font-size: 11pt !important; margin-top: 22px !important; }'
                . '.alici { font-size: 11pt !important; margin-top: 22px !important; }'
                . '.ilgi { font-size: 11pt !important; margin-top: 14px !important; }'
                . '.paragraf { font-size: 11pt !important; line-height: 1.6 !important; margin-top: 18px !important; }'
                . '.paragraf p { font-size: 11pt !important; margin-bottom: 6px !important; }'
                // .a4-container: position:relative yap ki absolute child içinde konumlansın.
                // min-height 267mm = 297mm − 2×15mm (page margin). İçerik kısa olsa
                // bile .sayfa-alti-wrapper containerın dibinde görünür.
                . '.a4-container { position: relative !important; min-height: 267mm !important; padding-bottom: 80mm !important; }'
                // Sayfa altı wrapper: dompdf'te absolute;bottom:0 sayfa dibine sabitler.
                // Web önizlemede a4-container relative olduğu için container dibi = 267mm.
                . '.sayfa-alti-wrapper { position: absolute !important; bottom: 0 !important; left: 12mm !important; right: 12mm !important; }'
                . '.imza { margin-top: 0 !important; font-size: 11pt !important; }'
                . '.altbilgi { margin-top: 12px !important; font-size: 8pt !important; }'
                . '.footer-dogrulama { margin-top: 6px !important; font-size: 8px !important; }'
                . '.footer-sayfa { margin-top: 4px !important; font-size: 9pt !important; }'
                . '</style></head>',
                $html
            );
        }

        if ($pdfType === 'taahhutname') {
            return str_ireplace(
                '</head>',
                '<style>.a4-container .madde-list { line-height: 1.3 !important; }'
                . '.a4-container .madde-list p { font-size: 9px !important; line-height: 1.25 !important; margin-bottom: 3px !important; }'
                . '.a4-container .beyan, .a4-container .not { line-height: 1.3 !important; }'
                . '.a4-container .imza-alani { margin-top: 12pt !important; }'
                . '.a4-container .imza-cizgi { margin-top: 16pt !important; }</style></head>',
                $html
            );
        }

        if ($pdfType === 'tahakkuk') {
            // ÇÖZÜM_02: Sadece tahakkuk'ta sayfa altı dolgusu — punto ve hücre
            // padding'i artırılır (pdfCssEnjekte'den SONRA geldiği için !important
            // kurallarını ezer; yalnızca bu tipin HTML'ine girer).
            return str_ireplace(
                '</head>',
                '<style>.a4-container { font-size: 11px !important; }'
                . '.a4-container td, .a4-container th { padding: 2px 4px !important; font-size: 11px !important; line-height: 1.3 !important; }'
                . '.a4-container .bilgi-grid table, .a4-container .toplamlar table { width: 100% !important; }</style></head>',
                $html
            );
        }

        return $html;
    }

    private function buildMetrajRows(Application $app): array
    {
        $rows = [];
        $sira = 0;
        $ilce = $app->district ?? 'EYYÜBİYE';
        $projeKodu = $app->project_code ?? '';
        $tarih = $app->start_date?->format('d.m.Y') ?? '';

        $mahalleList = [];
        $caddeList = [];

        if ($app->relationLoaded('gisNoktalari')) {
            foreach ($app->gisNoktalari as $n) {
                if ($n->mahalle) $mahalleList[] = mb_strtoupper(trim($n->mahalle), 'UTF-8');
            }
        }
        if ($app->relationLoaded('gisCizimleri')) {
            foreach ($app->gisCizimleri as $c) {
                foreach ($c->yolIliskileri as $y) {
                    if ($y->mahalle) $mahalleList[] = mb_strtoupper(trim($y->mahalle), 'UTF-8');
                    if ($y->yol_adi) $caddeList[] = mb_strtoupper(trim($y->yol_adi), 'UTF-8');
                }
            }
        }
        $mahalle = $mahalleList ? implode(', ', array_unique($mahalleList)) : ($app->address_text ? mb_strtoupper(trim(explode("\n", $app->address_text)[0]), 'UTF-8') : '');
        $cadde = $caddeList ? implode(', ', array_unique($caddeList)) : '';

        if ($app->relationLoaded('surfaceLines') && $app->surfaceLines->count() > 0) {
            foreach ($app->surfaceLines as $sl) {
                if (!$sl->surfaceType) continue;
                $sira++;
                $genislik = $sl->width_m ? number_format((float)$sl->width_m, 2, ',', '.') : '0,00';
                $uzunluk = $sl->length_m ? number_format((float)$sl->length_m, 2, ',', '.') : '0,00';
                // AYKOME 2 YIL KATI KURALI: multiplier > 1 ise M² sütunu
                // fiyatlandırma tabanını (orijinal × katı) gösterir — tahakkuk/ruhsat
                // ile birebir aynı rakam (calcFigures ile tek kaynak tutarlılığı).
                $katiCarpani = (float) ($sl->multiplier ?: 1);
                $efektifM2 = (float) ($sl->quantity ?? 0) * $katiCarpani;
                $m2 = number_format($efektifM2, 2, ',', '.');
                $zemin = mb_strtoupper($sl->surfaceType->name, 'UTF-8');

                $rows[] = [
                    'sira' => $sira,
                    'ilce' => $ilce,
                    'mahalle' => $mahalle,
                    'cadde' => $cadde,
                    'tarih' => $tarih,
                    'genislik' => $genislik,
                    'uzunluk' => $uzunluk,
                    'm2' => $m2,
                    'zemin' => $zemin,
                    'proje_kodu' => $projeKodu,
                    'kat' => $katiCarpani > 1 ? number_format($katiCarpani, 0, ',', '.') : '',
                    'aciklama' => $sl->aciklama ?? '',
                    'surface_line_id' => $sl->id,
                ];
            }
        }

        if (empty($rows)) {
            $rows[] = [
                'sira' => 1,
                'ilce' => $ilce,
                'mahalle' => $mahalle,
                'cadde' => $cadde ?: ($app->address_text ?: ''),
                'tarih' => $tarih,
                'genislik' => '0,00',
                'uzunluk' => '0,00',
                'm2' => '0,00',
                'zemin' => 'BELİRTİLMEMİŞ',
                'proje_kodu' => $projeKodu,
            ];
        }

        return $rows;
    }

    public function pdfIndir(EImzaTransaction $transaction): ?string
    {
        $path = $transaction->orijinal_pdf;
        if (!$path || !Storage::disk('public')->exists($path)) {
            return null;
        }
        return Storage::disk('public')->path($path);
    }

    /**
     * GÖREV 2 — Token-CN güvenlik kilidi. Cihazdaki akıllı kartın sertifika CN'i
     * ($certificateCn) imzayı başlatan kullanıcı ile uyuşmuyorsa imza işlemi,
     * kayıt yapılmadan HEMEN ÖNCE engellenir (EImzaSahibiUyusmazlikException).
     * CN boş gelirse (simülasyon/sertifikasız akış) kilit pas geçer.
     */
    private function verifyCertificateOwner(EImzaTransaction $transaction, ?string $certificateCn): void
    {
        if ($certificateCn === null || trim($certificateCn) === '') {
            return;
        }

        $authName = data_get($transaction->imzalayan_info, 'baslatan_user_name', '');
        $tokenSlug = Str::slug($certificateCn);
        $authSlug = Str::slug((string) $authName);

        if (! empty($tokenSlug) && strpos($tokenSlug, $authSlug) === false) {
            throw new \App\Exceptions\EImzaSahibiUyusmazlikException(
                'E-İmza Engellendi: Cihazdaki E-İmza Sahibi (Akıllı Kart) ile Sisteme Giriş Yapan Personel Uyuşmuyor!'
            );
        }
    }

    public function tamamla(EImzaTransaction $transaction, string $imzaliPdfContent, array $imzalayanInfo, ?string $certificateCn = null): void
    {
        // GÖREV 2 — Güvenlik kilidi imza işlemini yapmadan ÖNCE (imzalayan bilgisini
        // merge edip kaydetmeden önce) doğrulamalıdır.
        $this->verifyCertificateOwner($transaction, $certificateCn);

        // İmzalı PDF içindeki PAdES sertifikasından imzalayan bilgisini doğrula/zenginleştir.
        // baslatan_user_id/baslatan_user_name anahtarları korunur (merge tabanı transaction'dan,
        // sonraki update satırında tamamı kaydedilir).
        $imzalayanInfo = array_merge($transaction->imzalayan_info ?? [], $imzalayanInfo);

        $sertifikaBilgisi = $this->pdfSertifikaBilgisi($imzaliPdfContent);
        if ($sertifikaBilgisi !== null) {
            $imzalayanInfo = array_merge($imzalayanInfo, $sertifikaBilgisi);
        } else {
            Log::warning('PAdES sertifikası çıkarılamadı; istemci bilgisi kullanıldı', [
                'transaction_id' => $transaction->transaction_id,
                'application_id' => $transaction->application_id,
            ]);
        }

        $dizin = "e-imza/{$transaction->transaction_id}";
        $path = "{$dizin}/imzali.pdf";
        Storage::disk('public')->put($path, $imzaliPdfContent);

        // Kanonik kopya: storage/app/public/documents/YIL-ID/ → imzalı dosya ana dosya olur
        $application = $transaction->application;
        $year = now()->year;
        $appId = $application->id;
        $canonicalDir = "documents/{$year}-{$appId}";
        Storage::disk('public')->makeDirectory($canonicalDir);
        $signedCanonical = "{$canonicalDir}/{$year}-{$appId}_{$transaction->pdf_type}_imzali.pdf";
        Storage::disk('public')->put($signedCanonical, $imzaliPdfContent);

        $transaction->update([
            'status' => 'completed',
            'imzali_pdf' => $signedCanonical,
            'imzalayan_info' => $imzalayanInfo,
            'completed_at' => now(),
        ]);

        $moduleDocs = $application->module_documents ?? [];
        $pdfTypeLabels = [
            'ruhsat' => 'Ruhsat',
            'pre_permit' => 'Ön İzin',
            'taahhutname' => 'Taahhütname',
            'metraj' => 'Kazı Metraj',
            'tahakkuk' => 'Tahakkuk',
            'makbuz' => 'Tahsilat Makbuzu',
            'cover_letter' => 'Üst Yazı',
        ];
        $pdfTypeLabel = $pdfTypeLabels[$transaction->pdf_type] ?? $transaction->pdf_type;
        $moduleDocs[$transaction->pdf_type] = [
            'e_imza' => [
                'transaction_id' => $transaction->transaction_id,
                'imzalayan' => ($imzalayanInfo['ad'] ?? '') . ' ' . ($imzalayanInfo['soyad'] ?? ''),
                'sertifika' => $imzalayanInfo['sertifika_turu'] ?? '',
                'tarih' => now()->toIso8601String(),
                'durum' => 'completed',
                'signed_path' => $signedCanonical,
            ],
            'belediye_path' => $signedCanonical,
            'belediye_uploaded_at' => now()->toDateTimeString(),
            'status' => 'completed',
        ];
        $application->update(['module_documents' => $moduleDocs]);

        $application->timelineLogs()->create([
            'user_id' => auth()->id(),
            'action' => "{$pdfTypeLabel} belgesi e-İmza ile imzalandı",
            'message' => "İmzalayan: {$imzalayanInfo['ad']} {$imzalayanInfo['soyad']} ({$imzalayanInfo['sertifika_turu']})",
            'meta' => [
                'transaction_id' => $transaction->transaction_id,
                'pdf_type' => $transaction->pdf_type,
                'imzalayan' => $imzalayanInfo,
            ],
        ]);

        Log::info('E-İmza tamamlandı', [
            'transaction_id' => $transaction->transaction_id,
            'application_id' => $application->id,
            'pdf_type' => $transaction->pdf_type,
        ]);

        // 16.08 kullanıcı talebi: e-imza tamanınca süreç otomatik ilerlesin
        // (müdür imzalayınca başkan yrd. adımına geçsin). Sadece GÖREV 4'te
        // kurulan "e_imza" adimlarinda tetiklenir; onay tabanli varsayilan
        // süreçlerde (action_type='onay') hiçbir davranış değişmez.
        $this->ilerletSurecEgerEImzaAdimiTamamlandiysa($transaction, $imzalayanInfo);
    }

    /**
     * E-imza ile tamamlanan adim, ProcessEngine'deki mevcut adimla (action_type
     * = 'e_imza') eşleşiyorsa süreci bir sonraki adıma ilerletir. Güvenlik:
     * imzayı BAŞLATAN kullanıcı (imzalayan_info.baslatan_user_id — tamamla()
     * route'u api-key ile çalıştığı için auth()->user() burada YOKTUR) ile bu
     * adımda imza yetkisi (canSignStep) tekrar doğrulanır. Herhangi bir adımda
     * uyuşmazlık/hata olursa SADECE loglanır — e-imza kaydını asla bozmaz.
     */
    private function ilerletSurecEgerEImzaAdimiTamamlandiysa(EImzaTransaction $transaction, array $imzalayanInfo): void
    {
        try {
            $application = $transaction->application()->first();
            if (! $application) {
                return;
            }

            $baslatanUserId = $imzalayanInfo['baslatan_user_id'] ?? null;
            if (! $baslatanUserId) {
                return;
            }
            $user = \App\Models\User::find($baslatanUserId);
            if (! $user) {
                return;
            }

            $engine = app(ProcessEngine::class);
            $step = $engine->currentStep($application);
            if (! $step || ! $engine->stepRequiresSignature($step)) {
                return;
            }

            // Bu adım belirli bir PDF tipine kilitliyse (GÖREV 4 seed'i gibi),
            // sadece o tip imzalandığında ilerlet; başka bir belge (ör. önizleme)
            // imzalandıysa dokunma.
            $expectedPdfType = data_get($step->signature_config, 'pdf_type');
            if ($expectedPdfType && $expectedPdfType !== $transaction->pdf_type) {
                return;
            }

            if (! $engine->canSignStep($step, $user)) {
                return;
            }

            $result = $engine->approve($application->fresh(), $user);

            // 16.08 FIX: e-imza ile süreç ilerleyince de SADECE yeni aktif adımın
            // rolüne bildirim gider (advanceApproval() ile aynı mantık — bkz.
            // ApplicationService::notifyStepUsers).
            if (($result['approved'] ?? false) && ! ($result['finished'] ?? true) && ($result['next'] ?? null)) {
                app(ApplicationService::class)->notifyStepUsers($application, $result['next'], $user->id);
            }
        } catch (\Throwable $e) {
            Log::warning('E-imza sonrası süreç ilerletme başarısız (e-imza kaydı etkilenmedi)', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function tokenDogrula(EImzaTransaction $transaction, string $token): bool
    {
        return hash_equals($transaction->token, $token)
            && $transaction->status === 'pending'
            && $transaction->expires_at->isFuture();
    }

    /**
     * PAdES imzalı PDF içindeki gömülü sertifikadan imzalayan bilgisini çıkarır.
     * /ByteRange ile işaretli /Contents (DER PKCS#7) çözülür ve openssl ile parse edilir.
     */
    private function pdfSertifikaBilgisi(string $imzaliPdfContent): ?array
    {
        if (!preg_match('/\/ByteRange\s*\[(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\]/', $imzaliPdfContent, $m)) {
            return null;
        }

        $prefix = substr($imzaliPdfContent, 0, (int) $m[1]);
        $pos = strrpos($prefix, '/Contents');
        if ($pos === false) {
            return null;
        }

        $segment = substr($prefix, $pos);
        if (!preg_match('/<([0-9A-Fa-f\s]+)>/', $segment, $hm)) {
            return null;
        }

        $der = @hex2bin(preg_replace('/\s+/', '', $hm[1]));
        if ($der === false || $der === '') {
            return null;
        }

        $pem = "-----BEGIN PKCS7-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PKCS7-----\n";

        $certs = [];
        if (!@openssl_pkcs7_read($pem, $certs)) {
            return null;
        }

        foreach ($certs as $cert) {
            $parsed = @openssl_x509_parse($cert);
            if (!$parsed || empty($parsed['subject'])) {
                continue;
            }

            $subject = $parsed['subject'];
            $cn = trim((string) ($subject['CN'] ?? ''));
            $serialNo = (string) ($subject['serialNumber'] ?? '');
            $tckn = preg_replace('/\D/', '', $serialNo);

            $nameParts = preg_split('/\s+/', $cn, 2);
            $ad = $nameParts[0] ?? '';
            $soyad = $nameParts[1] ?? '';

            return [
                'ad' => $ad,
                'soyad' => $soyad,
                'tckn' => $tckn !== '' ? $tckn : null,
                'sertifika_turu' => 'Kamu SM',
                'sertifika_seri_no' => (string) ($parsed['serialNumber'] ?? ''),
                'sertifika_issuer' => (string) ($parsed['issuer']['CN'] ?? ($parsed['issuer']['O'] ?? '')),
                'sertifika_gecerli_baslangic' => isset($parsed['validFrom_time_t']) ? date('Y-m-d H:i:s', $parsed['validFrom_time_t']) : null,
                'sertifika_gecerli_bitis' => isset($parsed['validTo_time_t']) ? date('Y-m-d H:i:s', $parsed['validTo_time_t']) : null,
                'subject' => $subject,
                'kaynak' => 'pdf_pades',
            ];
        }

        return null;
    }

    public function temizle(): int
    {
        $count = 0;
        EImzaTransaction::query()
            ->where('status', 'pending')
            ->where('expires_at', '<', now()->subDay())
            ->chunk(100, function ($transactions) use (&$count) {
                foreach ($transactions as $t) {
                    $dizin = "e-imza/{$t->transaction_id}";
                    Storage::disk('public')->deleteDirectory($dizin);
                    $t->delete();
                    $count++;
                }
            });
        return $count;
    }
}
