@component('mail::message')
# Welcome to {{ config('app.name') }}

Use these credentials for your first login: <br>

<strong>Login:</strong> {{ $login }}<br>
<strong>Password:</strong> {{ $password }}

@component('mail::button', ['url' => config('app.frontend_url')])
    Login
@endcomponent

Regards, {{ config('app.name') }}
@endcomponent
