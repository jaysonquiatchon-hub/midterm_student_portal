<?php

namespace App\Http\Requests;

use App\Models\EnrollmentApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnrollmentApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $submissionToken = $this->input('submission_token');
        if (
            is_string($submissionToken)
            && $this->session()->get('enrollment_submission_token') === $submissionToken
            && EnrollmentApplication::query()->where('submission_token', $submissionToken)->exists()
        ) {
            return ['submission_token' => ['required', 'string']];
        }

        $draft = $this->session()->get('enrollment_draft', []);
        $studentType = $draft['student_type'] ?? '';

        $rules = [
            'submission_token' => ['required', 'string'],
            'first_name' => ['required', 'string', 'max:60'],
            'middle_name' => ['nullable', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['required', 'date'],
            'gender' => ['required', 'string'],
            'civil_status' => ['nullable', 'string'],
            'nationality' => ['nullable', 'string', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'contact_number' => ['required', 'string'],
            'house_block_lot' => ['nullable', 'string', 'max:120'],
            'street' => ['nullable', 'string', 'max:160'],
            'barangay' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'province' => ['required', 'string', 'max:120'],
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'student_type' => ['required', 'string'],
            'year_level' => ['required', 'integer'],
            'school_year' => ['required', 'string'],
            'semester' => ['required', 'string'],
            'selected_subject_ids' => ['required', 'array', 'min:1'],
            'selected_subject_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('courses', 'id')->where(fn ($query) => $query
                    ->where('program_id', $draft['program_id'] ?? null)
                    ->where('year_level', $draft['year_level'] ?? null)
                    ->where('semester', $draft['semester'] ?? null)
                    ->where('status', 'active')),
            ],
        ];

        foreach (config("enrollment.requirements.{$studentType}", []) as $key => $label) {
            $rules["requirements.{$key}"] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:1020'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $draft = $this->session()->get('enrollment_draft', []);
        $draft['submission_token'] = $this->input(
            'submission_token',
            $this->session()->get('enrollment_submission_token'),
        );

        $this->merge($draft);
    }
}
