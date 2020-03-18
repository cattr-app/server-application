@component('mail::message')
# Welcome to {{ config('app.name') }}!

You've been invited to {{ config('app.name') }}.

Please complete the registration account by following this link.

@component('mail::button', ['url' => $url])
    Create account
@endcomponent

Regards, {{ config('app.name') }}
@endcomponent
