@component('mail::message')
# Enrollment Application Update

Hello {{ $application->first_name }},

Your enrollment application (**{{ $application->application_number }}**) was not approved at this time.

**Reason provided by the administrator:**

{{ $application->rejection_reason }}

You may submit a new application after addressing the items above. For example, if a document or photo was unclear, upload a clear, readable replacement. Contact the school administrator if you need help.

Thank you,<br>
{{ config('app.name') }}
@endcomponent
