<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TransferTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $application = $this->route('application');

        return $this->user()->can('transferTask', $application);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'assigned_to' => ['required', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $id = $this->input('assigned_to');
            if (! $id) {
                return;
            }
            $user = User::query()->find($id);
            if (! $user || ! $user->hasRole('field-team')) {
                $v->errors()->add('assigned_to', 'Sadece saha ekibi rolündeki kullanıcılar seçilebilir.');
            }
        });
    }
}
