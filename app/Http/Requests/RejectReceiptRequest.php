<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $application = $this->route('application');

        return $application !== null && $this->user()->can('approveReceipt', $application);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'review_notes' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }
}
