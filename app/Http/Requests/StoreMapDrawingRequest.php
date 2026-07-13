<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreMapDrawingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('applications.edit') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'polygon_geojson' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || $value === '') {
                        return;
                    }

                    json_decode($value, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $fail('Polygon GeoJSON formatı geçersiz.');
                    }
                },
            ],
            'total_area_m2' => ['nullable', 'numeric', 'min:0'],
            'center_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'center_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'address_text' => ['nullable', 'string', 'max:500'],
        ];
    }
}
