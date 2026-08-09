<?php

namespace App\Services;

use App\Models\Application;
use App\Models\PreExcavationPermitSetting;

class DocumentRenderer
{
    const S = "\xEE\x98\x83";

    public static function renderPrePermit(Application $application): string
    {
        $application->load(['institution', 'creator']);
        $settings = PreExcavationPermitSetting::first() ?? new PreExcavationPermitSetting;
        $html = self::read('dompdf/Ön_Kazı_İzni.html');
        return self::injectOverlay(self::apply($html, self::prePermitMap($application, $settings)));
    }

    public static function renderCoverLetter(Application $application): string
    {
        $application->load(['institution', 'creator']);
        $html = self::read('dompdf/dicle-üst-yazı.html');
        return self::injectOverlay(self::apply($html, self::coverLetterMap($application)));
    }

    public static function renderRuhsat(Application $application): string
    {
        $application->load(['institution', 'creator']);
        $html = self::read('dompdf/ruhsat.html');
        return self::injectOverlay(self::apply($html, self::ruhsatMap($application)));
    }

    public static function renderMetrajForm(Application $application): string
    {
        $application->load(['institution', 'creator']);
        $html = self::read('dompdf/YerBilgiFormu_Metraj-Formu.html');
        return self::injectOverlay(self::apply($html, self::metrajMap($application)));
    }

    private static function read(string $path): string
    {
        $full = base_path($path);
        if (!file_exists($full)) {
            abort(404, 'Şablon dosyası bulunamadı: ' . $path);
        }
        return file_get_contents($full);
    }

    private static function apply(string $html, array $map): string
    {
        preg_match('/<img\s+class="bi[^"]*"[^>]*>/', $html, $matches);
        $imgTag = $matches[0] ?? '';

        if ($imgTag) {
            $html = str_replace($imgTag, '<!--BI-->', $html);
        }

        $html = strtr($html, $map);

        if ($imgTag) {
            $html = str_replace('<!--BI-->', $imgTag, $html);
        }

        return $html;
    }

    private static function prePermitMap(Application $app, PreExcavationPermitSetting $settings): array
    {
        $ws = '<span class="_"> </span>';
        $signer = $settings->signer_name ?? $app->creator?->name ?? 'Yetkili';
        $signerTitle = $settings->approver_title ?? 'Belediye Başkan Yardımcısı';
        $date = now()->format('d.m.Y');
        $endDate = $app->end_date?->format('d.m.Y') ?? now()->addDays(30)->format('d.m.Y');
        $crDate = $app->created_at?->format('d.m.Y') ?? $date;
        $docPrefix = $settings->document_prefix ?? '18790261';
        $docNo = 'E-' . $docPrefix . '-' . str_pad($app->id, 6, '0', STR_PAD_LEFT);

        $instName = mb_strtoupper($app->institution?->name ?? 'KURUM', 'UTF-8');
        $instParts = explode(' ', $instName);
        $instHtmlOld = "DİCLE{$ws}ELEKTRİK{$ws}DAĞITIM{$ws}AŞ.&apos;a";
        $instHtmlNew = implode($ws, $instParts) . "{$ws}A.Ş.&apos;a";

        $signerParts = explode(' ', $signer);
        $signerHtmlOld = "Zeynelabidin{$ws}AKT<span class=\"_ _1\"></span>AŞOĞLU";
        $signerHtmlNew = implode($ws, $signerParts);

        $signer2HtmlOld = "Mustafa{$ws}Kemal{$ws}KARA<span class=\"_ _5\"></span>T<span class=\"_ _6\"></span>AŞ";
        $signer2HtmlNew = implode($ws, $signerParts);

        $titleParts = explode(' ', $signerTitle);
        $titleHtmlOld = "Belediye{$ws}Başkan{$ws}Y<span class=\"_ _6\"></span>ardımcısı";
        $titleHtmlNew = implode($ws, $titleParts);

        $description = mb_strtoupper($app->description ?? 'Kazı İzni Hk.', 'UTF-8');
        $descParts = explode(' ', $description);
        $descHtmlOld = "Kazı{$ws}İzni{$ws}Hk.";
        $descHtmlNew = implode($ws, $descParts);

        $md5 = md5($app->id . $app->created_at);
        $email = $app->institution?->email ?? 'eposta@eposta.kep.tr';

        return [
            $md5                                      => $md5,
            'eyyubiye@hs03.kep.tr'                     => $email,
            $signerHtmlOld                             => $signerHtmlNew,
            $instHtmlOld                               => $instHtmlNew,
            'E-18790261-755-555505'                    => $docNo,
            $descHtmlOld                               => $descHtmlNew,
            $signer2HtmlOld                            => $signer2HtmlNew,
            $titleHtmlOld                              => $titleHtmlNew,
            '09/06/2026'                               => $date,
            '13/07/2026'                               => $endDate,
            '30.04.2026'                               => $crDate,
        ];
    }

    private static function coverLetterMap(Application $app): array
    {
        $s = self::S;
        $docNo = 'E-50005665001100-100-' . str_pad($app->id, 7, '0', STR_PAD_LEFT);

        $instName = mb_strtoupper($app->institution?->name ?? 'KURUM', 'UTF-8');
        $instParts = explode(' ', $instName);
        $kurumAdiS = implode($s, $instParts) . $s . 'A.Ş.';

        return [
            'E-50005665001100-100-1176543'                 => $docNo,
            "DİCLE{$s}ELEKTRİK{$s}DAĞITIM{$s}A.Ş."         => $kurumAdiS,
            '01D0-6OP0-0HZV'                                => strtoupper(substr(md5($app->id), 0, 12)),
            "650{$s}mt2"                                    => ($app->total_area_m2 ?? '0') . "{$s}mt2",
        ];
    }

    private static function ruhsatMap(Application $app): array
    {
        $date = $app->created_at?->format('d.m.Y') ?? now()->format('d.m.Y');
        $endDate = $app->end_date?->format('d.m.Y') ?? now()->addDays(10)->format('d.m.Y');
        $ruhsatNo = date('Y') . '/' . $app->id;

        return [
            '7.07.2026'   => $date,
            '17.07.202'   => $endDate,
            '2026/21,1,5' => $ruhsatNo,
            '904,00'      => number_format((float)($app->deposit_amount ?? 0), 2, ',', '.'),
        ];
    }

    private static function metrajMap(Application $app): array
    {
        $date = $app->created_at?->format('d.m.Y') ?? now()->format('d.m.Y');
        $instName = mb_strtoupper($app->institution?->short_name ?? 'EYYÜBİYE', 'UTF-8');

        return [
            '22.05.2026'           => $date,
            'EYYÜBiYE'             => $instName,
            'DicLE'                => mb_strtoupper($app->institution?->name ?? 'KURUM', 'UTF-8'),
            '30.06.2026'           => $date,
            'c_26-1100-].063-0019' => str_replace('-', '_', strtolower($app->project_code ?? 'c_00_0000_0000_0000')),
        ];
    }

    private static function injectOverlay(string $html): string
    {
        $overlay = <<<'HTML'
<style>
@media print{.bi{display:none!important}}
.print-overlay{position:fixed;top:0;left:0;right:0;z-index:9999;background:#1a1a2e;padding:10px 20px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 10px rgba(0,0,0,0.3)}
.print-overlay .title{color:#fff;font-size:14px;font-weight:600}
.print-overlay .btn-group{display:flex;gap:8px}
.print-overlay .btn-print{background:#0f3460;color:#fff;border:none;padding:10px 24px;border-radius:6px;font-size:14px;cursor:pointer;transition:background .2s}
.print-overlay .btn-print:hover{background:#1a5276}
.print-overlay .btn-close{background:transparent;color:#aaa;border:1px solid #444;padding:10px 20px;border-radius:6px;font-size:14px;cursor:pointer;transition:all .2s}
.print-overlay .btn-close:hover{color:#fff;border-color:#888}
@media print{.print-overlay{display:none!important}}
</style>
<div class="print-overlay">
    <span class="title">Belge Önizleme</span>
    <div class="btn-group">
        <button class="btn-close" onclick="window.close()">Kapat</button>
        <button class="btn-print" onclick="window.print()">Yazdır / PDF</button>
    </div>
</div>
HTML;
        return str_replace('</body>', $overlay . '</body>', $html);
    }

    /**
     * Ön Kazı İzni yazısı — e-imza PDF'i (pre_permit) dahil tüm çıktılar
     * bu metni kullanır.
     */
    public static function prePermitMetin(Application $app): string
    {
        $inst = $app->institution?->name ?? '';
        $projectCode = $app->project_code ?? '';
        $ilce = $app->district ?? '';
        $isAdi = $app->work_type ?? $app->description ?? '';

        // Mahalle — önce GIS ilişkilerinden, yoksa address_text'in ilk satırından.
        $mahalle = '';
        if ($app->relationLoaded('gisNoktalari')) {
            foreach ($app->gisNoktalari as $n) {
                if (! empty($n->mahalle)) {
                    $mahalle = mb_strtoupper(trim((string) $n->mahalle), 'UTF-8');
                    break;
                }
            }
        }
        if ($mahalle === '' && $app->relationLoaded('gisCizimleri')) {
            foreach ($app->gisCizimleri as $cizim) {
                foreach ($cizim->yolIliskileri ?? collect() as $yol) {
                    if (! empty($yol->mahalle)) {
                        $mahalle = mb_strtoupper(trim((string) $yol->mahalle), 'UTF-8');
                        break 2;
                    }
                }
            }
        }
        if ($mahalle === '' && ! empty($app->address_text)) {
            $mahalle = mb_strtoupper(trim(explode("\n", $app->address_text)[0]), 'UTF-8');
        }

        return "
        <p>İlgi sayılı yazı ile; {$inst} Şanlıurfa Tesis Yöneticiliği {$projectCode}
        Proje Numarasıyla {$ilce} İlçesi {$mahalle} {$isAdi}
        çalışması için kazı izni talep edilmektedir.</p>
        <p>&quot;Altyapı Tesisi Açım Ruhsatı&quot; iş ve işlemlerinin kazı kesin metrajlarının tespit edilmesinden
        sonra tamamlanması, Yapılacak çalışmanın AYKOME Çalışma Usul ve Esasları Uygulama yönetmeliğine
        uygun olarak yapılması, çalışma yapılacak cadde ve sokakların kazı öncesinde Eyyübiye Belediyesi Fen
        işleri Müdürlüğü AYKOME Birimimize haber verilmesi ve diğer altyapı kuruluşlarının (AKSA Şanlıurfa
        Doğalgaz A.Ş. Telekom İl Müdürlüğü, SUSKİ Genel Müdürlüğü, v.b.) mevcut tesislerine zarar
        verilmesinin önlenmesi için bu kuruluşlara da yapılacak calışma hakkında bilgi verilmesi koşulu ile kazı</p>
        ";
    }
}
