<!doctype html>
<html lang="{{ config('app.locale') }}">
    <head>
        <base href="/">
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" href="/js/favicon.ico" type="image/x-icon">
        <title>Amazing Time</title>

    @foreach ($styles as $style)
        <link rel="stylesheet" href="/js/{{ $style }}" />
    @endforeach
    </head>
    <body>
        <app-root></app-root>

    @foreach ($scripts as $script)
        <script type="text/javascript" src="/js/{{ $script }}"></script>
    @endforeach
    </body>
</html>
