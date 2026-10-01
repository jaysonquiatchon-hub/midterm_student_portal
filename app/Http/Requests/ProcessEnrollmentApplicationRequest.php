<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessEnrollmentApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['approve', 'reject', 'under_review'])],
            'rejection_reason' => ['required_if:action,reject', 'nullable', 'string', 'max:2000'],
        ];
    }
}
