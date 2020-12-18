@component('mail::message')
# {{ __('emails.invite.welcome', ['name' => config('app.name')]) }}

{{ __('emails.invite.use_these_credentials') }}: <br>

<strong>{{ __('emails.invite.login') }}:</strong> {{ $login }}<br>
<strong>{{ __('emails.invite.password') }}:</strong> {{ $password }}

@component('mail::button', ['url' => config('app.frontend_url')])
    {{ __('emails.invite.login_btn') }}
@endcomponent

{{ __('emails.invite.regards', ['name' => config('app.name')]) }}
@endcomponent
