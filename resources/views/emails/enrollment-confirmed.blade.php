<x-mail::message>
# Enrollment Successfully Processed

Hello {{ $enrollment->student->full_name }},

Your enrollment has been confirmed.

**Reference Number:** {{ $enrollment->reference_number }}  
**Status:** Enrolled  
**Academic Year:** {{ $enrollment->academic_year }}  
**Term:** {{ $enrollment->term }}  
**Curriculum:** {{ $enrollment->program->name }}  
**Processed:** {{ $enrollment->processed_at?->format('M j, Y') }}

Your assigned courses are now available in your Student Portal.

Regards,<br>
{{ config('app.name') }}
</x-mail::message>
