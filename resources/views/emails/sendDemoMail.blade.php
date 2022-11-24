<x-mail::message>

# {{ $mailData['title'] }}

{{ $mailData['content'] }}

@component('mail::button', ['url' => $mailData['url']])
Click Here to Reset Password
@endcomponent

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
