<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnrollmentStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array((int) $this->route('step'), [1, 2, 3, 4], true);
    }

    public function rules(): array
    {
        return match ((int) $this->route('step')) {
            1 => [
                'first_name' => ['required', 'string', 'max:60'],
                'middle_name' => ['nullable', 'string', 'max:60'],
                'last_name' => ['required', 'string', 'max:60'],
                'suffix' => ['nullable', 'string', 'max:20'],
                'birth_date' => ['required', 'date', 'before:today'],
                'gender' => ['required', Rule::in(['Female', 'Male', 'Non-binary', 'Prefer not to say'])],
                'civil_status' => ['nullable', Rule::in(['Single', 'Married', 'Widowed', 'Separated', 'Divorced'])],
                'nationality' => ['nullable', 'string', 'max:80'],
            ],
            2 => [
                'email' => ['required', 'email:rfc', 'max:255'],
                'contact_number' => ['required', 'regex:/^[0-9+().\-\s]{7,30}$/'],
                'house_block_lot' => ['nullable', 'string', 'max:120'],
                'street' => ['nullable', 'string', 'max:160'],
                'barangay' => ['required', 'string', 'max:120'],
                'city' => ['required', 'string', 'max:120'],
                'province' => ['required', 'string', 'max:120'],
            ],
            3 => [
                'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('status', 'active')],
                'program_id' => [
                    'required',
                    'integer',
                    Rule::exists('programs', 'id')->where(fn ($query) => $query
                        ->where('department_id', $this->input('department_id'))
                        ->where('status', 'active')),
                ],
                'student_type' => ['required', Rule::in(['New Student', 'Transferee', 'Returning Student'])],
                'year_level' => ['required', 'integer', 'between:1,4'],
                'school_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
                'semester' => ['required', Rule::in(['1st', '2nd', 'Summer'])],
            ],
            4 => [
                'selected_subject_ids' => ['required', 'array', 'min:1'],
                'selected_subject_ids.*' => [
                    'required',
                    'integer',
                    'distinct',
                    Rule::exists('courses', 'id')->where(fn ($query) => $query
                        ->where('program_id', $this->session()->get('enrollment_draft.program_id'))
                        ->where('year_level', $this->session()->get('enrollment_draft.year_level'))
                        ->where('semester', $this->session()->get('enrollment_draft.semester'))
                        ->where('status', 'active')),
                ],
            ],
            default => [],
        };
    }

    protected function prepareForValidation(): void
    {
        $textFields = match ((int) $this->route('step')) {
            1 => ['first_name', 'middle_name', 'last_name', 'suffix', 'nationality'],
            2 => ['email', 'contact_number', 'house_block_lot', 'street', 'barangay', 'city', 'province'],
            default => [],
        };
        $normalized = [];

        foreach ($textFields as $field) {
            $value = $this->input($field);
            if (is_string($value)) {
                $normalized[$field] = trim(strip_tags($value));
            }
        }

        $this->merge($normalized);
    }
}
