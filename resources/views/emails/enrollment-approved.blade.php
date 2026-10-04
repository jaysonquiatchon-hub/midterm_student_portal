@component('mail::message')
# Enrollment Application Approved

Hello {{ $application->first_name }},

Your enrollment application (**{{ $application->application_number }}**) has been approved.

- **Student number:** {{ $application->student->student_id }}
- **Program:** {{ $application->program?->name ?? 'N/A' }}
- **Year Level:** {{ $application->year_level }}
- **Semester:** {{ $application->semester }}
- **School Year:** {{ $application->school_year }}

@if ($application->applicant_user_id)
You can sign in to the Student Portal using the email address and password already associated with your account.

@component('mail::button', ['url' => route('student.login')])
Student Login
@endcomponent
@else
Create your student account using the same first name, last name, email address, and student number listed in this approval. You will choose your own password during registration.

@component('mail::button', ['url' => route('portal.register')])
Create Student Account
@endcomponent
@endif

Thank you,<br>
{{ config('app.name') }}
@endcomponent