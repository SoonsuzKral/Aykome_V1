<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Support\AykomeMath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Application extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'application_no',
        'parent_id',
        'is_additional_permit',
        'institution_id',
        'created_by',
        'status',
        'applicant_first_name',
        'applicant_last_name',
        'applicant_national_id',
        'tc_no',
        'identity_no',
        'applicant_phone',
        'excavation_reason',
        'work_type',
        'description',
        'start_date',
        'end_date',
        'total_area_m2',
        'total_price',
        'discovery_amount',
        'kdv_amount',
        'ruhsat_harci',
        'kesif_bedeli',
        'ztb_toplam',
        'teminat_tutari',
        'genel_toplam',
        'width_m',
        'length_m',
        'deposit_amount',
        'excavation_amount',
        'payment_status',
        'approval_status',
        'price_approved_at',
        'price_approved_by',
        'receipt_approved_at',
        'receipt_approved_by',
        'pre_excavation_approved_at',
        'pre_excavation_approved_by',
        'pre_excavation_document_path',
        'rejection_reason',
        'address_text',
        'license_document_path',
        'licensed_at',
        'receipt_file_path',
        'project_code',
        'application_type',
        'ztb_receipt_info',
        'deposit_receipt_info',
        'deposit_refunded_at',
        'deposit_refund_doc',
        'is_deposit_refunded',
        'deposit_status',
        'deposit_refund_notes',
        'address_components',
        'vice_mayor_name',
        'tesis_sorumlusu',
        'mudur_adi',
        'mudur_unvani',
        'approval_stage',
        'process_id',
        'staff_approved_by',
        'staff_approved_at',
        'director_approved_by',
        'director_approved_at',
        'vice_mayor_approved_by',
        'vice_mayor_approved_at',
        'module_documents',
        'taahhutname_notu',
        'assigned_to',
        'approval_log',
        'surface_sync_log',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'total_area_m2' => 'decimal:4',
            'total_price' => 'decimal:3',
            'discovery_amount' => 'decimal:3',
            'width_m' => 'decimal:3',
            'length_m' => 'decimal:3',
            'deposit_amount' => 'decimal:3',
            'excavation_amount' => 'decimal:3',
            'price_approved_at' => 'datetime',
            'receipt_approved_at' => 'datetime',
            'pre_excavation_approved_at' => 'datetime',
            'staff_approved_at' => 'datetime',
            'director_approved_at' => 'datetime',
            'vice_mayor_approved_at' => 'datetime',
            'deposit_refunded_at' => 'datetime',
            'is_deposit_refunded' => 'boolean',
            'is_additional_permit' => 'boolean',
            'licensed_at' => 'datetime',
            'status' => ApplicationStatus::class,
            'address_components' => 'array',
            'module_documents' => 'array',
            'approval_log' => 'array',
            'surface_sync_log' => 'array',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('petition')->singleFile();
        $this->addMediaCollection('pre_excavation_photos');
        $this->addMediaCollection('attachments');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(ProcessDefinition::class, 'process_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function priceApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'price_approved_by');
    }

    public function receiptApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receipt_approved_by');
    }

    public function preExcavationApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pre_excavation_approved_by');
    }

    public function staffApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_approved_by');
    }

    public function directorApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_approved_by');
    }

    public function viceMayorApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vice_mayor_approved_by');
    }

    public function excavationAreas(): HasMany
    {
        return $this->hasMany(ExcavationArea::class);
    }

    public function surfaceLines(): HasMany
    {
        return $this->hasMany(ApplicationSurfaceArea::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function fieldTasks(): HasMany
    {
        return $this->hasMany(FieldTask::class);
    }

    public function timelineLogs(): HasMany
    {
        return $this->hasMany(ApplicationTimelineLog::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(ApplicationAudit::class);
    }

    public function documentOverrides(): HasMany
    {
        return $this->hasMany(ApplicationDocumentOverride::class);
    }

    public function gisNoktalari(): HasMany
    {
        return $this->hasMany(GisBasvuruNokta::class, 'basvuru_id');
    }

    public function gisCizimleri(): HasMany
    {
        return $this->hasMany(GisCizim::class, 'basvuru_id');
    }

    public function extraPermits(): HasMany
    {
        return $this->hasMany(ExtraPermit::class);
    }

    /** Asıl başvuru (Ek Ruhsat'ın üstü). */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** Bu başvurudan üretilen Ek Ruhsatlar. */
    public function additionalPermits(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Başvuruya bağlı cadde/sokak listesini normalize eder.
     * Öncelik: address_components (create/edit ekranından gelen {mahalle, streets}),
     * yoksa GIS ilişkileri (gisCizimleri.yolIliskileri + gisNoktalari).
     * Dönen yapı: [['mahalle' => ..., 'yol' => ...], ...] — büyük harfe çevrilmiş,
     * boşluklar temizlenmiş, tekrarlar elenmiş.
     */
    public function streetLines(): array
    {
        $this->loadMissing(['gisCizimleri.yolIliskileri', 'gisNoktalari']);

        $rows = [];

        // 1) address_components: [ {mahalle, streets:[...]}, ... ]
        $components = $this->address_components ?? [];
        if (is_array($components)) {
            foreach ($components as $adres) {
                $mahalle = mb_strtoupper(trim((string) ($adres['mahalle'] ?? '')), 'UTF-8');
                $streets = $adres['streets'] ?? [];
                if (is_string($streets)) {
                    $streets = array_filter(array_map('trim', explode("\n", $streets)));
                }
                foreach ((array) $streets as $sokak) {
                    $sokak = trim((string) $sokak);
                    if ($sokak === '') continue;
                    $rows[] = [
                        'mahalle' => $mahalle !== '' ? $mahalle : 'BELİRTİLMEMİŞ MAHALLE',
                        'yol' => mb_strtoupper($sokak, 'UTF-8'),
                    ];
                }
            }
        }

        // 2) GIS çizimleri → yol ilişkileri
        if (empty($rows) && $this->relationLoaded('gisCizimleri')) {
            foreach ($this->gisCizimleri as $cizim) {
                foreach ($cizim->yolIliskileri ?? collect() as $yol) {
                    $sokak = trim((string) ($yol->yol_adi ?? ''));
                    if ($sokak === '') continue;
                    $rows[] = [
                        'mahalle' => $yol->mahalle
                            ? mb_strtoupper(trim((string) $yol->mahalle), 'UTF-8')
                            : 'BELİRTİLMEMİŞ MAHALLE',
                        'yol' => mb_strtoupper($sokak, 'UTF-8'),
                    ];
                }
            }
        }

        // 3) GIS noktaları (parsel bazlı adres)
        if (empty($rows) && $this->relationLoaded('gisNoktalari')) {
            foreach ($this->gisNoktalari as $nokta) {
                if (empty($nokta->parsel)) continue;
                $mahalle = $nokta->mahalle
                    ? mb_strtoupper(trim((string) $nokta->mahalle), 'UTF-8')
                    : 'BELİRTİLMEMİŞ MAHALLE';
                $parselAdi = 'PARSEL: ' . ($nokta->ada ? $nokta->ada . '/' : '') . $nokta->parsel;
                $rows[] = ['mahalle' => $mahalle, 'yol' => mb_strtoupper($parselAdi, 'UTF-8')];
            }
        }

        // 4) Yedek: address_text ilk satırı
        if (empty($rows) && ! empty($this->address_text)) {
            $ilkSatir = trim(explode("\n", $this->address_text)[0] ?? '');
            if ($ilkSatir !== '') {
                $rows[] = [
                    'mahalle' => 'BELİRTİLMEMİŞ MAHALLE',
                    'yol' => mb_strtoupper($ilkSatir, 'UTF-8'),
                ];
            }
        }

        // Dedupe (mahalle + yol ikilisi)
        $seen = [];
        $unique = [];
        foreach ($rows as $r) {
            $key = $r['mahalle'] . '|' . $r['yol'];
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $unique[] = $r;
        }

        return $unique;
    }

    /** Geçerli (boş olmayan) sokak sayısı. */
    public function streetCount(): int
    {
        return count($this->streetLines());
    }

    /** MUHTELİF kuralı: 3'ten fazla cadde/sokak varsa true. */
    public function isMuhtelif(): bool
    {
        return $this->streetCount() > 3;
    }

    /** Vatandaş/başvuran tam adı (ilk + soyisim). */
    public function getApplicantNameAttribute(): ?string
    {
        return trim(($this->applicant_first_name ?? '') . ' ' . ($this->applicant_last_name ?? '')) ?: null;
    }

    /** İlçe adı — başvuru/bağlı GIS verisinden. */
    public function getDistrictNameAttribute(): ?string
    {
        $d = trim((string) ($this->district ?? ''));

        return $d !== '' ? $d : 'EYYÜBİYE';
    }

    /** EK-1 sayfası için mahalle bazında gruplanmış sokak listesi. */
    public function streetLinesGroupedByMahalle(): array
    {
        $grouped = [];
        foreach ($this->streetLines() as $r) {
            $grouped[$r['mahalle']][] = $r['yol'];
        }
        ksort($grouped);

        return $grouped;
    }

    /**
     * Belirli bir belge modülü için en güncel imzalı dosya yolunu döndürür.
     * Öncelik: e-imza canonical kopyası → belediye yüklemesi → kurum yüklemesi.
     * Dosya yoksa null döner (hard-coded isim/makam içermez).
     */
    public function moduleSignedPath(string $module): ?string
    {
        $docs = $this->module_documents ?? [];
        $d = $docs[$module] ?? [];

        $candidates = [
            $d['e_imza']['signed_path'] ?? null,
            $d['belediye_path'] ?? null,
            $d['kurum_path'] ?? null,
        ];

        foreach ($candidates as $path) {
            if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Bir belge modülünün e-imza ile tamamlanıp tamamlanmadığı.
     */
    public function moduleEImzaTamamlandi(string $module): bool
    {
        $docs = $this->module_documents ?? [];

        return ! empty($docs[$module]['e_imza']['durum'] ?? null);
    }

    protected ?array $calcFiguresCache = null;

    /** Başvuru kurumunun Dicle Elektrik olup olmadığı (ad bazlı, vergi no'ya bağımlı değil). */
    public function isDicle(): bool
    {
        // strtolower ASCII-only olduğundan İ/ı/i/I Türkçe karakter sorunlarını
        // tamamen bypass eder: 'Dicle Elektrik' içinde İ/ı yoktur.
        $name = strtolower(trim((string) ($this->institution?->name ?? '')));

        return str_contains($name, 'dicle elektrik');
    }

    /** KURAL 1: Alt kurum başvurusu mu? (Merkez Belediye DEĞİL ve kurum atanmış) */
    public function isInstitutionApplication(): bool
    {
        return (bool) $this->institution_id && ! (bool) ($this->institution?->is_municipality ?? false);
    }

    /**
     * TEK MUHASEBE KAYNAĞI (Single Source of Truth).
     * Dashboard, PDF/Ruhsat/Tahakkuk fişleri ve şablon servisleri buradan okur.
     * Blade/Controller içinde asla fiyat biçilmez — değer burada üretilir.
     *
     * Kurallar (business):
     *  - KURAL 1: Alt kurum (Merkez Bld/Vatandaş DEĞİL) başvurusu ise TEMİNAT = 0 olur.
     *  - KURAL 2: Başvuru kurumu "Dicle Elektrik Dağıtım A.Ş." ise ayrıca RUHSAT HARCI = 0 olur.
     *  - Ek Ruhsat (is_additional_permit) ise de TEMİNAT = 0 olur.
     */
    public function calcFigures(): array
    {
        if ($this->calcFiguresCache !== null) {
            return $this->calcFiguresCache;
        }

        $this->loadMissing(['surfaceLines.surfaceType', 'institution']);

        $rows = [];
        foreach ($this->surfaceLines ?? [] as $line) {
            $rows[] = [
                'quantity' => $line->quantity ?? 0,
                'price_per_m2' => $line->surfaceType?->price_per_m2 ?? 0,
            ];
        }

        $ctx = [
            'isDicle' => $this->isDicle(),
            'isInstitutionApp' => $this->isInstitutionApplication(),
            'isAdditionalPermit' => (bool) ($this->is_additional_permit ?? false),
        ];

        return $this->calcFiguresCache = AykomeMath::compute($rows, $ctx);
    }

    /** Toplam kazı miktarı (m²). */
    public function getToplamMiktarAttribute(): string
    {
        return number_format($this->calcFigures()['toplam_miktar'], 2, '.', '');
    }

    /** Zemin Tahrip Bedeli (ZTB). */
    public function getZtbAmountAttribute(): string
    {
        return number_format($this->calcFigures()['ztb_amount'], 2, '.', '');
    }

    /** K.D.V. (%20). */
    public function getKdvAmountAttribute(): string
    {
        return number_format($this->calcFigures()['kdv_amount'], 2, '.', '');
    }

    /** Ruhsat Harcı — Dicle Elektrik için kural gereği 0. */
    public function getLicenseFeeAttribute(): string
    {
        return number_format($this->calcFigures()['license_fee'], 2, '.', '');
    }

    /** Keşif Bedeli. */
    public function getDiscoveryFeeAttribute(): string
    {
        return number_format($this->calcFigures()['discovery_fee'], 2, '.', '');
    }

    /** ZTB Toplam (ZTB + KDV + Ruhsat Harcı + Keşif). */
    public function getZtbTotalAttribute(): string
    {
        return number_format($this->calcFigures()['ztb_total'], 2, '.', '');
    }

    /** Teminat — kurum başvurularında kural gereği 0. */
    public function getTeminatAmountAttribute(): string
    {
        return number_format($this->calcFigures()['teminat'], 2, '.', '');
    }

    /** Genel Toplam (tüm kurallar sonrası ödenecek tutar). */
    public function getGeneralTotalAttribute(): string
    {
        return number_format($this->calcFigures()['general_total'], 2, '.', '');
    }

    // DB kolon adlarıyla uyumlu takma adlar (şablon/belgeler için).
    public function getRuhsatHarciAttribute(): string
    {
        return $this->license_fee;
    }

    public function getKesifBedeliAttribute(): string
    {
        return $this->discovery_fee;
    }

    public function getZtbToplamAttribute(): string
    {
        return $this->ztb_total;
    }

    public function getTeminatTutariAttribute(): string
    {
        return $this->teminat_amount;
    }

    public function getGenelToplamAttribute(): string
    {
        return $this->general_total;
    }
}
