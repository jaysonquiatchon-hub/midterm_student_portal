<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnrollmentApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin'
            && $this->route('application')?->status === 'pending';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:60'],
            'middle_name' => ['nullable', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['Female', 'Male', 'Non-binary', 'Prefer not to say'])],
            'civil_status' => ['nullable', Rule::in(['Single', 'Married', 'Widowed', 'Separated', 'Divorced'])],
            'nationality' => ['nullable', 'string', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'contact_number' => ['required', 'regex:/^[0-9+().\-\s]{7,30}$/'],
            'house_block_lot' => ['nullable', 'string', 'max:120'],
            'street' => ['nullable', 'string', 'max:160'],
            'barangay' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'province' => ['required', 'string', 'max:120'],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('status', 'active')],
            'program_id' => ['required', 'integer', Rule::exists('programs', 'id')->where(fn ($query) => $query
                ->where('department_id', $this->input('department_id'))
                ->where('status', 'active'))],
            'student_type' => ['required', Rule::in(['New Student', 'Transferee', 'Returning Student'])],
            'year_level' => ['required', 'integer', 'between:1,4'],
            'school_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['required', Rule::in(['1st', '2nd', 'Summer'])],
            'selected_subject_ids' => ['required', 'array', 'min:1'],
            'selected_subject_ids.*' => ['required', 'integer', 'distinct', Rule::exists('courses', 'id')->where(fn ($query) => $query
                ->where('program_id', $this->input('program_id'))
                ->where('year_level', $this->input('year_level'))
                ->where('semester', $this->input('semester'))
                ->where('status', 'active'))],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];
        foreach (['first_name', 'middle_name', 'last_name', 'suffix', 'nationality', 'email', 'contact_number', 'house_block_lot', 'street', 'barangay', 'city', 'province'] as $field) {
            if (is_string($value = $this->input($field))) {
                $normalized[$field] = trim(strip_tags($value));
            }
        }
        $this->merge($normalized);
    }
}
