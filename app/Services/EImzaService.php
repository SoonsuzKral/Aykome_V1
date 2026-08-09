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
        $pdf = $this->pdfOlustur($application, $pdfType);
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
     * Belge PDF'ini üretir. GÖREV 1: Görsel imza damgası kaldırıldığı için belge
     * şablondan nasıl render edildiyse birebir korunur (görsel müdahale YOK).
     *
     * @param array|null $imzaDamgasi @deprecated İmza damgası kaldırıldı; parametre
     *                                geriye dönük uyumluluk (BC) için tutulur, hiç basılmaz.
     */
    public function pdfOlustur(Application $application, string $pdfType, ?array $imzaDamgasi = null): \Barryvdh\DomPDF\PDF
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
                $paper = ! empty(DocumentTemplateService::TYPES[$mapped]['landscape']) ? 'landscape' : 'portrait';

                // GÖREV 1+2: UI kalıntıları temizlenir + font DejaVu'ya sabitlenir.
                // GÖREV 1: Görsel EBYS imza damgası kaldırıldı — belge birebir korunur.
                $html = DocumentTemplateService::pdfCssEnjekte($html);

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
            'signatories' => SignatoryEngine::roleMap($pdfType, $application),
        ]);

        if ($pdfType === 'cover_letter') {
            $logoBase64 = null;
            if ($application->institution && $application->institution->logo_path) {
                try {
                    $fileContent = \Illuminate\Support\Facades\Storage::disk('public')->get($application->institution->logo_path);
                    if ($fileContent) {
                        $mime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($application->institution->logo_path);
                        $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode($fileContent);
                    }
                } catch (\Exception $e) {
                    $logoBase64 = null;
                }
            }
            $data['logo_base64'] = $logoBase64;
        }

        if ($pdfType === 'pre_permit') {
            $data['metin'] = DocumentRenderer::prePermitMetin($application);
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

        return Pdf::loadHTML($html);
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
                $m2 = $sl->quantity ? number_format((float)$sl->quantity, 2, ',', '.') : '0,00';
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
