<x-mail::message>
# Enrollment Confirmation

Hello {{ $application->full_name }},

Your enrollment application has been successfully approved.

**Student ID:** {{ $application->student?->student_id }}  
**Name:** {{ $application->full_name }}  
**Department:** {{ $application->department->name }}  
**Course:** {{ $application->program->name }}  
**Year Level:** {{ $application->year_level }}  
**Semester:** {{ $application->semester }}  
**School Year:** {{ $application->school_year }}

## SAMPLE / TEST ACCOUNT

**Username:** {{ $application->sample_username }}  
**Password:** {{ $application->sample_password }}

Important: These credentials are provided for testing and demonstration purposes only. The Student Portal login is not currently enabled for this sample account.

Thank you,<br>
{{ config('app.name') }}
</x-mail::message>
