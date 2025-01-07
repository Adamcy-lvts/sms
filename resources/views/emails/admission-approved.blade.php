@component('mail::message')
# Admission Application Approved

Dear {{ $admission->guardian_name }},

We are pleased to inform you that the admission application for **{{ $admission->full_name }}** has been approved at {{ $admission->school->name }}.

## Admission Details:
- **Admission Number:** {{ $admission->admission_number }}
- **Class:** {{ $admission->classRoom?->name }}
- **Academic Session:** {{ $admission->academicSession->name }}

Please find attached the official admission letter. You can also view the admission letter online using the button below.

@component('mail::button', ['url' => $url])
View Admission Letter
@endcomponent

**Next Steps:**
1. Complete the registration process by bringing all required documents
2. Pay the necessary fees
3. Attend the orientation program

If you have any questions, please don't hesitate to contact us.

Best regards,  
{{ $admission->school->name }}
@endcomponent
