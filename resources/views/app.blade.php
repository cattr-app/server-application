<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/favicon.ico">
    <link rel="preconnect" href="/api">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <x:sri.link mix href="/dist/app.css" rel="stylesheet" />
    <title>Cattr</title>
</head>
<body>
<noscript>
    <strong>We're sorry but Cattr doesn't work properly without JavaScript enabled. Please enable it to continue.</strong>
</noscript>
<div id="app"></div>
<script>
    window.__CATTR_FRONTEND_MODULES__ = @json(config('modules.frontend_activation', []));
</script>
<x:sri.script mix src="/dist/app.js"></x:sri-script>
</body>
</html>
