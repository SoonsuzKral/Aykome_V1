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
        'receipt_file_path',
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
            'status' => ApplicationStatus::class,
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
}
