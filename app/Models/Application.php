<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
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
            'licensed_at' => 'datetime',
            'status' => ApplicationStatus::class,
            'address_components' => 'array',
            'module_documents' => 'array',
            'approval_log' => 'array',
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
}
