<x-mail::message>
# {{ $greeting }}

## {{ $title }}

{{ $message }}

@if (filled($actionUrl))
<x-mail::button :url="$actionUrl">
{{ $actionLabel }}
</x-mail::button>
@endif

{{ __('notifications.mail.thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
