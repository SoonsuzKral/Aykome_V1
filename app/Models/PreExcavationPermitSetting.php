<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PreExcavationPermitSetting extends Model
{
    protected $table = 'pre_excavation_permit_settings';

    protected $fillable = [
        'title',
        'header_text',
        'footer_text',
        'sections',
        'logo_path',
        'signature_path',
        'stamp_path',
        'approver_name',
        'approver_title',
    ];

    protected function casts(): array
    {
        return [
            'sections' => 'array',
        ];
    }

    public static function getSingleton(): static
    {
        return static::firstOrCreate([]);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path && Storage::disk('public')->exists($this->logo_path)
            ? Storage::disk('public')->url($this->logo_path)
            : null;
    }

    public function signatureUrl(): ?string
    {
        return $this->signature_path && Storage::disk('public')->exists($this->signature_path)
            ? Storage::disk('public')->url($this->signature_path)
            : null;
    }

    public function stampUrl(): ?string
    {
        return $this->stamp_path && Storage::disk('public')->exists($this->stamp_path)
            ? Storage::disk('public')->url($this->stamp_path)
            : null;
    }

    public static function toBase64DataUri(?string $storagePath): ?string
    {
        if ($storagePath === null || ! Storage::disk('public')->exists($storagePath)) {
            return null;
        }

        $fullPath = Storage::disk('public')->path($storagePath);
        $mime = mime_content_type($fullPath) ?: 'image/png';
        $data = file_get_contents($fullPath);

        if ($data === false) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }
}
