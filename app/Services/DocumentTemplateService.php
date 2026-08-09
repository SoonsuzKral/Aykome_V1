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
        'metraj' => [
            'label' => 'Kazı Metraj',
            'full'  => 'Kazı Metraj Cetveli (Excel)',
            'desc'  => 'Kazı Metraj Cetveli ve Onay — yatay (landscape) A4 düzen, hücre hücre düzenlenebilir tablo',
            'editor'=> 'excel',
            'blade' => 'admin.pdf.metraj',
            'icon'  => '📐',
            'pdf_title' => 'KAZI METRAJ CETVELİ VE ONAY',
            'landscape' => true,
        ],
        'tahsilat_fisi' => [
            'label' => 'Tahsilat Fişi',
            'full'  => 'Tahsilat Fişi (Word)',
            'desc'  => 'Kazı İzni Tahsilat Fişi — düzenlenebilir fiş',
            'editor'=> 'word',
            'blade' => 'admin.pdf.tahsilat_fisi',
            'icon'  => '🧾',
            'pdf_title' => 'KAZI İZNİ TAHSİLAT FİŞİ',
        ],
        'makbuz' => [
            'label' => 'Tahsilat Makbuzu',
            'full'  => 'Tahsilat Makbuzu (Word)',
            'desc'  => 'Vezne Tahsilat Belgesi — düzenlenebilir makbuz',
            'editor'=> 'word',
            'blade' => 'admin.pdf.tahsilat_makbuzu',
            'icon'  => '🧾',
            'pdf_title' => 'ALTYAPI KAZI HARCI TAHSİLAT BELGESİ',
        ],
        'taahhutname' => [
            'label' => 'Taahhütname',
            'full'  => 'Taahhütname (Sözleşme)',
            'desc'  => 'Altyapı Tesisleri Açım Ruhsatı Taahhütnamesi — e-Devlet standartlarında 20 maddelik sözleşme',
            'editor'=> 'word',
            'blade' => 'admin.pdf.taahhutname',
            'icon'  => '📝',
            'pdf_title' => 'TAAHHÜTNAME',
        ],
    ];

    /** Standalone PDF sarmalayıcısı için temel A4 + yazdırma çubuğu CSS'i. */
    protected const LAYOUT_CSS = <<<'CSS'
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: #e5e7eb; padding-top: 0; display: block; font-family: 'DejaVu Sans', 'Helvetica', sans-serif; }
/* A4 kağıtlar DİKEY eksende alt alta (Word/PDF viewer düzeni) — block model, asla yan yana değil */
.a4-container { background: #fff; width: 210mm; min-height: 297mm; padding: 18mm 20mm; box-shadow: 0 5px 15px rgba(0,0,0,0.4); margin: 0 auto; box-sizing: border-box; }
/* ÜST ORTA İZOLE PANEL: kâğıt DOM'unun dışında, havada duran modern action bar — asla antetle çakışmaz */
.print-bar { position: fixed; top: 8px; left: 50%; transform: translateX(-50%); z-index: 50; background: rgba(15,23,42,.92); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); color: #fff; display: flex; align-items: center; gap: 12px; padding: 8px 16px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,.3); border: 1px solid #334155; }
.print-bar .title { font-size: 15px; font-weight: 700; letter-spacing: .3px; display: flex; align-items: center; gap: 8px; }
.print-bar .title .doc-ico { font-size: 18px; }
.print-bar .actions { display: flex; align-items: center; gap: 10px; }
.print-bar .btn-print { background: #2563eb; color: #fff; border: none; padding: 9px 18px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
.print-bar .btn-print:hover { background: #1d4ed8; }
.print-bar .btn-pdf { background: #16a34a; color: #fff; border: none; padding: 9px 18px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
.print-bar .btn-pdf:hover { background: #15803d; }
.print-bar .btn-close { background: transparent; color: #94a3b8; border: 1px solid #475569; padding: 8px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; }
.print-bar .btn-close:hover { background: #334155; color: #fff; }
@media print { body { background: #fff; padding: 0; display: block; } .print-bar { display: none !important; } .a4-container { width: 100% !important; box-shadow: none; padding: 0 !important; margin: 0; min-height: auto; } }
@page { size: A4; margin: 15mm; }
CSS;

    /** Landscape (metraj) A4 sarmalayıcı CSS'i. */
    protected const LAYOUT_CSS_LANDSCAPE = <<<'CSS'
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: #e5e7eb; padding-top: 0; display: block; font-family: 'DejaVu Sans', 'Helvetica', sans-serif; }
/* A4 kağıtlar DİKEY eksende alt alta (Word/PDF viewer düzeni) — block model, asla yan yana değil */
.a4-container { background: #fff; width: 297mm; min-height: 210mm; padding: 12mm 14mm; box-shadow: 0 5px 15px rgba(0,0,0,0.4); margin: 0 auto; box-sizing: border-box; }
/* ÜST ORTA İZOLE PANEL: kâğıt DOM'unun dışında, havada duran modern action bar — asla antetle çakışmaz */
.print-bar { position: fixed; top: 8px; left: 50%; transform: translateX(-50%); z-index: 50; background: rgba(15,23,42,.92); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); color: #fff; display: flex; align-items: center; gap: 12px; padding: 8px 16px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,.3); border: 1px solid #334155; }
.print-bar .title { font-size: 15px; font-weight: 700; letter-spacing: .3px; display: flex; align-items: center; gap: 8px; }
.print-bar .title .doc-ico { font-size: 18px; }
.print-bar .actions { display: flex; align-items: center; gap: 10px; }
.print-bar .btn-print { background: #2563eb; color: #fff; border: none; padding: 9px 18px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
.print-bar .btn-print:hover { background: #1d4ed8; }
.print-bar .btn-pdf { background: #16a34a; color: #fff; border: none; padding: 9px 18px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
.print-bar .btn-pdf:hover { background: #15803d; }
.print-bar .btn-close { background: transparent; color: #94a3b8; border: 1px solid #475569; padding: 8px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; }
.print-bar .btn-close:hover { background: #334155; color: #fff; }
@media print { body { background: #fff; padding: 0; display: block; } .print-bar { display: none !important; } .a4-container { width: 100% !important; box-shadow: none; padding: 0 !important; margin: 0; min-height: auto; } }
@page { size: A4 landscape; margin: 8mm; }
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
    protected static function sampleApp(?int $institutionId = null): Application
    {
        $sample = new Application();
        $sample->id = 0;
        $sample->institution_id = $institutionId;
        $sample->created_by = null;
        $sample->applicant_first_name = '';
        $sample->applicant_last_name = '';
        $sample->application_no = '';
        $sample->project_code = '';
        $sample->excavation_reason = '';
        $sample->description = '';
        $sample->address_text = '';
        $sample->tesis_sorumlusu = '';
        $sample->applicant_phone = '';
        $sample->total_area_m2 = 0;
        $sample->mudur_adi = '';
        $sample->mudur_unvani = '';
        $sample->district = '';
        $sample->work_type = '';
        $sample->created_at = null;
        $sample->deposit_amount = 0;
        $sample->discovery_amount = 0;

        $sample->setRelation('institution', $institutionId ? \App\Models\Institution::query()->find($institutionId) : null);
        $sample->setRelation('creator', null);

        return $sample;
    }

    /** Global editör için boş örnek metraj satırları (rakamlar sıfır). */
    protected static function sampleMetrajSatirlari(): array
    {
        return [
            ['ad' => 'ASFALT (SICAK KARIŞIM)', 'birim' => 'm2', 'miktar' => '0,00', 'birim_fiyat' => '0,00', 'tutar' => '0,00'],
            ['ad' => 'ASFALT (SOĞUK ASFALT)',  'birim' => 'm2', 'miktar' => '0,00',   'birim_fiyat' => '0,00',      'tutar' => '0,00'],
            ['ad' => 'PARKE',                    'birim' => 'm2', 'miktar' => '0,00',   'birim_fiyat' => '0,00',      'tutar' => '0,00'],
            ['ad' => 'BETON',                    'birim' => 'm2', 'miktar' => '0,00',   'birim_fiyat' => '0,00',      'tutar' => '0,00'],
            ['ad' => 'STABİLİZE',                'birim' => 'm2', 'miktar' => '0,00',   'birim_fiyat' => '0,00',      'tutar' => '0,00'],
            ['ad' => 'TRETUAR (PARKE PRİZM)',   'birim' => 'm2', 'miktar' => '0,00',   'birim_fiyat' => '0,00',      'tutar' => '0,00'],
            ['ad' => 'BORDÜR (BETON)',           'birim' => 'm',  'miktar' => '0,00',   'birim_fiyat' => '0,00',      'tutar' => '0,00'],
            ['ad' => 'ÇİM',                      'birim' => 'm2', 'miktar' => '0,00',   'birim_fiyat' => '0,00',      'tutar' => '0,00'],
        ];
    }

    /** Belge tipine göre blade verisi üretir ($app null ise global örnek veri). */
    protected static function bladeData(string $type, ?Application $app, ?int $institutionId = null): array
    {
        if ($type === 'on_kazi') {
            $app = $app ?? self::sampleApp($institutionId);
            $settings = \App\Models\PreExcavationPermitSetting::first();
            $signatories = $app->id > 0 ? SignatoryEngine::roleMap('pre_permit', $app) : [];

            return [
                'belediye' => 'EYYÜBİYE BELEDİYE BAŞKANLIĞI',
                'mudurluk' => 'Fen İşleri Müdürlüğü',
                'sayi' => 'E-' . ($settings->document_prefix ?? '') . ($app->id > 0 ? '-' . str_pad($app->id, 6, '0', STR_PAD_LEFT) : ''),
                'tarih' => $app->created_at?->format('d.m.Y') ?? '',
                'konu' => mb_strtoupper($app->description ?? $app->excavation_reason ?? '', 'UTF-8'),
                'kurum' => mb_strtoupper($app->institution?->name ?? $app->applicant_first_name . ' ' . $app->applicant_last_name, 'UTF-8'),
                'ilgi_tarih' => $app->created_at?->format('d.m.Y') ?? '',
                'ilgi_sayi' => $app->id > 0 ? str_pad($app->id, 7, '0', STR_PAD_LEFT) : '',
                'metin' => ApplicationsController::buildPrePermitText($app),
                'imza_ad' => $signatories['belediye_baskan_yardimcisi']['ad_soyad'] ?? '',
                'imza_unvan' => $signatories['belediye_baskan_yardimcisi']['unvan'] ?? '',
                'adres' => $app->address_text ?? $settings->address ?? '',
                'bilgi_kisi' => $settings->signer_name ?? '',
                'telefon' => $settings->phone ?? '',
                'fax' => $settings->fax ?? '',
                'eposta' => $app->institution?->email ?? $settings->email ?? '',
                'web' => $settings->website ?? '',
                'kep_adresi' => $app->institution?->email ?? '',
            ];
        }

        if ($type === 'cover_letter') {
            if (! $app) {
                $app = self::sampleApp($institutionId);
                if ($app->institution) {
                    $app->setRelation('creator', null);
                }
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

        if ($type === 'metraj') {
            $app = $app ?? self::sampleApp($institutionId);
            if ($app->id > 0) {
                $app->loadMissing(['institution', 'creator', 'surfaceLines.surfaceType', 'gisCizimleri.yolIliskileri', 'gisNoktalari']);
            }
            $rows = $app->id > 0 ? ApplicationsController::buildMetrajRows($app) : self::metrajRowsFromSample();
            $toplamM2 = 0;
            foreach ($rows as $r) {
                $toplamM2 += (float) str_replace(['.', ','], ['', '.'], $r['m2'] ?? '0');
            }

            return [
                'kurum' => mb_strtoupper($app->institution?->name ?? $app->applicant_first_name . ' ' . $app->applicant_last_name, 'UTF-8'),
                'birim' => 'PROJE TESİS YÖNETİCİLİĞİ',
                'alici' => 'EYYÜBİYE BELEDİYE BAŞKANLIĞI FEN İŞLERİ MÜDÜRLÜĞÜ AYKOME BİRİMİ',
                'signatories' => $app->id > 0 ? SignatoryEngine::roleMap('metraj', $app) : [],
                'proje_kodu' => $app->project_code ?? '',
                'tarih' => $app->start_date?->format('d.m.Y') ?? '',
                'rows' => $rows,
                'toplam_m2' => number_format($toplamM2, 2, ',', '.'),
                'ilce' => $app->district ?? '',
                'firma' => mb_strtoupper($app->institution?->name ?? '', 'UTF-8'),
                'is_cinsi' => $app->description ?? $app->excavation_reason ?? '',
                'talep_sahibi' => $app->id > 0 ? mb_strtoupper(trim($app->tesis_sorumlusu ?? ''), 'UTF-8') : '',
            ];
        }

        if ($type === 'tahsilat_fisi') {
            $app = $app ?? self::sampleApp($institutionId);
            $app->loadMissing(['institution', 'surfaceLines.surfaceType']);
            $metraj = $app->id > 0 ? ApplicationsController::buildMetrajSatirlari($app) : self::sampleMetrajSatirlari();

            return [
                'belediye' => 'EYYÜBİYE BELEDİYE BAŞKANLIĞI',
                'mudurluk' => 'Fen İşleri Müdürlüğü',
                'birim' => 'AYKOME BİRİMİ',
                'altbaslik' => 'TAHSİLAT FİŞİ',
                'fis_no' => 'F-' . str_pad((string) $app->id, 6, '0', STR_PAD_LEFT),
                'talep_sahibi' => mb_strtoupper($app->institution?->name ?? 'DİCLE ELEKTRİK', 'UTF-8'),
                'metraj_satirlari' => $metraj,
                'application' => $app,
                'signatories' => SignatoryEngine::roleMap('tahakkuk', $app),
            ];
        }

        if ($type === 'makbuz') {
            return ['application' => $app ?? self::sampleApp()];
        }

        if ($type === 'taahhutname') {
            return ['application' => $app ?? self::sampleApp()];
        }

        if ($type === 'ruhsat') {
            $app = $app ?? self::sampleApp($institutionId);
            $app->loadMissing(['institution', 'surfaceLines.surfaceType', 'creator', 'priceApprover', 'receiptApprover']);

            return [
                'application' => $app,
                'isim' => trim(($app->applicant_first_name ?? '') . ' ' . ($app->applicant_last_name ?? '')),
                'signatories' => SignatoryEngine::roleMap('ruhsat', $app),
            ];
        }

        if ($type === 'tahakkuk') {
            $app = $app ?? self::sampleApp($institutionId);
            $app->loadMissing(['institution', 'surfaceLines.surfaceType']);
            $metraj = $app->id > 0 ? ApplicationsController::buildMetrajSatirlari($app) : self::sampleMetrajSatirlari();

            return [
                'belediye' => 'EYYÜBİYE BELEDİYESİ',
                'mudurluk' => 'FEN İŞLERİ MÜDÜRLÜĞÜ',
                'birim' => 'AYKOME BİRİMİ',
                'altbaslik' => 'ALTYAPI TESİSİ AÇIM RUHSAT BEDELİ HESABI',
                'talep_sahibi' => mb_strtoupper($app->institution?->name ?? '', 'UTF-8'),
                'ilce' => $app->district ?? '',
                'adres' => trim(($app->project_code ?? '') . ' ' . ($app->district ?? '')),
                'firma' => mb_strtoupper($app->institution?->name ?? '', 'UTF-8'),
                'is_cinsi' => $app->description ?? '',
                'vergino' => '',
                'metraj_satirlari' => $metraj,
                'application' => $app,
            ];
        }

        return [];
    }

    protected static function renderBlade(string $type, ?Application $app, ?int $institutionId = null): string
    {
        $blade = self::TYPES[$type]['blade'] ?? null;
        if (! $blade) {
            return '';
        }

        return view($blade, self::bladeData($type, $app, $institutionId))->render();
    }

    /** HTML içindeki tüm <style> bloklarının birleştirilmiş CSS'i. */
    protected static function extractStyles(string $html): string
    {
        preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $html, $m);
        $css = implode("\n", $m[1] ?? []);

        return str_replace(['<style>', '</style>'], '', $css);
    }

    /** HTML içindeki A4 container divinin iç HTML'ini çıkarır (iç içe div güvenli). */
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
            $class = (string) $div->getAttribute('class');
            if (str_contains($class, 'a4-container') || str_contains($class, 'a4-landscape-container')) {
                $container = $div;
                break;
            }
        }

        if ($container) {
            $fragment = '';
            foreach ($container->childNodes as $child) {
                $fragment .= $doc->saveHTML($child);
            }
            return $fragment;
        }

        // Container'sız blade (örn. tahsilat_makbuzu): body çocuklarını içerik al
        $body = $doc->getElementsByTagName('body')->item(0);
        if ($body) {
            $fragment = '';
            foreach ($body->childNodes as $child) {
                $fragment .= $doc->saveHTML($child);
            }
            return trim($fragment) !== '' ? $fragment : $html;
        }

        return $html;
    }

    /* ─── Kaynak çözümleme (override → global → null) ─────────────────── */

    public static function globalContent(string $type): ?string
    {
        $row = GlobalDocumentTemplate::where('document_type', $type)->first();

        return $row?->content_data;
    }

    /** Kuruma özel şablon içeriği. */
    public static function institutionContent(?int $institutionId, string $type): ?string
    {
        if (! $institutionId) {
            return null;
        }

        $row = \App\Models\InstitutionDocumentTemplate::where('institution_id', $institutionId)
            ->where('document_type', $type)
            ->first();

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

            // Kurum bazlı şablon (AKSA, Dicle Elektrik vb. kendi üst yazısı)
            $inst = self::institutionContent($app->institution_id, $type);
            if ($inst !== null) {
                return $inst;
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

    /** Kuruma özel şablonu kaydet. */
    public static function saveInstitution(int $institutionId, string $type, string $content): void
    {
        \App\Models\InstitutionDocumentTemplate::updateOrCreate(
            ['institution_id' => $institutionId, 'document_type' => $type],
            ['content_data' => $content, 'editor_type' => self::editor($type)]
        );
    }

    /** Kuruma özel şablonu sil → global/varsayılan akışa dön. */
    public static function deleteInstitution(int $institutionId, string $type): void
    {
        \App\Models\InstitutionDocumentTemplate::where('institution_id', $institutionId)
            ->where('document_type', $type)
            ->delete();
    }

    /* ─── Kurum bazlı Üst Yazı şablonu (merkezden yönetim) ─────────────── */

    /** Üst yazı şablonu dinamik alan yer tutucuları (başvuru verisiyle dolar). */
    public const KURUM_ADI_TOKEN  = '{KURUM_ADI}';
    public const TESIS_TOKEN      = '{TESIS_SORUMLUSU}';
    public const DUZENLEYEN_TOKEN = '{DUZENLEYEN}';
    public const KAZI_MIKTAR_TOKEN = '{KAZI_MIKTAR}';
    public const MUDUR_ADI_TOKEN  = '{MUDUR_ADI}';
    public const MUDUR_UNVAN_TOKEN = '{MUDUR_UNVAN}';
    public const DOGRULAMA_TOKEN  = '{DOGRULAMA_KODU}';
    public const SAYI_TOKEN       = '{SAYI}';
    public const TARIH_TOKEN      = '{TARIH}';

    /** EK-1 overflow eşiği: bu sayıdan fazla sokak → tablo ayrı A4'e (belge sonuna) sürülür. */
    public const MUHTELIF_OVERFLOW_THRESHOLD = 6;

    /** Kurum şablonundaki tüm dinamik token listesi (başvuru verisiyle hidrate). */
    public static function coverTokens(): array
    {
        return [
            self::KURUM_ADI_TOKEN, self::TESIS_TOKEN, self::DUZENLEYEN_TOKEN,
            self::KAZI_MIKTAR_TOKEN, self::MUDUR_ADI_TOKEN, self::MUDUR_UNVAN_TOKEN,
            self::DOGRULAMA_TOKEN, self::SAYI_TOKEN, self::TARIH_TOKEN,
        ];
    }

    /**
     * Yeni alt kurum eklendiğinde Üst Yazı şablonunu otomatik oluşturur.
     * Kaynak: merkez (global) master şablon → varsa kopyalar; yoksa blade
     * varsayılanından ilk içerik üretir. Kopyalanan içerikte kurum adı, imza
     * yetkilileri, doğrulama kodu ve tarih bölgeleri yer tutucularına
     * dönüştürülür, böylece her başvurunun PDF'inde KENDİ verisi basılır.
     */
    public static function seedInstitutionCover(int $institutionId, ?string $masterHtml = null): bool
    {
        $content = $masterHtml ?? self::globalContent('cover_letter');
        if (! is_string($content) || trim($content) === '') {
            // Master yoksa blade varsayılanını (kurum adı dinamik) başlangıç kabul et.
            $rendered = self::renderBlade('cover_letter', self::sampleApp($institutionId), $institutionId);
            $content = self::extractA4Fragment($rendered);
        }
        if (! is_string($content) || trim($content) === '') {
            return false;
        }

        $content = self::maskCoverDynamicFields($content);
        self::saveInstitution($institutionId, 'cover_letter', $content);

        return true;
    }

    /**
     * HTML içindeki dinamik Üst Yazı bölgelerini yer tutuculara çevirir:
     *   - antet kurum adı + imza altı kurum adı     → {KURUM_ADI}
     *   - Tesis Kontrol / Yetkilisi                 → {TESIS_SORUMLUSU}
     *   - Evrağı Düzenleyen                         → {DUZENLEYEN}
     *   - Yaklaşık Kazı miktarı                     → {KAZI_MIKTAR}
     *   - Müdür adı / unvanı                        → {MUDUR_ADI} / {MUDUR_UNVAN}
     *   - BELGE DOĞRULAMA KODU                      → {DOGRULAMA_KODU}
     *   - Sayı satırı (E-...)                       → {SAYI}
     *   - Tarih (sağ üst)                           → {TARIH}
     * Kullanıcının elle yazdığı / boş bıraktığı alanlar dokunulmadan kalır
     * (yalnızca blade çıktısındaki bilinen placeholder desenleri dönüştürülür).
     */
    protected static function maskCoverDynamicFields(string $html): string
    {
        // ÖN ADIM (DOM): antet kurum adı + imza sağı kurum adı + gömülü logo temizliği.
        // Bu adımlar DOM üzerinde, referans bozulmasın diye tek pasda string regex ile yapılır.
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        if ($loaded) {
            $xpath = new \DOMXPath($doc);

            $setText = static function (\DOMElement $el, string $token): void {
                $el->textContent = ' ' . $token . ' ';
            };

            // 1) Kurum adı: antet başlık (text-align:center span.font-bold) + imza altı (font-size:12.5px)
            foreach ($xpath->query('//td[contains(@style,"text-align:center")]/span[contains(@class,"font-bold")] | //span[contains(@style,"font-size:12.5px")]') as $el) {
                if (! $el instanceof \DOMElement) {
                    continue;
                }
                $txt = trim((string) $el->textContent);
                if ($txt === '' || preg_match('/^[A-ZÇĞİÖŞÜ0-9\.\&\-\' ]{3,}$/u', $txt)) {
                    $setText($el, self::KURUM_ADI_TOKEN);
                }
            }

            // 2) ANTET LOGO SİL: blade a4-container başına gömülen <img> kaldırılır.
            //    PDF üretiminde downloadCoverLetter logo bloğunu dinamik enjekte eder —
            //    çift logo olmaması için şablona gömülü logo parçası atılır.
            foreach ($doc->getElementsByTagName('img') as $img) {
                $img->parentNode?->removeChild($img);
            }

            $html = $doc->saveHTML($doc->getElementsByTagName('body')->item(0));
        }

        // 3) İmza bölgesi: Tesis Kontrol / Yetkilisi, Evrağı Düzenleyen, Yaklaşık Kazı
        //    (u flag: &nbsp; = \xC2\xA0 baytı \s ile yakalansın diye UTF-8 modu)
        $html = (string) preg_replace('/Tesis Kontrol \/ Yetkilisi\s*:\s*<b>[^<]*<\/b>/u', 'Tesis Kontrol / Yetkilisi : <b>' . self::TESIS_TOKEN . '</b>', $html);
        $html = (string) preg_replace('/Evrağı Düzenleyen\s*:\s*<b>[^<]*<\/b>/u', 'Evrağı Düzenleyen &nbsp;: <b>' . self::DUZENLEYEN_TOKEN . '</b>', $html);
        $html = (string) preg_replace('/Yaklaşık Kazı\s*:\s*<b>[^<]*<\/b>/u', 'Yaklaşık Kazı &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <b>' . self::KAZI_MIKTAR_TOKEN . '</b>', $html);

        // 4) Müdür adı / unvanı: imza sağı (text-transform:uppercase b + font-size:14px span)
        $html = (string) preg_replace('/<b[^>]*style="text-transform:uppercase;"[^>]*>[^<]*<\/b>/u', '<b style="text-transform:uppercase;">' . self::MUDUR_ADI_TOKEN . '</b>', $html, 1);
        $html = (string) preg_replace('/<span style="font-size:14px;">[^<]*<\/span>/u', '<span style="font-size:14px;">' . self::MUDUR_UNVAN_TOKEN . '</span>', $html, 1);

        // 5) Doğrulama kodu + sayı + tarih
        $html = (string) preg_replace('/BELGE DOĞRULAMA KODU:\s*<b[^>]*>[^<]*<\/b>/u', 'BELGE DOĞRULAMA KODU: <b style="color:#d97706;">' . self::DOGRULAMA_TOKEN . '</b>', $html);
        $html = (string) preg_replace('/Sayı\s*:\s*[^<]{0,60}<br>/u', 'Sayı &nbsp;&nbsp;&nbsp;: ' . self::SAYI_TOKEN . '<br>', $html);
        // Tarih: sağ üst contenteditable span — yalnızca tarih deseni varsa
        $html = (string) preg_replace('/<span[^>]*contenteditable="true"[^>]*>\s*\d{1,2}\.\d{1,2}\.\d{4}\s*<\/span>/u', '<span contenteditable="true">' . self::TARIH_TOKEN . '</span>', $html);

        return $html;
    }

    /**
     * PDF üretiminde kurum şablonundaki yer tutucuları başvuru verisiyle doldurur.
     * Başvuru alanı boşsa yer tutucu boş bırakılır (kullanıcı editörde elle doldurur).
     */
    public static function hydrateInstitutionTokens(string $html, Application $app): string
    {
        $kurumAdi = $app->institution?->name;
        $map = [
            self::KURUM_ADI_TOKEN   => mb_strtoupper(trim((string) $kurumAdi), 'UTF-8'),
            self::TESIS_TOKEN       => mb_strtoupper(trim((string) $app->tesis_sorumlusu_adi), 'UTF-8'),
            self::DUZENLEYEN_TOKEN  => mb_strtoupper(trim((string) ($app->duzenleyen_kisi ?? $app->creator?->name)), 'UTF-8'),
            self::KAZI_MIKTAR_TOKEN => collect($app->surfaceLines ?? [])->sum('quantity') . ' m² / m.',
            self::MUDUR_ADI_TOKEN   => mb_strtoupper(trim((string) $app->mudur_adi), 'UTF-8'),
            self::MUDUR_UNVAN_TOKEN => trim((string) $app->mudur_unvani),
            self::DOGRULAMA_TOKEN   => $app->verification_code ?? 'GEÇERSİZ/TASLAK',
            self::SAYI_TOKEN        => 'E-50005665001100-100-' . str_pad((string) $app->id, 7, '0', STR_PAD_LEFT),
            self::TARIH_TOKEN       => $app->created_at?->format('d.m.Y') ?? '',
        ];

        foreach ($map as $token => $val) {
            $html = str_replace($token, e((string) $val), $html);
        }

        return $html;
    }

    /* ─── BİLGİ KATMANI: dinamik alan seçici (tüm belgeler) ───────────── */

    /**
     * Editördeki "Bilgi Katmanı" paneli için alan kataloğu (gruplu).
     * Her kayıt: ['key' => token anahtarı, 'label' => görünen ad, 'tip' => veri tipi].
     * key değerleri fieldValue() eşlemesiyle birebir örtüşür.
     */
    public static function fieldCatalog(): array
    {
        return [
            'Başvuru' => [
                ['key' => 'basvuru_no',      'label' => 'Başvuru No',            'tip' => 'text'],
                ['key' => 'dogrulama_kodu',  'label' => 'Doğrulama Kodu',        'tip' => 'text'],
                ['key' => 'kurum_adi',       'label' => 'Kurum Adı',             'tip' => 'text'],
                ['key' => 'proje_kodu',      'label' => 'Proje Kodu',            'tip' => 'text'],
                ['key' => 'proje_adi',       'label' => 'Proje Adı / Açıklama',  'tip' => 'text'],
                ['key' => 'kazi_nedeni',     'label' => 'Kazı Nedeni',           'tip' => 'text'],
                ['key' => 'is_cinsi',        'label' => 'İş Cinsi',              'tip' => 'text'],
                ['key' => 'adres',           'label' => 'Tek Adres',            'tip' => 'text'],
                ['key' => 'muhtelif_adres_tablosu', 'label' => 'Muhtelif Adres/Metraj Tablosu', 'tip' => 'html'],
            ],
            'Kişi' => [
                ['key' => 'basvuran_ad',     'label' => 'Başvuran Ad',           'tip' => 'text'],
                ['key' => 'basvuran_soyad',  'label' => 'Başvuran Soyad',        'tip' => 'text'],
                ['key' => 'basvuran_tc',     'label' => 'T.C. Kimlik No',        'tip' => 'text'],
                ['key' => 'telefon',         'label' => 'Telefon',               'tip' => 'text'],
                ['key' => 'tesis_sorumlusu', 'label' => 'Tesis Sorumlusu',       'tip' => 'text'],
            ],
            'Tarihler' => [
                ['key' => 'baslangic_tarihi', 'label' => 'Başlangıç Tarihi',     'tip' => 'tarih'],
                ['key' => 'bitis_tarihi',     'label' => 'Bitiş Tarihi',         'tip' => 'tarih'],
                ['key' => 'olusturulma_tarihi', 'label' => 'Oluşturulma Tarihi', 'tip' => 'tarih'],
            ],
            'Alanlar' => [
                ['key' => 'toplam_alan_m2',  'label' => 'Toplam Alan (m²)',      'tip' => 'sayi'],
                ['key' => 'kazi_miktari',    'label' => 'Kazı Miktarı (m²/m)',   'tip' => 'sayi'],
            ],
            'İmza' => [
                ['key' => 'mudur_adi',       'label' => 'Müdür Adı',             'tip' => 'text'],
                ['key' => 'mudur_unvani',    'label' => 'Müdür Unvanı',          'tip' => 'text'],
                ['key' => 'duzenleyen',      'label' => 'Evrağı Düzenleyen',     'tip' => 'text'],
            ],
        ];
    }

    /**
     * Bir token anahtarının başvurudan gelecek değerini üretir.
     * Bilinmeyen anahtar '' döner → hidrasyonda token dokunulmaz kalır.
     */
    public static function fieldValue(Application $app, string $key): string
    {
        $d = static fn ($v) => (string) ($v ?? '');

        return match ($key) {
            'basvuru_no', 'application_no' => $d($app->application_no),
            'dogrulama_kodu' => $d($app->verification_code),
            'kurum_adi' => mb_strtoupper(trim($d($app->institution?->name)), 'UTF-8'),
            'proje_kodu' => $d($app->project_code),
            'proje_adi' => $d($app->description),
            'kazi_nedeni' => $d($app->excavation_reason),
            'is_cinsi' => $d($app->work_type),
            'basvuran_ad' => $d($app->applicant_first_name),
            'basvuran_soyad' => $d($app->applicant_last_name),
            'basvuran_tc' => $d($app->applicant_national_id ?? $app->tc_no ?? $app->identity_no),
            'telefon' => $d($app->applicant_phone),
            'tesis_sorumlusu' => mb_strtoupper(trim($d($app->tesis_sorumlusu ?? $app->tesis_sorumlusu_adi ?? $app->institution?->tesis_sorumlusu_adi)), 'UTF-8'),
            'mudur_adi' => mb_strtoupper(trim($d($app->mudur_adi ?? $app->institution?->mudur_adi)), 'UTF-8'),
            'mudur_unvani' => $d($app->mudur_unvani ?? $app->institution?->mudur_unvani),
            'duzenleyen' => $d($app->duzenleyen_kisi ?? $app->creator?->name),
            'baslangic_tarihi' => $d($app->start_date?->format('d.m.Y')),
            'bitis_tarihi' => $d($app->end_date?->format('d.m.Y')),
            'olusturulma_tarihi' => $d($app->created_at?->format('d.m.Y')),
            'toplam_alan_m2' => $d($app->total_area_m2),
            'kazi_miktari' => number_format((float) collect($app->surfaceLines ?? [])->sum('quantity'), 2, ',', '.') . ' m² / m.',
            // GÖREV 1: muhtelif ise {adres} tokeni "MUHTELİF CADDE VE SOKAK" başlığını oluşturur;
            // tekil başvuruda ise normal adres metnini basar.
            'adres' => $app->streetCount() > 1 ? 'MUHTELİF CADDE VE SOKAK' : $d($app->address_text),
            'muhtelif_adres_tablosu' => self::muhtelifAdresTablosu($app),
            default => '',
        };
    }

    /**
     * {muhtelif_adres_tablosu} SHORTCODE — muhtelif adres tablosu üretici.
     * Başvuruda birden fazla mahalle/sokak kaydı varsa 2 kolonlu (chunk(2))
     * hücresel HTML tablo döner; tek/boş ise "" (iz bırakmadan silinir).
     */
    public static function muhtelifAdresTablosu(Application $app): string
    {
        if ($app->streetCount() <= 1) {
            return '';
        }

        $grouped = $app->streetLinesGroupedByMahalle();
        $html = '<table style="width: 100%; border-collapse: collapse; margin-top:10px; margin-bottom: 10px;" border="1">';
        foreach ($grouped as $mahalle => $sokaklar) {
            $baslik = mb_strtoupper(trim((string) $mahalle), 'UTF-8');
            $son = preg_replace('/[İIıi]/u', 'I', $baslik);
            $baslik .= (str_ends_with($son, 'MAHALLE') || str_ends_with($son, 'MAHALLESI') ? '' : ' MAHALLESİ');
            $html .= '<tr><th colspan="2" style="background:#e5e7eb; text-align:center; padding:4px;">' . e($baslik) . '</th></tr>';

            foreach (collect($sokaklar)->chunk(2) as $ikiliSokakGrubu) {
                $hucreler = $ikiliSokakGrubu->values();
                $html .= '<tr>'
                    . '<td style="width:50%; padding:2px;">' . e((string) ($hucreler->get(0) ?? '')) . '</td>'
                    . '<td style="width:50%; padding:2px;">' . e((string) ($hucreler->get(1) ?? '')) . '</td>'
                    . '</tr>';
            }
        }

        return $html . '</table>';
    }

    /**
     * EK-1 overflow yönlendirmesi (GÖREV 2-B): sokak sayısı eşiği (6) aştığında
     * uzun çift sütunlu tablo, dokümanın TAAA EN DİBİNE yeni A4 frame'i içinde
     * page-break-before ile append edilir. İlk sayfa kapanır, imza bloğu
     * A4 yerleşimini korur — belge asla ikiye bölünmez.
     */
    public static function appendEk1Cizelge(string $html, Application $app): string
    {
        $frame = '<div class="a4-container no-print-buttons" style="page-break-before: always; width: 100%; padding-top: 2cm;">'
            . '<h3 style="text-align: center; text-decoration: underline; margin-bottom: 20px;">EK-1: MUHTELİF KAZI ADRESLERİ ÇİZELGESİ</h3>'
            . self::muhtelifAdresTablosu($app)
            . '</div>';

        if (stripos($html, '</body>') !== false) {
            return str_ireplace('</body>', $frame . '</body>', $html);
        }

        return $html . $frame;
    }

    /**
     * GENEL token hidrasyonu (tüm belge tipleri).
     * Adım 1 — mevcut sabit Üst Yazı token'ları ({KURUM_ADI}, {DOGRULAMA_KODU} vb.):
     *          eski davranış aynen korunur.
     * Adım 2 — kullanıcının Bilgi Katmanı'ndan eklediği dinamik {alan_adi} token'ları
     *          fieldValue() ile doldurur; bilinmeyen anahtar token'ı dokunulmaz bırakır.
     */
    public static function hydrateTemplateTokens(string $html, Application $app): string
    {
        // Adım 1 — mevcut sabit cover token map'i (eski davranış).
        $html = self::hydrateInstitutionTokens($html, $app);

        // Adım 2 — dinamik token'lar ({kucuk_harf_alt_cizgi} / Türkçe karakterler)
        $hasMuhtelif = $app->streetCount() > 1;
        $overflow = $hasMuhtelif && $app->streetCount() > self::MUHTELIF_OVERFLOW_THRESHOLD;

        $html = (string) preg_replace_callback(
            '/\{([a-z_çğıiöşü0-9]+)\}/u',
            function (array $m) use ($app, $hasMuhtelif, $overflow): string {
                $key = $m[1] ?? '';
                if ($key === '') {
                    return $m[0];
                }
                // ÖZEL SHORTCODE {muhtelif_adres_tablosu}: ham HTML tablo döner
                // (escape edilmez); başvuruda veri yoksa iz bırakmadan "" ile değişir.
                if ($key === 'muhtelif_adres_tablosu') {
                    if (! $hasMuhtelif) {
                        return ''; // tekil/boş → iz bırakmadan sil
                    }
                    if ($overflow) {
                        // GÖREV 2-A: inline uyarı; asıl tablo belge sonuna EK-1 olarak eklenir
                        return '<strong style="font-size:12px;">ADRESLER EK.1 İÇERİSİNDE BULUNMAKTADIR.</strong>';
                    }

                    return self::muhtelifAdresTablosu($app);
                }
                $val = self::fieldValue($app, $key);
                if ($val === '') {
                    return $m[0]; // bilinmeyen / boş → token dokunulmaz
                }

                return e($val);
            },
            $html
        );

        // GÖREV 2-B: overflow durumunda uzun tablo doküman sonuna append edilir
        if ($overflow) {
            $html = self::appendEk1Cizelge($html, $app);
        }

        return $html;
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

    /* ─── Modüller arası sayı senkronu (data-aykome-* sözleşmesi) ───────── */

    /** Türkçe sayı formatı: "1.234,56". */
    public static function fmtTr(float $number): string
    {
        return number_format((float) round($number, 2), 2, ',', '.');
    }

    /** "1.234,56 TL" / "1234.56" / "1234,56" → float. */
    public static function parseTrNumber(string $value): float
    {
        $s = preg_replace('/[^\d.,\-]/', '', (string) $value);
        if ($s === '' || $s === '-') {
            return 0.0;
        }
        if (str_contains($s, ',') && str_contains($s, '.')) {
            $s = str_replace('.', '', $s);   // binlik ayracı (TR)
            $s = str_replace(',', '.', $s);  // ondalık ayracı (TR)
        } elseif (str_contains($s, ',')) {
            $s = str_replace(',', '.', $s);
        }
        return (float) $s;
    }

    /** Fragment veya tam HTML'i UTF-8 DOMDocument'a yükler. */
    protected static function domLoad(string $html): \DOMDocument
    {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();

        return $doc;
    }

    /** DOMDocument içinden body children HTML'ini (fragment) döndürür. */
    protected static function domBodyHtml(\DOMDocument $doc): string
    {
        $body = $doc->getElementsByTagName('body')->item(0);
        if (! $body) {
            return $doc->saveHTML();
        }
        $out = '';
        foreach ($body->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }

    /**
     * Override HTML'indeki SAYI hücrelerini DB'den yeniden basar.
     * El ile yapılan metin düzenlemeleri korunur; yalnızca data-aykome-* ile
     * işaretli sayı hücreleri accessor/surface satırlarından tazelenir.
     * " TL" sonekleri ve Türkçe format korunur.
     */
    public static function hydrateNumbers(string $html, Application $app): string
    {
        $app->loadMissing(['surfaceLines.surfaceType', 'institution']);

        $doc = self::domLoad($html);
        $xp = new \DOMXPath($doc);

        // 1) Yüzey satırları (data-aykome-surface) — miktar/m2/tutar/genislik/uzunluk
        $surfaceRows = $xp->query('//*[@data-aykome-surface]');
        if ($surfaceRows !== false) {
            foreach ($surfaceRows as $tr) {
                $name = trim((string) $tr->getAttribute('data-aykome-surface'));
                if ($name === '') {
                    continue;
                }
                $line = collect($app->surfaceLines ?? [])->first(function ($sl) use ($name) {
                    return $sl->surfaceType
                        && mb_strtolower(trim((string) $sl->surfaceType->name), 'UTF-8')
                           === mb_strtolower($name, 'UTF-8');
                });
                if (! $line) {
                    continue;
                }

                $cells = $xp->query('.//*[@data-aykome-col]', $tr);
                foreach ($cells as $td) {
                    switch ($td->getAttribute('data-aykome-col')) {
                        case 'miktar':
                        case 'm2':
                            self::setCellText($td, self::fmtTr((float) ($line->quantity ?? 0)));
                            break;
                        case 'tutar':
                            self::setCellText($td, self::fmtTr((float) ($line->amount ?? 0)));
                            break;
                        case 'genislik':
                            self::setCellText($td, number_format((float) ($line->width_m ?? 0), 2, ',', '.'));
                            break;
                        case 'uzunluk':
                            self::setCellText($td, number_format((float) ($line->length_m ?? 0), 2, ',', '.'));
                            break;
                    }
                }
            }
        }

        // 2) Ücret hücreleri (data-aykome-fee) — accessor değerleri
        $fees = [
            'toplam_miktar' => $app->toplam_miktar,
            'ztb_amount'    => $app->ztb_amount,
            'kdv_amount'    => $app->kdv_amount,
            'license_fee'   => $app->license_fee,
            'discovery_fee' => $app->discovery_fee,
            'ztb_total'     => $app->ztb_total,
            'teminat'       => $app->teminat_amount,
            'general_total' => $app->general_total,
            'toplam_m2'     => $app->toplam_miktar,
        ];
        foreach ($fees as $key => $value) {
            $nodes = $xp->query('//*[@data-aykome-fee="' . $key . '"]');
            foreach ($nodes as $td) {
                self::setCellText($td, (string) $value);
            }
        }

        return self::domBodyHtml($doc);
    }

    /** Hücre metnini değiştirir; " TL" sonekini korur. */
    protected static function setCellText(\DOMElement $td, string $value): void
    {
        $text = (string) $td->textContent;
        $suffix = str_contains($text, ' TL') ? ' TL' : '';
        $td->textContent = $value . $suffix;
    }

    /**
     * Belge HTML'inden (override) her yüzey tipinin belge miktarını çıkarır.
     * Veri modeli: [['name'=>..., 'quantity'=>float, 'tutar'=>float, 'price'=>float|null], ...]
     */
    public static function extractSurfaceRows(string $html): array
    {
        $doc = self::domLoad($html);
        $xp = new \DOMXPath($doc);

        $rows = [];
        $surfaceRows = $xp->query('//*[@data-aykome-surface]');
        if ($surfaceRows === false) {
            return $rows;
        }

        foreach ($surfaceRows as $tr) {
            $name = trim((string) $tr->getAttribute('data-aykome-surface'));
            if ($name === '') {
                continue;
            }

            $qty = null;
            $tutar = null;
            $price = null;
            $cells = $xp->query('.//*[@data-aykome-col]', $tr);
            foreach ($cells as $td) {
                $col = $td->getAttribute('data-aykome-col');
                $val = self::parseTrNumber(trim((string) $td->textContent));
                if ($col === 'miktar' || $col === 'm2') {
                    $qty = $val;
                } elseif ($col === 'tutar') {
                    $tutar = $val;
                } elseif ($col === 'birim_fiyat') {
                    $price = $val;
                }
            }

            if ($qty !== null) {
                $rows[] = [
                    'name' => $name,
                    'quantity' => $qty,
                    'tutar' => $tutar,
                    'price' => $price,
                ];
            }
        }

        return $rows;
    }

    /* ─── Excel hücre matrisi ──────────────────────────────────────────── */

    public static function buildRuhsatGrid(?Application $app): array
    {
        $surfaceRows = [];
        if ($app) {
            $app->loadMissing(['institution', 'surfaceLines.surfaceType']);
            foreach ($app->surfaceLines ?? [] as $sl) {
                if (! $sl->surfaceType) {
                    continue;
                }
                $surfaceRows[] = [
                    $sl->surfaceType->name,
                    'm2',
                    number_format((float) ($sl->quantity ?? 0), 2, ',', '.'),
                    number_format((float) ($sl->amount ?? 0), 2, ',', '.'),
                    '',
                    '',
                ];
            }
        }

        // TEK MUHASEBE KAYNAĞI: KDV/harç/keşif/teminat Model accessor'larından okunur.
        $kdv = $app ? $app->kdv_amount : '0,00';
        $ruhsatHarci = $app ? $app->license_fee : '0,00';
        $kesifBedeli = $app ? $app->discovery_fee : '0,00';
        $ztbToplam = $app ? $app->ztb_total : '0,00';
        $teminat = $app ? $app->teminat_amount : '0,00';
        $genelToplam = $app ? $app->general_total : '0,00';

        $info = [
            ['TALEP SAHİBİ', $app?->institution?->name ?? 'KURUM ADI', '', '', '', ''],
            ['İLÇE', 'EYYÜBİYE', '', '', '', ''],
            ['ADRES', $app?->address ?? '-', '', '', '', ''],
        ];

        $header = ['AÇILACAK ZEMİN', 'BİRİM', 'MİKTAR', 'TUTAR', 'DİĞER BEDELLER', 'TOPLAM'];

        $fees = [
            ['', '', '', '', 'KDV (%20)', $kdv . ' TL'],
            ['', '', '', '', 'RUHSAT HARCI', $ruhsatHarci . ' TL'],
            ['', '', '', '', 'KEŞİF BEDELİ', $kesifBedeli . ' TL'],
            ['', '', '', '', 'ZTB TOPLAM', $ztbToplam . ' TL'],
            ['', '', '', '', 'TEMİNAT', $teminat . ' TL'],
            ['', '', '', '', 'GENEL TOPLAM', $genelToplam . ' TL'],
        ];

        return array_merge($info, [$header], $surfaceRows, $fees);
    }

    public static function buildTahakkukGrid(?Application $app): array
    {
        $metraj = $app ? ApplicationsController::buildMetrajSatirlari($app) : [];

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

        // TEK MUHASEBE KAYNAĞI: Tüm tutarlar Model accessor'larından okunur.
        $totals = [
            ['Toplam Miktar', '', '', '', $app ? $app->toplam_miktar : '0,00'],
            ['Toplam Tutar', '', '', '', ($app ? $app->ztb_amount : '0,00') . ' TL'],
            ['Zemin Tahrip Bedeli', '', '', '', ($app ? $app->ztb_amount : '0,00') . ' TL'],
            ['K.D.V. (%20)', '', '', '', ($app ? $app->kdv_amount : '0,00') . ' TL'],
            ['Ruhsat Harcı', '', '', '', ($app ? $app->license_fee : '0,00') . ' TL'],
            ['Keşif Bedeli', '', '', '', ($app ? $app->discovery_fee : '0,00') . ' TL'],
            ['ZTB Toplam', '', '', '', ($app ? $app->ztb_total : '0,00') . ' TL'],
            ['Teminat', '', '', '', ($app ? $app->teminat_amount : '0,00') . ' TL'],
            ['Genel Toplam', '', '', '', ($app ? $app->general_total : '0,00') . ' TL'],
        ];

        return array_merge($info, [$header], $rows, $totals);
    }

    public static function buildMetrajGrid(?Application $app): array
    {
        $app = $app ?? self::sampleApp();
        if ($app->id > 0) {
            $app->loadMissing(['institution', 'creator', 'surfaceLines.surfaceType', 'gisCizimleri.yolIliskileri', 'gisNoktalari']);
        }
        $rows = $app->id > 0 ? ApplicationsController::buildMetrajRows($app) : self::metrajRowsFromSample();

        $header = ['SIRA', 'İLÇE', 'MAHALLE', 'CADDE VE SOKAK', 'KAZI BAŞLANGIÇ TARİHİ', 'GENİŞLİK', 'UZUNLUK', 'M² / M', 'ZEMİN CİNSİ', 'PROJE / İŞİN ADI'];

        $gridRows = [];
        foreach ($rows as $r) {
            $gridRows[] = [
                $r['sira'] ?? '',
                $r['ilce'] ?? '',
                $r['mahalle'] ?? '',
                $r['cadde'] ?? '',
                $r['tarih'] ?? '',
                $r['genislik'] ?? '',
                $r['uzunluk'] ?? '',
                $r['m2'] ?? '',
                $r['zemin'] ?? '',
                $r['proje_kodu'] ?? '',
            ];
        }

        $info = [
            ['ALICI', 'EYYÜBİYE BELEDİYE BAŞKANLIĞI FEN İŞLERİ MÜDÜRLÜĞÜ AYKOME BİRİMİ', '', '', '', '', '', '', '', ''],
        ];

        return array_merge($info, [$header], $gridRows);
    }

    /** Global örnek veri için metraj row yapısında boş satırlar (rakamlar sıfır). */
    protected static function metrajRowsFromSample(): array
    {
        return [
            ['sira' => 1, 'ilce' => '', 'mahalle' => '', 'cadde' => '', 'tarih' => '', 'genislik' => '0,00', 'uzunluk' => '0,00', 'm2' => '0,00', 'zemin' => '', 'proje_kodu' => ''],
            ['sira' => 2, 'ilce' => '', 'mahalle' => '', 'cadde' => '', 'tarih' => '', 'genislik' => '0,00', 'uzunluk' => '0,00', 'm2' => '0,00', 'zemin' => '', 'proje_kodu' => ''],
            ['sira' => 3, 'ilce' => '', 'mahalle' => '', 'cadde' => '', 'tarih' => '', 'genislik' => '0,00', 'uzunluk' => '0,00', 'm2' => '0,00', 'zemin' => '', 'proje_kodu' => ''],
            ['sira' => 4, 'ilce' => '', 'mahalle' => '', 'cadde' => '', 'tarih' => '', 'genislik' => '0,00', 'uzunluk' => '0,00', 'm2' => '0,00', 'zemin' => '', 'proje_kodu' => ''],
        ];
    }

    public static function gridFor(string $type, ?Application $app): array
    {
        if ($type === 'ruhsat') {
            return self::buildRuhsatGrid($app);
        }
        if ($type === 'tahakkuk') {
            return self::buildTahakkukGrid($app);
        }
        if ($type === 'metraj') {
            return self::buildMetrajGrid($app);
        }

        return [];
    }

    /* ─── Editör kaynağı ───────────────────────────────────────────────── */

    /**
     * Editör sayfasına verilecek içerik.
     * word  : ['editor'=>'word',  'content'=>fragment, 'css'=>doc css]
     * excel : ['editor'=>'excel', 'content'=>json grid]
     */
    public static function editorSource(string $type, ?Application $app, ?int $institutionId = null): array
    {
        // Tüm tipler (word + excel) artık orijinal A4 HTML + contenteditable olarak açılır.
        // Harici JS kütüphaneleri (TinyMCE/Jexcel) kaldırıldı; blade'in zengin A4 yapısı (üst
        // başlıklar, imzalar) asla bozulmaz; sadece editörde contenteditable ile düzenlenir.
        $content = self::resolveContentFor($type, $app, $institutionId);
        $rendered = null;
        if ($content === null) {
            $rendered = self::renderBlade($type, $app, $institutionId);
            $content = self::extractA4Fragment($rendered);
        } else {
            // CSS için blade render'a ihtiyaç var (content zaten kayıtlı şablondan geldi)
            $rendered = self::renderBlade($type, $app, $institutionId);
        }
        $css = self::extractStyles($rendered);

        return ['editor' => 'contenteditable', 'content' => $content, 'css' => $css];
    }

    /**
     * İçerik çözümleme — başvuru override'ı → kurum şablonu → global → null.
     * $institutionId verilirse o kurumun şablonu aranır.
     */
    public static function resolveContentFor(string $type, ?Application $app, ?int $institutionId = null): ?string
    {
        if ($app) {
            $ov = self::overrideContent($app, $type);
            if ($ov !== null) {
                return $ov;
            }
        }

        $ofScope = $institutionId ?: ($app?->institution_id ?? null);
        if ($ofScope) {
            $inst = self::institutionContent((int) $ofScope, $type);
            if ($inst !== null) {
                return $inst;
            }
        }

        return self::globalContent($type);
    }

    /* ─── PDF çizim (override / global varsa) ──────────────────────────── */

    /** Belge için kaynak şablon varsa tam HTML döner, yoksa null (normal blade akışı).
     *  $withUi=false → PDF render için print-bar/butonlar HTML'e yazılmaz (GÖREV 2). */
    public static function renderFor(string $type, Application $app, bool $withStamp = true, bool $withUi = true): ?string
    {
        $content = self::resolveContent($type, $app);
        if ($content === null || trim($content) === '') {
            return null;
        }

        $isWord = self::editor($type) === 'word';
        $looksLikeJsonGrid = str_starts_with(trim($content), '[') || str_starts_with(trim($content), '{');

        if ($isWord || ! $looksLikeJsonGrid) {
            // Word tipleri + contenteditable ile kaydedilmiş (artık HTML olan) excel tipleri
            $css = self::extractStyles(self::renderBlade($type, $app));
            // GÖREV 1: Şablon CSS'indeki tüm font-family bildirimleri DejaVu'ya yönlendirilir
            // (dompdf Type1 Times Latin-1'dir; Türkçe karakterler bu sayede tam basılır).
            $css = (string) preg_replace('/font-family\s*:\s*[^;}]+/i', "font-family: 'DejaVu Sans', sans-serif", $css);
            $html = self::wrapStandalone($type, $css, $content, $withUi);
        } else {
            // Eski JSON grid kayıtları (uyumluluk) — HTML tabloya çevrilir
            $html = self::renderExcelPage($type, $content, $withUi);
        }

        // BİLGİ KATMANI: şablondaki tüm {alan_adi} / {KURUM_ADI} / {DOGRULAMA_KODU}
        // yer tutucularını başvurunun kendi verisiyle doldurur (tüm belge tipleri).
        // Kullanıcı Bilgi Katmanı panelinden hangi alanı nereye koyacağını seçer.
        $html = self::hydrateTemplateTokens($html, $app);

        return $withStamp ? self::applyEImzaStamp($html, $app) : $html;
    }

    /**
     * EBYS E-İmza damga/şerit enjeksiyonu — KÖKTEN KALDIRILDI.
     *
     * KESİN EMİR (Baş Mimar): İmza atan yöneticinin bilgileri veya PAdES şerhi asla,
     * ASLA HTML layouta enjekte edilmeyecek. Belge şablondan nasıl üretildiyse
     * birebir kalır; e-imza yalnızca görünmez (invisible) kriptografik PAdES
     * mührüdür. Bu metod adı çağrılabilir (BC) ama hiçbir şey eklemez — 0 insertion.
     */
    public static function applyEImzaStamp(string $html, ?Application $app = null): string
    {
        return $html;
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

    protected static function wrapStandalone(string $type, string $docCss, string $bodyHtml, bool $withUi = true): string
    {
        $title = self::TYPES[$type]['pdf_title'] ?? self::label($type);
        $layoutCss = ! empty(self::TYPES[$type]['landscape'])
            ? self::LAYOUT_CSS_LANDSCAPE
            : self::LAYOUT_CSS;

        $uiBar = $withUi
            ? '<div class="print-bar no-print fixed top-2 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 bg-slate-900/90 backdrop-blur py-2 px-4 rounded-xl shadow-lg border border-slate-700">'
                . '<span class="title"><span class="doc-ico">📄</span>' . e($title) . '</span>'
                . '<div class="actions">'
                . '<button type="button" class="btn-close" onclick="window.close()">✕ Kapat</button>'
                . '<button type="button" class="btn-pdf" onclick="saveAsPdf()">📄 PDF Olarak Kaydet</button>'
                . '<button type="button" class="btn-print" onclick="window.print()">🖨️ Yazdır</button>'
                . '</div></div>'
            : '';

        return '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><title>' . e($title) . '</title>'
            . '<style>' . $layoutCss . '</style>'
            . '<style>' . $docCss . '</style>'
            . '</head><body>'
            . $uiBar
            . '<div class="a4-container">' . $bodyHtml . '</div>'
            . ($withUi ? '<script>function saveAsPdf(){ window.print(); }</script>' : '')
            . '</body></html>';
    }

    /**
     * Salt-okunur sunum: Bir belgenin düzenleme kilidi alt kuruma kapandığında
     * (belediye onay/devralma sürecinde) renderFor() çıktısındaki TÜM
     * contenteditable="true" atribütlerini söker; belge ne tarayıcıda ne de
     * editor'de asla düzenlenemez. Metraj'daki "imza alanı dışı kilit" davranışının
     * üst yazı dahil her tipe uygulanmış hâlidir.
     */
    public static function readOnlyRender(string $html, bool $keepSignEditable = true, bool $keepPrintBar = false): string
    {
        if (! $keepSignEditable) {
            // TAM SALT-OKUNUR (görüntüleme): tüm contenteditable VE data-sign-editable sökülür.
            $html = (string) preg_replace('/\s+contenteditable\s*=\s*["\'][^"\']*["\']/i', '', $html);
        } else {
        // Her HTML etiketini tekil işle: contenteditable attribut'unu taşıyan öğelerden
        // düzenleme yeteneğini sök. TEK İSTİSNA: data-sign-editable="1" ile işaretlenmiş
        // "KURUM/KURULUŞ (YETKİLİ GÖREVLİ)" imza kutusu — alt kurum yalnızca kendi
        // imza bölgesini düzenleyebilir, miktar/fiyat/satır hücreleri asla.
        $html = (string) preg_replace_callback(
            '/<([a-zA-Z][a-zA-Z0-9]*)\b([^>]*?)>/',
            function (array $m): string {
                $tag = $m[1];
                $attrs = $m[2];

                if (! preg_match('/contenteditable\s*=/i', $attrs)) {
                    return $m[0];
                }

                // İmza kutusu işaretliyse dokunma (alt kurum kendi imzasını atabilir).
                if (preg_match('/data-sign-editable/i', $attrs)) {
                    return $m[0];
                }

                $attrs = preg_replace('/\s+contenteditable\s*=\s*["\'][^"\']*["\']/i', '', $attrs);

                    return '<' . $tag . $attrs . '>';
                },
                $html
            );
        }

        // Yalnızca araç çubuklarını gizle; belge içeriğine ASLA display:none uygulama.
        // Salt-okunur görüntüleyicisinde yazdır barı korunur (bak + yazdır).
        $hide = $keepPrintBar
            ? '<style>.toolbar, .print-bar { display:none !important; }</style>'
            : '<style>.toolbar, .print-bar, .no-print-bar { display:none !important; }</style>';

        return str_ireplace('</head>', $hide . '</head>', $html);
    }

    /**
     * Salt-okunur görüntüleme: belge salt-okunur; düzenleme toolbarini gizler,
     * yazdır barını (no-print-bar) korur. (PDF Görüntüle → bak + yazdır.)
     */
    public static function readOnlyView(string $html, bool $keepSignEditable = false): string
    {
        return self::readOnlyRender($html, $keepSignEditable, true);
    }

    /**
     * EDITÖR İÇERİK DÜZENLENEBİLİRLİĞİ:
     * Kaydedilmiş şablonlarda contenteditable özniteliği düşmüş/seçici DOM normalleşmesiyle
     * "false" olmuş olabilir. "✏️ Düzenle (Kaydet)" editörünün belediye/alt-kurum taslak
     * düzenlemesinde beklenen hücreleri yeniden contenteditable="true" yapar.
     * $editable=true → mevcut contenteditable'ı taşıyan öğeler "true" olur (kilit açılır).
     * $editable=false → hepsi "false" olur (salt-okunur).
     * Yalnızca contenteditable attr'ı taşıyan öğeler işlenir; diğerleri dokunulmaz.
     */
    public static function ensureContentEditable(string $html, bool $editable = true): string
    {
        if (! preg_match('/contenteditable\s*=|data-sign-editable/i', $html)) {
            return $html;
        }
        $flag = $editable ? 'true' : 'false';

        return (string) preg_replace_callback(
            '/<([a-zA-Z][a-zA-Z0-9]*)\b([^>]*?)>/',
            function (array $m) use ($flag): string {
                $attrs = $m[2];

                if (! preg_match('/contenteditable\s*=/i', $attrs)) {
                    return $m[0];
                }

                $attrs = preg_replace(
                    '/\s+contenteditable\s*=\s*["\'][^"\']*["\']/i',
                    ' contenteditable="' . $flag . '"',
                    $attrs
                );

                return '<' . $m[1] . $attrs . '>';
            },
            $html
        );
    }

    /**
     * ALT KURUM METRAJ İMZA TABANI: Alt kurum metrajın "KURUM/KURULUŞ" imza kutusunu
     * kaydederken sunucuda kullanılacak güvenli taban içerik.
     *  - Başvuruya özel override varsa o korunur (belediye düzenlemeleri kaybolmaz).
     *  - Aksi halde metraj blade'i belediye (her hücre düzenlenebilir) üretilir; böylece
     *    miktar/fiyat/satırlar veritabanından yeniden türetilir, alt kurum hücre korsanlığı yapamaz.
     */
    public static function metrajSignatureBase(Application $app): string
    {
        return self::signatureSaveBase('metraj', $app);
    }

    /**
     * ALT KURUM İMZA TABANI (metraj + taahhütname vb.): Alt kurum yalnızca kendi
     * data-sign-editable imza hücresini kaydederken sunucuda kullanılacak güvenli taban.
     *  - Başvuruya özel override varsa o korunur (belediye düzenlemeleri kaybolmaz).
     *  - Aksi halde ilgili blade belediye (forceMuni, her hücre doğrulanabilir) üretilir;
     *    metin/satır/bedel vb. veritabanından yeniden türetilir, alt kurum korsanlık yapamaz.
     */
    public static function signatureSaveBase(string $type, Application $app): string
    {
        $override = self::overrideContent($app, $type);
        if ($override !== null && trim((string) $override) !== '') {
            return (string) $override;
        }

        $html = view('admin.pdf.' . $type, array_merge(self::bladeData($type, $app), ['forceMuni' => true]))->render();

        return self::extractA4Fragment($html);
    }

    /**
     * ALT KURUM İMZA BİRLEŞTİRME: Alt kurumun gönderdiği HTML'den YALNIZCA
     * data-sign-editable (imza hücreleri) alınıp taban içeriğe eklenir. Geri kalan her
     * şey sunucu tarafında korunur; hücre düzenleme korsanlığı böylece imkânsızdır.
     * Birden fazla işaretli hücre (ör. ruhsat FİRMA/SORUMLU/TELEFON/İMZA) sırayla işlenir.
     *
     * $climbToTable=true  (metraj): imza hücresi kendi taşıyıcı tablosuyla birlikte değişir.
     * $climbToTable=false (taahhütname/ruhsat): yalnızca data-sign-editable elemanların kendisi.
     */
    public static function mergeSignatureOnly(string $baseHtml, string $submittedHtml, bool $climbToTable = true): string
    {
        $load = function (string $html): \DOMDocument {
            $doc = new \DOMDocument();
            libxml_use_internal_errors(true);
            $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            libxml_clear_errors();

            return $doc;
        };

        $findSignatureNodes = function (\DOMDocument $doc) use ($climbToTable): array {
            $xp = new \DOMXPath($doc);
            $nodes = [];
            foreach ($xp->query('//*[@data-sign-editable="1"]') as $node) {
                if ($climbToTable) {
                    while ($node instanceof \DOMNode && strtolower((string) $node->nodeName) !== 'table') {
                        $node = $node->parentNode;
                    }
                }
                if ($node instanceof \DOMElement) {
                    $nodes[] = $node;
                }
            }

            return $nodes;
        };

        $subDoc = $load($submittedHtml);
        $submittedNodes = $findSignatureNodes($subDoc);
        if (! $submittedNodes) {
            return $baseHtml;
        }

        $baseDoc = $load($baseHtml);
        $baseNodes = $findSignatureNodes($baseDoc);
        if (! $baseNodes) {
            return $baseHtml;
        }

        $count = min(count($baseNodes), count($submittedNodes));
        for ($i = 0; $i < $count; $i++) {
            $imported = $baseDoc->importNode($submittedNodes[$i], true);
            $baseNodes[$i]->parentNode->replaceChild($imported, $baseNodes[$i]);
        }

        $body = $baseDoc->getElementsByTagName('body')->item(0);
        if (! $body) {
            return $baseHtml;
        }

        $out = '';
        foreach ($body->childNodes as $child) {
            $out .= $baseDoc->saveHTML($child);
        }

        return trim($out) !== '' ? $out : $baseHtml;
    }

    protected static function renderExcelPage(string $type, string $json, bool $withUi = true): string
    {
        $grid = json_decode($json, true);
        if (! is_array($grid)) {
            $grid = [];
        }

        $header = '<div style="text-align:center;margin-bottom:10px;font-weight:bold;font-size:15px;text-decoration:underline;">'
            . e(self::TYPES[$type]['pdf_title'] ?? '') . '</div>';

        return self::wrapStandalone($type, '', $header . self::gridToHtml($grid), $withUi);
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

    /**
     * GÖREV 1+2 — Dompdf'e verilen HTML'e evrensel CSS güvencesi enjekte eder.
     *
     * dompdf `@media print` kurallarını UYGULAMAZ; bu yüzden blade'lerdeki
     * print-bar/toolbar (.no-print) ve Latin-1 fontlar PDF'e sızıyordu. Bu CSS:
     *   - .no-print / .print-bar / .toolbar → display:none (UI butonları PDF'ten silinir)
     *   - * → font-family DejaVu Sans (Türkçe ğ/ş/ı/ö/ü/ç tam render; dompdf Type1 yok)
     */
    public static function pdfCssEnjekte(string $html): string
    {
        $css = '<style>'
            . '.no-print, .no-print-bar, .print-bar, .toolbar { display: none !important; }'
            . '* { font-family: "DejaVu Sans", sans-serif !important; }'
            . '</style>';

        return str_ireplace('</head>', $css . '</head>', $html);
    }

    /**
     * E-İmza damga bloğu enjeksiyonu — KÖKTEN KALDIRILDI.
     *
     * KESİN EMİR (Baş Mimar): Belgenin altına/üstüna SONDAN enjekte edilen
     * <div class="aykome-damga"> tarzı statik damga metodlarının ve </body>
     * önüne text/div bağlamalarının TÜMÜ silinir. İmza atan yöneticinin bilgileri
     * veya PAdES şerhi HTML layouta ASLA enjekte edilmez. Eski görsel damga bu
     * belgeleri 2. sayfaya taşıyordu; belge tek A4 çerçevesinde birebir kalır.
     *
     * Metod adı BC için tutulur (çağrılsa bile) ama içi BOŞTUR — 0 insertion.
     *
     * @param string $html      Değiştirilmeden aynen döndürülür
     * @param string $damgaHtml Yoksayılır (asla eklenmez)
     */
    public static function imzaDamgaEnjekte(string $html, string $damgaHtml): string
    {
        return $html;
    }
}
