<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PermitSetting extends Model
{
    protected $table = 'permit_settings';

    protected $fillable = [
        'institution_name',
        'institution_address',
        'department_name',
        'institution_logo_path',
        'director_name',
        'director_title',
        'preparer_name',
        'preparer_title',
        'preparer_signature_path',
        'approver_name',
        'approver_title',
        'secondary_approver_name',
        'secondary_approver_title',
        'director_signature_path',
        'municipality_stamp_path',
        'validity_agreement',
        'footer_note',
    ];

    /**
     * Returns the singleton settings row, creating an empty one if it doesn't exist.
     */
    public static function getSingleton(): static
    {
        return static::firstOrCreate([]);
    }

    public function institutionLogoUrl(): ?string
    {
        if (! $this->institution_logo_path) {
            return null;
        }
        return Storage::disk('public')->exists($this->institution_logo_path)
            ? Storage::disk('public')->url($this->institution_logo_path)
            : null;
    }

    public function directorSignatureUrl(): ?string
    {
        if (! $this->director_signature_path) {
            return null;
        }
        return Storage::disk('public')->exists($this->director_signature_path)
            ? Storage::disk('public')->url($this->director_signature_path)
            : null;
    }

    public function municipalityStampUrl(): ?string
    {
        if (! $this->municipality_stamp_path) {
            return null;
        }
        return Storage::disk('public')->exists($this->municipality_stamp_path)
            ? Storage::disk('public')->url($this->municipality_stamp_path)
            : null;
    }

    public function preparerSignatureUrl(): ?string
    {
        if (! $this->preparer_signature_path) {
            return null;
        }
        return Storage::disk('public')->exists($this->preparer_signature_path)
            ? Storage::disk('public')->url($this->preparer_signature_path)
            : null;
    }

    /**
     * Returns the file content as base64 data URI for DomPDF embedding.
     */
    public static function toBase64DataUri(?string $storagePath): ?string
    {
        if (! $storagePath) {
            return null;
        }
        if (! Storage::disk('public')->exists($storagePath)) {
            return null;
        }
        $content  = Storage::disk('public')->get($storagePath);
        $mimeType = Storage::disk('public')->mimeType($storagePath);
        return 'data:' . $mimeType . ';base64,' . base64_encode($content);
    }
}
