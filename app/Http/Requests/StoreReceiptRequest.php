<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $application = $this->route('application');

        return $application !== null && $this->user()->can('update', $application);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'receipt_file' => ['required', 'file', 'mimes:pdf,jpeg,png,jpg', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
