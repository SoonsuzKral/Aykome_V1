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
    public function baslat(Application $application, string $pdfType): EImzaTransaction
    {
        $transactionId = 'txn_' . Str::uuid();
        $token = hash_hmac('sha256', $transactionId, config('app.key'));

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
        ]);
    }

    public function pdfOlustur(Application $application, string $pdfType): \Barryvdh\DomPDF\PDF
    {
        $application->load([
            'institution', 'creator',
            'surfaceLines.surfaceType',
            'gisNoktalari',
            'gisCizimleri.yolIliskileri',
        ]);

        $view = match ($pdfType) {
            'ruhsat' => 'admin.pdf.ruhsat',
            'pre_permit' => 'admin.pdf.pre_permit',
            'taahhutname' => 'admin.pdf.pre_permit',
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

        return Pdf::loadView($view, $data);
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

    public function tamamla(EImzaTransaction $transaction, string $imzaliPdfContent, array $imzalayanInfo): void
    {
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
