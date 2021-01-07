@component('mail::message')
# {{ __('emails.registration.welcome', ['name' => config('app.name')]) }}

{{ __('emails.registration.invited', ['name' => config('app.name')]) }}

{{ __('emails.registration.registration') }}

@component('mail::button', ['url' => $url])
    {{ __('emails.registration.create') }}
@endcomponent

{{ __('emails.registration.regards', ['name' => config('app.name')]) }}
@endcomponent
