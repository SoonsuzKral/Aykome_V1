<?php

namespace App\Services;

use App\Http\Controllers\Admin\ApplicationsController;
use App\Models\Application;
use App\Models\ApplicationDocumentOverride;
use App\Models\GlobalDocumentTemplate;

/**
 * EBYS Taslak Motoru — Global / Başvuru Bazlı Belge Şablonları
 * --------------------------------------------------------------
 * Kaynak hiyerarşisi (PDF çizim ve editör açılışı):
 *   başvuru override'ı → global şablon → derlenmiş Blade (mevcut akış)
 *
 * - word  (cover_letter / on_kazi): content_data = A4 gövde HTML fragment'ı
 * - excel (ruhsat / tahakkuk)     : content_data = JSON (2D hücre matrisi)
 */
class DocumentTemplateService
{
    public const TYPES = [
        'cover_letter' => [
            'label' => 'Üst Yazı',
            'full'  => 'Üst Yazı Şablonu',
            'desc'  => 'Dilekçe / Kurum Başvuru Yazısı',
            'editor'=> 'word',
            'blade' => 'admin.pdf.cover_letter',
            'icon'  => '✉️',
            'pdf_title' => 'KURUM BAŞVURU YAZISI',
        ],
        'on_kazi' => [
            'label' => 'Ön Kazı',
            'full'  => 'Ön Kazı Şablonu',
            'desc'  => 'Ön Kazı İzin Belgesi',
            'editor'=> 'word',
            'blade' => 'admin.pdf.pre_permit',
            'icon'  => '🪪',
            'pdf_title' => 'ÖN KAZI İZNİ ONAYI',
        ],
        'ruhsat' => [
            'label' => 'Ruhsat',
            'full'  => 'Ruhsat Şablonu (Excel)',
            'desc'  => 'Altyapı Tesisi Açım Ruhsatı — hücre hücre düzenlenebilir tablo',
            'editor'=> 'excel',
            'blade' => 'admin.pdf.ruhsat',
            'icon'  => '📄',
            'pdf_title' => 'ALTYAPI TESİSİ AÇIM RUHSATI',
        ],
        'tahakkuk' => [
            'label' => 'Tahakkuk Fişi',
            'full'  => 'Tahakkuk Fişi Şablonu (Excel)',
            'desc'  => 'Ruhsat Bedeli Hesabı / Tahakkuk Fişi — hücre hücre düzenlenebilir tablo',
            'editor'=> 'excel',
            'blade' => 'admin.pdf.tahakkuk',
            'icon'  => '🧾',
            'pdf_title' => 'ALTYAPI TESİSİ AÇIM RUHSAT BEDELİ HESABI',
        ],
    ];

    /** Standalone PDF sarmalayıcısı için temel A4 + yazdırma çubuğu CSS'i. */
    protected const LAYOUT_CSS = <<<'CSS'
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: #e5e7eb; padding-top: 56px; display: flex; justify-content: center; font-family: 'Times New Roman', Times, serif; }
.a4-container { background: #fff; width: 210mm; min-height: 297mm; padding: 18mm 20mm; box-shadow: 0 5px 15px rgba(0,0,0,0.4); margin: 16px auto; box-sizing: border-box; }
.print-bar { position: fixed; top: 0; left: 0; right: 0; z-index: 9999; background: #1e293b; color: #fff; height: 48px; display: flex; align-items: center; justify-content: space-between; padding: 0 16px; }
.print-bar .title { font-size: 14px; font-weight: 600; }
.print-bar .btn-print { background: #2563eb; color: #fff; border: none; padding: 8px 20px; border-radius: 5px; font-weight: 700; cursor: pointer; }
@media print { body { background: #fff; padding: 0; display: block; } .print-bar { display: none !important; } .a4-container { width: 100% !important; box-shadow: none; padding: 0 !important; margin: 0; min-height: auto; } }
@page { size: A4; margin: 15mm; }
CSS;

    /* ─── Tür yardımcıları ─────────────────────────────────────────────── */

    public static function type(string $type): ?array
    {
        return self::TYPES[$type] ?? null;
    }

    public static function isValid(string $type): bool
    {
        return isset(self::TYPES[$type]);
    }

    public static function label(string $type): string
    {
        return self::TYPES[$type]['label'] ?? $type;
    }

    public static function editor(string $type): string
    {
        return self::TYPES[$type]['editor'] ?? 'word';
    }

    /* ─── Blade derleme yardımcıları ───────────────────────────────────── */

    /** Global (örnek) editör verisi için dolu ama varsayılanlı örnek başvuru. */
    protected static function sampleApp(): Application
    {
        $sample = new Application();
        $sample->id = 0;
        $sample->institution_id = null;
        $sample->created_by = null;
        $sample->applicant_first_name = 'DİCLE ELEKTRİK';
        $sample->applicant_last_name = 'DAĞITIM A.Ş.';
        $sample->project_code = 'C-26-1100-1063-0019';
        $sample->excavation_reason = 'ALTYAPI TESİS';
        $sample->description = 'ENH TESİS YAPIM İŞİ';
        $sample->address_text = 'Eyyüpnebi Mah. 3554 Sk. Eyyübiye / Şanlıurfa';
        $sample->tesis_sorumlusu = 'YETKİLİ TESİS SORUMLUSU';
        $sample->applicant_phone = '0541 762 29 57';
        $sample->total_area_m2 = 650;
        $sample->mudur_adi = 'KURUM MÜDÜRÜ';
        $sample->mudur_unvani = 'İl Müdürü';

        $sample->setRelation('institution', null);
        $sample->setRelation('creator', null);

        return $sample;
    }

    /** Belge tipine göre blade verisi üretir ($app null ise global örnek veri). */
    protected static function bladeData(string $type, ?Application $app): array
    {
        if ($type === 'on_kazi') {
            if (! $app) {
                return [];
            }
            $settings = \App\Models\PreExcavationPermitSetting::first();
            $signatories = SignatoryEngine::roleMap('pre_permit', $app);

            return [
                'belediye' => 'EYYÜBİYE BELEDİYE BAŞKANLIĞI',
                'mudurluk' => 'Fen İşleri Müdürlüğü',
                'sayi' => 'E-' . ($settings->document_prefix ?? '18790261') . '-' . str_pad($app->id, 6, '0', STR_PAD_LEFT),
                'tarih' => $app->created_at?->format('d.m.Y') ?? now()->format('d.m.Y'),
                'konu' => mb_strtoupper($app->description ?? 'Kazı İzni Hk.', 'UTF-8'),
                'kurum' => mb_strtoupper($app->institution?->name ?? 'KURUM', 'UTF-8'),
                'ilgi_tarih' => $app->created_at?->format('d.m.Y') ?? now()->format('d.m.Y'),
                'ilgi_sayi' => str_pad($app->id, 7, '0', STR_PAD_LEFT),
                'metin' => ApplicationsController::buildPrePermitText($app),
                'imza_ad' => $signatories['belediye_baskan_yardimcisi']['ad_soyad'] ?? 'Yetkili',
                'imza_unvan' => $signatories['belediye_baskan_yardimcisi']['unvan'] ?? 'Belediye Başkan Yardımcısı',
                'adres' => $settings->address ?? 'Eyyüpnebi mh. 3554. Sk. Eski Ptt Binası Eyyübiye / Şanlıurfa',
                'bilgi_kisi' => $settings->signer_name ?? 'Zeynelabidin AKTAŞOĞLU',
                'telefon' => $settings->phone ?? '()',
                'fax' => $settings->fax ?? '()',
                'eposta' => $app->institution?->email ?? $settings->email ?? '-',
                'web' => $settings->website ?? '-',
                'kep_adresi' => $app->institution?->email ?? 'eyyubiye@hs03.kep.tr',
            ];
        }

        if ($type === 'cover_letter') {
            if (! $app) {
                return ['logo_base64' => null, 'application' => self::sampleApp()];
            }
            $app->loadMissing(['institution', 'creator', 'gisCizimleri.yolIliskileri', 'gisNoktalari']);

            $logoBase64 = null;
            if ($app->institution && $app->institution->logo_path) {
                try {
                    $fileContent = \Illuminate\Support\Facades\Storage::disk('public')->get($app->institution->logo_path);
                    if ($fileContent) {
                        $mime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($app->institution->logo_path);
                        $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode($fileContent);
                    }
                } catch (\Exception $e) {
                    $logoBase64 = null;
                }
            }

            return ['logo_base64' => $logoBase64, 'application' => $app];
        }

        return [];
    }

    protected static function renderBlade(string $type, ?Application $app): string
    {
        $blade = self::TYPES[$type]['blade'] ?? null;
        if (! $blade) {
            return '';
        }

        return view($blade, self::bladeData($type, $app))->render();
    }

    /** HTML içindeki tüm <style> bloklarının birleştirilmiş CSS'i. */
    protected static function extractStyles(string $html): string
    {
        preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $html, $m);
        $css = implode("\n", $m[1] ?? []);

        return str_replace(['<style>', '</style>'], '', $css);
    }

    /** HTML içindeki .a4-container divinin iç HTML'ini çıkarır (iç içe div güvenli). */
    protected static function extractA4Fragment(string $html): string
    {
        if ($html === '') {
            return '';
        }
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();

        $container = null;
        foreach ($doc->getElementsByTagName('div') as $div) {
            if (str_contains((string) $div->getAttribute('class'), 'a4-container')) {
                $container = $div;
                break;
            }
        }
        if (! $container) {
            return $html;
        }

        $fragment = '';
        foreach ($container->childNodes as $child) {
            $fragment .= $doc->saveHTML($child);
        }

        return $fragment;
    }

    /* ─── Kaynak çözümleme (override → global → null) ─────────────────── */

    public static function globalContent(string $type): ?string
    {
        $row = GlobalDocumentTemplate::where('document_type', $type)->first();

        return $row?->content_data;
    }

    public static function overrideContent(Application $app, string $type): ?string
    {
        $row = ApplicationDocumentOverride::where('application_id', $app->id)
            ->where('document_type', $type)
            ->first();

        return $row?->content_data;
    }

    public static function hasOverride(Application $app, string $type): bool
    {
        return ApplicationDocumentOverride::where('application_id', $app->id)
            ->where('document_type', $type)
            ->exists();
    }

    public static function resolveContent(string $type, ?Application $app): ?string
    {
        if ($app) {
            $ov = self::overrideContent($app, $type);
            if ($ov !== null) {
                return $ov;
            }
        }

        return self::globalContent($type);
    }

    /* ─── Kaydet / Sil ─────────────────────────────────────────────────── */

    public static function saveGlobal(string $type, string $content): void
    {
        GlobalDocumentTemplate::updateOrCreate(
            ['document_type' => $type],
            ['content_data' => $content, 'editor_type' => self::editor($type)]
        );
    }

    public static function saveOverride(Application $app, string $type, string $content): void
    {
        ApplicationDocumentOverride::updateOrCreate(
            ['application_id' => $app->id, 'document_type' => $type],
            ['content_data' => $content, 'editor_type' => self::editor($type)]
        );
    }

    public static function deleteOverride(Application $app, string $type): void
    {
        ApplicationDocumentOverride::where('application_id', $app->id)
            ->where('document_type', $type)
            ->delete();
    }

    /* ─── Excel hücre matrisi ──────────────────────────────────────────── */

    public static function buildRuhsatGrid(?Application $app): array
    {
        $d = $app ? (float) ($app->deposit_amount ?? 0) : 0;
        $disc = $app ? (float) ($app->discovery_amount ?? 0) : 0;

        $surfaceRows = [];
        if ($app) {
            $app->loadMissing(['institution', 'surfaceLines.surfaceType']);
            foreach ($app->surfaceLines ?? [] as $sl) {
                if (! $sl->surfaceType) {
                    continue;
                }
                $miktar = (float) ($sl->quantity ?? 0);
                $surfaceRows[] = [
                    $sl->surfaceType->name,
                    'm2',
                    number_format($miktar, 2, ',', '.'),
                    number_format($miktar * (float) ($sl->surfaceType->price_per_m2 ?? 0), 2, ',', '.'),
                    '',
                    '',
                ];
            }
        }

        $kdv = $d * 0.2;
        $ruhsatHarci = $d * 0.18;
        $kesifBedeli = $disc ?: $d * 0.01;
        $ztbToplam = $d + $kdv;
        $genelToplam = $ztbToplam + $ruhsatHarci + $kesifBedeli;

        $info = [
            ['TALEP SAHİBİ', $app?->institution?->name ?? 'KURUM ADI', '', '', '', ''],
            ['İLÇE', 'EYYÜBİYE', '', '', '', ''],
            ['ADRES', $app?->address ?? '-', '', '', '', ''],
        ];

        $header = ['AÇILACAK ZEMİN', 'BİRİM', 'MİKTAR', 'TUTAR', 'DİĞER BEDELLER', 'TOPLAM'];

        $fees = [
            ['', '', '', '', 'KDV (%20)', number_format($kdv, 2, ',', '.') . ' TL'],
            ['', '', '', '', 'RUHSAT HARCI', number_format($ruhsatHarci, 2, ',', '.') . ' TL'],
            ['', '', '', '', 'KEŞİF BEDELİ', number_format($kesifBedeli, 2, ',', '.') . ' TL'],
            ['', '', '', '', 'ZTB TOPLAM', number_format($ztbToplam, 2, ',', '.') . ' TL'],
            ['', '', '', '', 'TEMİNAT', '0,00 TL'],
            ['', '', '', '', 'GENEL TOPLAM', number_format($genelToplam, 2, ',', '.') . ' TL'],
        ];

        return array_merge($info, [$header], $surfaceRows, $fees);
    }

    public static function buildTahakkukGrid(?Application $app): array
    {
        $metraj = $app ? ApplicationsController::buildMetrajSatirlari($app) : [];
        $d = $app ? (float) ($app->deposit_amount ?? 0) : 0;

        $rows = [];
        foreach ($metraj as $s) {
            $rows[] = [
                $s['ad'],
                $s['birim'] ?? 'm2',
                $s['miktar'] ?? '0,00',
                $s['birim_fiyat'] ?? '0,00',
                $s['tutar'] ?? '0,00',
            ];
        }

        $info = [
            ['Talep sahibi', $app?->institution?->name ?? 'KURUM ADI', '', '', ''],
            ['ilçe', $app?->district ?? 'EYYÜBİYE', '', '', ''],
            ['Adres / Proje Adı', trim(($app?->project_code ?? '') . ' ' . ($app?->district ?? '')), '', '', ''],
            ['Firma', $app?->institution?->name ?? 'KURUM ADI', '', '', ''],
            ['İş Cinsi', $app?->description ?? 'ENH TESİS YAPIM İŞİ', '', '', ''],
        ];

        $header = ['ZEMİN CİNSİ', 'BİRİM', 'MİKTAR', 'BİRİM FİYAT', 'TUTAR'];

        $totals = [
            ['Toplam Miktar', '', '', '', '545,80'],
            ['Toplam Tutar', '', '', '', number_format($d, 2, ',', '.') . ' TL'],
            ['Zemin Tahrip Bedeli', '', '', '', number_format($d, 2, ',', '.') . ' TL'],
            ['K.D.V. (%20)', '', '', '', number_format($d * 0.2, 2, ',', '.') . ' TL'],
            ['Keşif Bedeli', '', '', '', number_format($d * 0.01, 2, ',', '.') . ' TL'],
            ['ZTB Toplam', '', '', '', number_format($d * 1.21, 2, ',', '.') . ' TL'],
            ['Teminat', '', '', '', '0,00 TL'],
            ['Genel Toplam', '', '', '', number_format($d * 1.21, 2, ',', '.') . ' TL'],
        ];

        return array_merge($info, [$header], $rows, $totals);
    }

    public static function gridFor(string $type, ?Application $app): array
    {
        if ($type === 'ruhsat') {
            return self::buildRuhsatGrid($app);
        }
        if ($type === 'tahakkuk') {
            return self::buildTahakkukGrid($app);
        }

        return [];
    }

    /* ─── Editör kaynağı ───────────────────────────────────────────────── */

    /**
     * Editör sayfasına verilecek içerik.
     * word  : ['editor'=>'word',  'content'=>fragment, 'css'=>doc css]
     * excel : ['editor'=>'excel', 'content'=>json grid]
     */
    public static function editorSource(string $type, ?Application $app): array
    {
        $isWord = self::editor($type) === 'word';

        if ($isWord) {
            $content = self::resolveContent($type, $app);
            if ($content === null) {
                $content = self::extractA4Fragment(self::renderBlade($type, $app));
            }
            $css = self::extractStyles(self::renderBlade($type, $app));

            return ['editor' => 'word', 'content' => $content, 'css' => $css];
        }

        $content = self::resolveContent($type, $app);
        if ($content === null) {
            $content = json_encode(self::gridFor($type, $app), JSON_UNESCAPED_UNICODE);
        }

        return ['editor' => 'excel', 'content' => $content, 'css' => ''];
    }

    /* ─── PDF çizim (override / global varsa) ──────────────────────────── */

    /** Belge için kaynak şablon varsa tam HTML döner, yoksa null (normal blade akışı). */
    public static function renderFor(string $type, Application $app): ?string
    {
        $content = self::resolveContent($type, $app);
        if ($content === null || trim($content) === '') {
            return null;
        }

        $isWord = self::editor($type) === 'word';

        if ($isWord) {
            $css = self::extractStyles(self::renderBlade($type, $app));
            $html = self::wrapStandalone($type, $css, $content);
        } else {
            $html = self::renderExcelPage($type, $content);
        }

        return self::applyEImzaStamp($html, $app);
    }

    /**
     * EBYS E-İmza önizleme (dummy QR) alanını </body> öncesine enjekte eder.
     * Gerçek e-imza entegrasyonu gelene kadar her editör çıktısına eklenir;
     * dosya yoksa SVG fallback üretir, sistem asla kırılmaz.
     */
    public static function applyEImzaStamp(string $html, ?Application $app = null): string
    {
        if (trim($html) === '' || ! str_contains($html, '</body>')) {
            return $html;
        }

        $qrImg = file_exists(public_path('images/dummy-qr.png'))
            ? '<img src="' . e(asset('images/dummy-qr.png')) . '" alt="E-İmza QR Kodu" style="width:76px;height:76px;border:1px solid #cbd5e1;border-radius:6px;background:#fff;flex:none;">'
            : self::inlineQrSvg();

        $no = $app?->application_no ?? $app?->id;
        $stamp = '<div class="e-imza-alani" style="margin-top:30px;padding-top:16px;border-top:2px solid #1e293b;display:flex;align-items:center;gap:16px;">'
            . $qrImg
            . '<div style="flex:1;font-size:10px;line-height:1.6;color:#334155;">'
            . '<div style="font-weight:700;font-size:11px;letter-spacing:.6px;color:#111827;">E-İMZA / DOĞRULAMA ALANI</div>'
            . '<div>Bu alan, belgenin elektronik imza doğrulama bölgesidir. Henüz imzalanmamış önizlemedir.</div>'
            . '<div>Belge / Doğrulama Kodu: <b>' . e((string) ($no ?? '-')) . '</b></div>'
            . '</div></div>';

        return str_replace('</body>', $stamp . '</body>', $html);
    }

    /** dummy-qr.png bulunamazsa SVG ile QR görünümü üretir. */
    protected static function inlineQrSvg(string $seed = 'aykome'): string
    {
        $n = 21;
        $grid = array_fill(0, $n, array_fill(0, $n, 0));
        $hash = md5($seed);
        $hl = strlen($hash);
        $h = 0;
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                $grid[$y][$x] = ord($hash[$h % $hl]) % 2;
                $h++;
            }
        }
        $finder = static function (int $cx, int $cy) use (&$grid) {
            for ($y = 0; $y < 7; $y++) {
                for ($x = 0; $x < 7; $x++) {
                    $on = ($x === 0 || $x === 6 || $y === 0 || $y === 6)
                        || ($x >= 2 && $x <= 4 && $y >= 2 && $y <= 4);
                    $grid[$cy + $y][$cx + $x] = $on ? 1 : 0;
                }
            }
        };
        $finder(0, 0);
        $finder(14, 0);
        $finder(0, 14);

        $rects = '';
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if ($grid[$y][$x]) {
                    $rects .= '<rect x="' . $x . '" y="' . $y . '" width="1" height="1"/>';
                }
            }
        }

        return '<svg viewBox="0 0 21 21" width="76" height="76" xmlns="http://www.w3.org/2000/svg" shape-rendering="crispEdges" style="flex:none;">'
            . '<rect width="21" height="21" fill="#fff"/>'
            . '<g fill="#000">' . $rects . '</g></svg>';
    }

    protected static function wrapStandalone(string $type, string $docCss, string $bodyHtml): string
    {
        $title = self::TYPES[$type]['pdf_title'] ?? self::label($type);

        return '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><title>' . e($title) . '</title>'
            . '<style>' . self::LAYOUT_CSS . '</style>'
            . '<style>' . $docCss . '</style>'
            . '</head><body>'
            . '<div class="print-bar no-print"><span class="title">' . e($title) . '</span>'
            . '<button class="btn-print" onclick="window.print()">🖨️ Yazdır / PDF Kaydet</button></div>'
            . '<div class="a4-container">' . $bodyHtml . '</div>'
            . '</body></html>';
    }

    protected static function renderExcelPage(string $type, string $json): string
    {
        $grid = json_decode($json, true);
        if (! is_array($grid)) {
            $grid = [];
        }

        $header = '<div style="text-align:center;margin-bottom:10px;font-weight:bold;font-size:15px;text-decoration:underline;">'
            . e(self::TYPES[$type]['pdf_title'] ?? '') . '</div>';

        return self::wrapStandalone($type, '', $header . self::gridToHtml($grid));
    }

    protected static function gridToHtml(array $grid): string
    {
        $html = '<table style="width:100%;border-collapse:collapse;border:1px solid #000;font-size:10px;">';

        foreach ($grid as $rowIndex => $row) {
            if (! is_array($row)) {
                $row = [$row];
            }

            $isHeader = false;
            foreach ($row as $cell) {
                $upper = mb_strtoupper(trim((string) $cell), 'UTF-8');
                if ($upper === 'AÇILACAK ZEMİN' || $upper === 'ZEMİN CİNSİ') {
                    $isHeader = true;
                    break;
                }
            }

            $first = mb_strtoupper(trim((string) ($row[0] ?? '')), 'UTF-8');
            $isTotals = $first !== '' && str_contains($first, 'TOPLAM')
                || $first !== '' && (str_contains($first, 'TUTAR') || str_contains($first, 'BEDELİ') || str_contains($first, 'K.D.V') || str_contains($first, 'Teminat'));

            $hasValue = array_filter($row, fn ($c) => trim((string) ($c ?? '')) !== '');
            if (! $hasValue) {
                continue;
            }

            $html .= '<tr>';
            foreach ($row as $cell) {
                $v = (string) ($cell ?? '');
                $bold = $isHeader || $isTotals || $rowIndex === 0;
                $right = preg_match('/TL$|[\d.,]+\s*$/', trim($v)) === 1 && ! str_starts_with(trim($v), 'TALEP');
                $style = 'border:1px solid #000;padding:4px;vertical-align:middle;text-align:' . ($right ? 'right' : 'left') . ';'
                    . ($bold ? 'font-weight:bold;' : '')
                    . ($isHeader || $isTotals ? 'background-color:#f2f2f2;' : '');
                $html .= '<td style="' . $style . '">' . e($v) . '</td>';
            }
            $html .= '</tr>';
        }

        return $html . '</table>';
    }
}
