<x-mail::message>
# Hello {{ $staff->full_name }}

Your system access account has been created. You can log in using the following credentials:

<x-mail::panel>
**Email:** {{ $staff->email }}  
**Password:** {{ $password }}
</x-mail::panel>

@if($forcePasswordChange)
**Important:** You will be required to change your password when you first log in.
@endif

<x-mail::button :url="$loginUrl">
Login to System
</x-mail::button>

For security reasons, please change your password immediately after logging in.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>