@component('mail::message')
# Exception Occurred in {{ config('app.name') }}

**Time:** {{ $data['timestamp'] }}  
**Environment:** {{ $data['environment'] }}

## Exception Details
**Type:** {{ get_class($exception) }}  
**Message:** {{ $data['message'] }}  
**File:** {{ $data['file'] }}  
**Line:** {{ $data['line'] }}

## Request Details
**URL:** {{ $data['url'] }}  
**Method:** {{ $data['method'] }}  

@component('mail::panel')
### Stack Trace
{{ $data['trace'] }}
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent