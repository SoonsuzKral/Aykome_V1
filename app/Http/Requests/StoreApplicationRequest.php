<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('applications.create');
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();
        // Kurum personeli (belediye/super-admin olmayan): controller zaten override eder.
        // Burada sadece digit temizliğini çalıştırmak yeterli; sahte veri validation'a girseydi
        // bile controller katmanı üzerine yazar.
        $rawPrimary = (string) ($this->input('applicant_national_id') ?: $this->input('tc_no') ?: $this->input('identity_no'));
        $rawTcNo = (string) $this->input('tc_no', '');
        $rawIdentityNo = (string) $this->input('identity_no', '');

        $primary = preg_replace('/\D+/', '', $rawPrimary) ?: null;
        $tcNo = preg_replace('/\D+/', '', $rawTcNo) ?: null;
        $identityNo = preg_replace('/\D+/', '', $rawIdentityNo) ?: null;

        $this->merge([
            'applicant_national_id' => $primary,
            'tc_no' => $tcNo ?: $primary,
            'identity_no' => $identityNo ?: $primary,
        ]);

        foreach (['width_m', 'length_m', 'quantity', 'multiplier', 'total_area_m2', 'deposit_amount', 'excavation_amount'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $val = $this->input($field);
                // Nokta (binlik) ve virgül (ondalık) temizliği: "2.500,00" → "2500.00"
                $val = str_replace('.', '', $val);   // binlik ayracını kaldır
                $val = str_replace(',', '.', $val);  // virgülü noktaya çevir
                $this->merge([$field => $val !== '' ? $val : null]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user();
        // Belediye/super-admin: TCKN zorunlu 11 hane.
        // Kurum personeli: TC ya da vergi no (esnek) — controller zaten auth()->user() ile ezer.
        $isInstitutionUser = $user && ! $user->hasRole(['super-admin', 'municipality-admin', 'municipality-staff']);

        $nationalIdRules = $isInstitutionUser
            ? ['nullable', 'string', 'max:20']
            : ['required', 'digits:11'];

        $tcAliasRules = $isInstitutionUser
            ? ['nullable', 'string', 'max:20']
            : ['nullable', 'digits:11'];

        return [
            'institution_id' => ['nullable', 'exists:institutions,id'],
            'applicant_first_name' => ['required', 'string', 'max:120'],
            'applicant_last_name' => ['required', 'string', 'max:120'],
            'applicant_national_id' => $nationalIdRules,
            'tc_no' => $tcAliasRules,
            'identity_no' => $tcAliasRules,
            'applicant_phone' => ['nullable', 'string', 'max:32'],
            'excavation_reason' => ['nullable', 'string', 'max:255'],
            'work_type' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'address_text' => ['nullable', 'string', 'max:500'],
            'polygon_geojson' => ['nullable', 'string'],
            'total_area_m2' => ['nullable', 'numeric', 'min:0'],
            'center_lat' => ['nullable', 'numeric'],
            'center_lng' => ['nullable', 'numeric'],
            'surface_type_id' => ['nullable', 'exists:surface_types,id'],
            'width_m' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'length_m' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'quantity' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'multiplier' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'excavation_amount' => ['nullable', 'numeric', 'min:0'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['nullable', 'file', 'mimetypes:application/pdf,image/jpeg,image/png,image/jpg,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/webp,image/gif,image/bmp,image/tiff', 'max:51200'],
        ];
    }
}
