<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * SignerPlacementService
 * ============================================================
 * AMAÇ
 * ============================================================
 * Hangi belge tipinde (ruhsat / on_kazi / ust_yazi / tahsilat_fisi),
 * hangi süreç adımında (buro_personeli / birim_sefi / fen_isleri_muduru /
 * baskan_yardimcisi), HANGİ Blade placeholder'ının kiminle -ya da BOŞ-
 * doldurulacağını TEK noktadan yönetir.
 *
 * ============================================================
 * KRİTİK MİMARİ KURAL
 * ============================================================
 * Bu servis PDF'e SONRADAN yazı ÇİZMEZ. Sadece dompdf render'ından ÖNCE
 * Blade view'a geçilecek $data dizisini hazırlar. İmza (AKİS/PAdES) adımı
 * SADECE kriptografik imzalama yapar, görsel içeriğe DOKUNMAZ.
 *
 * Yanlış kullanım :  render() -> imzala() -> buraya_isim_çiz()
 * Doğru kullanım  :  buraya_isim_koy() -> render() -> imzala() [sadece mühür]
 *
 * ============================================================
 * ADAPTE ETMENİZ GEREKENLER (TODO'lar aşağıda işaretli)
 * ============================================================
 * Bu dosya bir REFERANS/İSKELETTİR — gerçek EImzaService, User modeli ve
 * süreç-adımı veri yapınıza (muhtemelen bir "başvuru" / "surec_adimlari"
 * tablosu) göre uyarlanmalı. Claude Code, gerçek kod tabanınızı görerek
 * bu TODO'ları doldurmalı.
 */
class SignerPlacementService
{
    /**
     * Süreç adımı => Blade placeholder key eşlemesi.
     * Görsel 4'teki 4 adımlı sürece göre (Panel > Alt Kurum > Süreç Adımları).
     */
    private const STEP_PLACEHOLDER_MAP = [
        'buro_personeli'    => 'imza_buro_personeli',
        'birim_sefi'        => 'imza_birim_sefi',
        'fen_isleri_muduru' => 'imza_fen_isleri_muduru',
        'baskan_yardimcisi' => 'imza_baskan_yardimcisi',
    ];

    /**
     * TODO — BELEDİYE İLE TEYİT EDİN:
     * Hangi adımların ismi ŞABLONDA STATİK/ÖNCEDEN BELLİ (örn. Fen İşleri
     * Müdürü hep aynı kişi, bir organizasyon tablosundan gelir) vs. hangileri
     * "kim imzalarsa o" DİNAMİK (örn. Başkan Yardımcısı — kullanıcının kendi
     * isteği: "Başkan Yardımcısı yazan yerin adını boş bırakacağız").
     *
     * Şu an sadece baskan_yardimcisi dinamik olarak işaretli — kullanıcının
     * mesajındaki açık talebe göre. Diğerleri de dinamikse buraya ekleyin.
     */
    private const DYNAMIC_STEPS = [
        'baskan_yardimcisi',
    ];

    public function __construct(
        // TODO: gerçek EImzaService'inizi buraya inject edin.
        // kullanicidanImzalayan(User $user): string metodu zaten var ve
        // çalışıyor (Spatie rolünden Türkçe unvan üretiyor) — burada
        // YENİDEN YAZILMIYOR, sadece çağrılıyor.
        private EImzaService $eImzaService
    ) {}

    /**
     * Bir belge render edilmeden ÖNCE (dompdf'e gitmeden önce) çağrılır.
     *
     * @param string   $documentType      'ruhsat' | 'on_kazi' | 'ust_yazi' | 'tahsilat_fisi'
     * @param string[] $tamamlananAdimlar Şu ana kadar tamamlanmış (imzalanmış/onaylanmış)
     *                                    adım key'leri, örn: ['buro_personeli', 'birim_sefi']
     * @param User|null $suAnkiKullanici  Şu an işlemi yapan/imzalayan kullanıcı
     *                                    (sadece DYNAMIC_STEPS için kullanılır)
     *
     * @return array<string,string> Blade'e geçilecek placeholder => değer dizisi.
     *                               Örn: ['imza_buro_personeli' => 'Ayşe Öztürk (Büro Personeli)',
     *                                     'imza_birim_sefi' => '',
     *                                     'imza_fen_isleri_muduru' => '',
     *                                     'imza_baskan_yardimcisi' => '']
     */
    public function yerlesimHazirla(
        string $documentType,
        array $tamamlananAdimlar,
        ?User $suAnkiKullanici = null
    ): array {
        $result = [];

        foreach (self::STEP_PLACEHOLDER_MAP as $stepKey => $placeholder) {

            $tamamlandiMi = in_array($stepKey, $tamamlananAdimlar, true);

            if (!$tamamlandiMi) {
                // Bu adıma henüz gelinmedi -> alan BOŞ kalır.
                // (Kullanıcının talebi: "Başkan Yardımcısı yazan yerin adını
                //  boş bırakacağız" — bu, henüz imzalanmamış TÜM adımlar için
                //  genel kural olarak uygulanıyor.)
                $result[$placeholder] = '';
                continue;
            }

            if (in_array($stepKey, self::DYNAMIC_STEPS, true)) {
                // Dinamik: o adımı FİİLEN tamamlayan kullanıcının bilgisi.
                // Bu, mevcut çalışan kullanicidanImzalayan() servisini
                // YENİDEN KULLANIR — yeni bir isim/unvan üretme mantığı
                // burada TEKRARLANMAZ.
                $result[$placeholder] = $suAnkiKullanici
                    ? $this->eImzaService->kullanicidanImzalayan($suAnkiKullanici)
                    : '';
            } else {
                // Statik: pozisyonun sabit sahibinden.
                $result[$placeholder] = $this->statikPozisyonSahibi($stepKey, $documentType);
            }
        }

        Log::info('[SignerPlacement] Yerleşim hazırlandı', [
            'belge_tipi' => $documentType,
            'tamamlanan' => $tamamlananAdimlar,
            'sonuc'      => $result,
        ]);

        return $result;
    }

    /**
     * Statik pozisyon sahiplerini döner.
     *
     * TODO: gerçek veri kaynağına bağlayın. İki olası model:
     *
     * (A) Ayrı bir "organizasyon pozisyonları" tablosu varsa:
     *     return DB::table('organizasyon_pozisyonlari')
     *         ->where('pozisyon_key', $stepKey)
     *         ->value('ad_soyad_unvan') ?? '';
     *
     * (B) Pozisyon sahibi de aslında bir User ise (rolüyle):
     *     $user = User::role($this->stepToSpatieRole($stepKey))->first();
     *     return $user ? $this->eImzaService->kullanicidanImzalayan($user) : '';
     *
     * Görsel 3'teki (ruhsat) örnekte "Fen İşleri Müdürü: Burak Bakır
     * YÜCETEPE" değeri muhtemelen (B) modeline uyuyor — o pozisyondaki
     * kullanıcı sistemde zaten rolüyle kayıtlı.
     */
    private function statikPozisyonSahibi(string $stepKey, string $documentType): string
    {
        // Placeholder — gerçek sorgu ile değiştirin.
        return '';
    }

    /**
     * Süreç adım key'ini Spatie rol adına çevirir.
     * TODO: gerçek rol isimlerinizle eşleştirin (municipality-staff,
     * municipality-admin, super-admin gibi görsel 4'te gördüklerimle
     * uyumlu olacak şekilde güncelleyin).
     */
    private function stepToSpatieRole(string $stepKey): string
    {
        return match ($stepKey) {
            'buro_personeli'    => 'municipality-staff',
            'birim_sefi'        => 'municipality-admin',
            'fen_isleri_muduru' => 'super-admin',
            'baskan_yardimcisi' => 'municipality-admin',
            default             => '',
        };
    }
}
