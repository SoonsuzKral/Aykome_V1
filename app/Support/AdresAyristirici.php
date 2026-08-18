<?php

namespace App\Support;

/**
 * ÇÖZÜM_11A §6 — SERBEST METİN ADRES AYRIŞTIRICI.
 * ------------------------------------------------------------------
 * Başvuru YAPILANDIRILMIŞ "+ Mahalle & Sokak Ekle" akışıyla değil, TEK serbest
 * metin "Adres" kutusuyla girildiğinde Kazı Metraj tablosunda MAHALLE sütununa
 * adresin TAMAMI ("KADIKENDİ, 4151. SK, 41 FG, 63000 ŞANLIURFA M") düşüyor,
 * "CADDE VE SOKAK" sütunu ise boş kalıyordu. Bu sınıf ham metni makul biçimde
 * mahalle / cadde-sokak bileşenlerine böler.
 *
 * Kural: ASLA veri uydurmaz — ayrıştırılamayan metin eski davranışta olduğu gibi
 * mahalleye düşer, cadde boş kalır (yanlış sütuna yanlış bilgi yazılmaz).
 */
class AdresAyristirici
{
    /** Cadde/sokak/bulvar belirteçleri (kısaltmalar dahil). */
    private const CADDE_BELIRTECLERI = [
        'SOKAK', 'SOKAĞI', 'SOKAGI', 'SOK', 'SK',
        'CADDE', 'CADDESİ', 'CADDESI', 'CAD', 'CD',
        'BULVAR', 'BULVARI', 'BULV', 'BLV', 'BUL',
        'YOLU', 'YOL', 'KÜME', 'KUME', 'MEVKİİ', 'MEVKI', 'MEVKİ',
    ];

    /** Mahalle/köy belirteçleri. */
    private const MAHALLE_BELIRTECLERI = [
        'MAHALLESİ', 'MAHALLESI', 'MAHALLE', 'MAH', 'MH',
        'KÖYÜ', 'KOYU', 'KÖY', 'KOY', 'MEZRA', 'BELDE', 'BELDESİ',
    ];

    /**
     * Çok satırlı ham adresi tek bir mahalle/cadde özetine indirger.
     *
     * @return array{mahalle: string, cadde: string}
     */
    public static function ozet(?string $ham): array
    {
        $mahalleler = [];
        $caddeler = [];

        foreach (preg_split('/\R+/u', (string) $ham) ?: [] as $satir) {
            $p = self::ayir($satir);
            if ($p['mahalle'] !== '') {
                $mahalleler[$p['mahalle']] = true;
            }
            if ($p['cadde'] !== '') {
                $caddeler[$p['cadde']] = true;
            }
        }

        return [
            'mahalle' => implode(', ', array_keys($mahalleler)),
            'cadde' => implode(', ', array_keys($caddeler)),
        ];
    }

    /**
     * Tek satır serbest metin adresi mahalle + cadde/sokak olarak ayırır.
     *
     * @return array{mahalle: string, cadde: string}
     */
    public static function ayir(?string $ham): array
    {
        $metin = self::normalize($ham);
        if ($metin === '') {
            return ['mahalle' => '', 'cadde' => ''];
        }

        $parcalar = array_values(array_filter(
            array_map(static fn ($p) => trim((string) $p, " \t.-"), preg_split('/[,;]+/u', $metin) ?: []),
            static fn ($p) => $p !== ''
        ));

        if (count($parcalar) <= 1) {
            return self::tekParcayiAyir($parcalar[0] ?? $metin);
        }

        $mahalle = '';
        $caddeler = [];
        foreach ($parcalar as $parca) {
            if (self::caddeBelirteciVar($parca)) {
                // Parça içindeki kapı/bina kodu ("4151. SK. 41 FG") sütuna yazılmaz.
                $temiz = self::caddeTemizle($parca);
                if ($temiz !== '') {
                    $caddeler[$temiz] = true;
                }
                continue;
            }
            if ($mahalle === '' && ! self::atlanabilirParca($parca)) {
                // İlk anlamlı parça mahalledir; "MAH." belirteci varsa kesinleşir.
                $mahalle = $parca;
            }
        }

        // Hiçbir parça sokak olarak tanınmadıysa tek parça mantığına düş
        // (ör. "KADIKENDİ 4151. SK" virgülsüz yazılmış olabilir).
        if (! $caddeler) {
            return self::tekParcayiAyir($parcalar[0]);
        }

        return ['mahalle' => $mahalle, 'cadde' => implode(', ', array_keys($caddeler))];
    }

    /**
     * Virgülsüz tek parçayı ayırır:
     *  - "ATATÜRK MAH. 1234. SOKAK" → mahalle "ATATÜRK MAH.", cadde "1234. SOKAK"
     *  - "KADIKENDİ 4151. SK"       → mahalle "KADIKENDİ",    cadde "4151. SK"
     *
     * @return array{mahalle: string, cadde: string}
     */
    private static function tekParcayiAyir(string $parca): array
    {
        $parca = self::normalize($parca);
        if ($parca === '') {
            return ['mahalle' => '', 'cadde' => ''];
        }

        $kelimeler = preg_split('/\s+/u', $parca) ?: [];
        $caddeBaslangic = null;
        foreach ($kelimeler as $i => $kelime) {
            if (self::belirtecMi($kelime, self::CADDE_BELIRTECLERI)) {
                // Sokak adı belirtecin HEMEN öncesindeki kelimedir ("4151." / "ATATÜRK").
                $caddeBaslangic = max(0, $i - 1);
                break;
            }
            if (self::belirtecMi($kelime, self::MAHALLE_BELIRTECLERI)) {
                // Mahalle belirteci: mahalle burada biter, kalan kısım cadde adayıdır.
                $caddeBaslangic = $i + 1;
                break;
            }
        }

        if ($caddeBaslangic === null || $caddeBaslangic <= 0 || $caddeBaslangic >= count($kelimeler)) {
            // Ayrıştırılamadı → eski davranış: her şey mahalleye.
            return ['mahalle' => $parca, 'cadde' => ''];
        }

        $mahalle = trim(implode(' ', array_slice($kelimeler, 0, $caddeBaslangic)), " .,-");
        $cadde = self::caddeTemizle(implode(' ', array_slice($kelimeler, $caddeBaslangic)));

        if ($mahalle === '' || $cadde === '') {
            return ['mahalle' => $parca, 'cadde' => ''];
        }

        return ['mahalle' => $mahalle, 'cadde' => $cadde];
    }

    /**
     * Cadde/sokak metnini sütuna yazılabilir hale getirir: kapı-daire-blok ekleri
     * ve belirteçten sonra gelen çıplak bina kodları atılır.
     *  - "1234. SOKAK NO:5"  → "1234. SOKAK"   (numara eki)
     *  - "4151. SK. 41 FG"   → "4151. SK"      (çıplak kapı kodu)
     *  - "1234. SOKAK"       → "1234. SOKAK"   (numara sokak adının parçası, korunur)
     */
    private static function caddeTemizle(string $cadde): string
    {
        $cadde = trim($cadde, " .,-");
        $cadde = trim((string) preg_replace('/\s+\b(NO|NO:|N0|KAPI|DAİRE|DAIRE|BLOK|APT|KAT)\b.*$/u', '', $cadde));
        $cadde = trim((string) preg_replace('/\s+\d+\s*[A-ZÇĞİÖŞÜ]{0,3}$/u', '', $cadde), " .,-");

        // Harf içermeyen artık (ör. "YENİ MAH 5" → "5") sokak adı değildir.
        return preg_match('/\p{L}/u', $cadde) ? $cadde : '';
    }

    /** Boşluk sadeleştirme + TÜRKÇE büyük harfe çevirme (belge sütunları büyük harf). */
    private static function normalize(?string $ham): string
    {
        $metin = trim((string) preg_replace('/\s+/u', ' ', (string) $ham));
        if ($metin === '') {
            return '';
        }

        // mb_strtoupper Unicode kuralını uygular: 'i' → 'I'. Türkçede doğrusu 'İ'dir
        // ("Kadıkendi" → "KADIKENDİ"), bu yüzden noktalı i önce çevrilir.
        return mb_strtoupper(str_replace('i', 'İ', $metin), 'UTF-8');
    }

    private static function caddeBelirteciVar(string $parca): bool
    {
        foreach (preg_split('/\s+/u', $parca) ?: [] as $kelime) {
            if (self::belirtecMi($kelime, self::CADDE_BELIRTECLERI)) {
                return true;
            }
        }

        return false;
    }

    /** Posta kodu + il / kapı-daire gibi sütuna yazılmaması gereken parçalar. */
    private static function atlanabilirParca(string $parca): bool
    {
        if (preg_match('/^\d{5}\b/u', $parca)) {
            return true; // "63000 ŞANLIURFA M"
        }
        if (preg_match('/^(NO|N0|KAPI|DAİRE|DAIRE|BLOK|APT|KAT|D)\b/u', $parca)) {
            return true; // "NO: 5", "KAT 3"
        }

        // "41 FG" gibi kısa kapı/bina kodları (sayı + 1-3 harf) mahalle değildir.
        return (bool) preg_match('/^\d+\s*[A-ZÇĞİÖŞÜ]{1,3}$/u', $parca);
    }

    private static function belirtecMi(string $kelime, array $belirtecler): bool
    {
        $k = trim($kelime, " .,:;-");

        return $k !== '' && in_array($k, $belirtecler, true);
    }
}
