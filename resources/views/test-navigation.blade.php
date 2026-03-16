<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>{{ __('app.testing.navigation_title') }}</title>
</head>
<body>
    {!! $testView !!}
</body>
</html>
