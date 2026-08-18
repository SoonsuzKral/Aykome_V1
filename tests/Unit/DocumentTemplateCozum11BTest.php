<?php

namespace Tests\Unit;

use App\Enums\ApplicationStatus;
use Tests\TestCase;

/**
 * ÇÖZÜM_11B regresyon testleri — Ödeme Üst Yazı modülü.
 *
 * - Yeni statüler enum'da mevcut ve label'ları dolu.
 * - workflowStep: belediye metraj onayı → Step 3 Ödeme Üst Yazı; kurum yalnızca
 *   gönderim sonrası (odeme_ust_yazi_sent) Step 4'ü görür (pending kuruma gizli).
 * - Sonraki adımlar kaydı: kurum Tahakkuk Step 5 / Taahhütname Step 6 / Ruhsat Step 7.
 */
class DocumentTemplateCozum11BTest extends TestCase
{
    public function test_yeni_statusler_enumda_tanimli(): void
    {
        $this->assertSame('odeme_ust_yazi_pending', ApplicationStatus::OdemeUstYaziPending->value);
        $this->assertSame('odeme_ust_yazi_sent', ApplicationStatus::OdemeUstYaziSent->value);

        $this->assertSame('Ödeme Üst Yazı Açıldı', ApplicationStatus::OdemeUstYaziPending->label());
        $this->assertSame('Ödeme Üst Yazı Kuruma Gönderildi', ApplicationStatus::OdemeUstYaziSent->label());
    }

    public function test_belediye_metraj_sonrasi_odeme_ust_yazi_step3(): void
    {
        $s = ApplicationStatus::workflowStep('metrage_approved', true);
        $this->assertSame(2, $s['step']);
        $this->assertSame('Kazı Metraj Bilgi', $s['label']);

        $s = ApplicationStatus::workflowStep('odeme_ust_yazi_pending', true);
        $this->assertSame(3, $s['step']);
        $this->assertSame('Ödeme Üst Yazı', $s['label']);
        $this->assertSame('odeme_ust_yazi', $s['module']);

        $s = ApplicationStatus::workflowStep('odeme_ust_yazi_sent', true);
        $this->assertSame(3, $s['step']);
    }

    public function test_kurum_yalnizca_sent_sonrasi_step4_görür(): void
    {
        // pending kurumda GÖRÜNMEZ (Beklemede fallback) — modül adı çıkmaz
        $s = ApplicationStatus::workflowStep('odeme_ust_yazi_pending', false);
        $this->assertNotSame('odeme_ust_yazi', $s['module']);

        // sent → alt kurum Step 4 Ödeme Üst Yazı
        $s = ApplicationStatus::workflowStep('odeme_ust_yazi_sent', false);
        $this->assertSame(4, $s['step']);
        $this->assertSame('Ödeme Üst Yazı', $s['label']);
        $this->assertSame('odeme_ust_yazi', $s['module']);
    }

    public function test_sonraki_adimlar_kaydi(): void
    {
        // Kurum: Tahakkuk 5, Taahhütname 6, Ruhsat 7
        $this->assertSame(5, ApplicationStatus::workflowStep('tahakkuk_sent', false)['step']);
        $this->assertSame(6, ApplicationStatus::workflowStep('taahhutname_sent', false)['step']);
        $this->assertSame(7, ApplicationStatus::workflowStep('ruhsat_sent', false)['step']);

        // Belediye: Taahhütname 4, Ruhsat 5 (Ödeme Üst Yazı 3'ten sonra kayar)
        $this->assertSame(4, ApplicationStatus::workflowStep('taahhutname_sent', true)['step']);
        $this->assertSame(5, ApplicationStatus::workflowStep('ruhsat_sent', true)['step']);
    }

    public function test_workflow_steps_listeleri_odeme_ust_yaziyi_icerir(): void
    {
        $muni = ApplicationStatus::workflowSteps(true);
        $this->assertSame('Ödeme Üst Yazı', $muni[2]['label']);
        $this->assertCount(5, $muni);

        $kurum = ApplicationStatus::workflowSteps(false);
        $this->assertSame('Ödeme Üst Yazı', $kurum[3]['label']);
        $this->assertCount(7, $kurum);
        $this->assertSame('Ruhsat', $kurum[6]['label']);
    }
}