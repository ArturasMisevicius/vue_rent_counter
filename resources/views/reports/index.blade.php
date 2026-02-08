<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>{{ __('reports.public.title') }}</title>
</head>
<body>
    <h1>{{ __('reports.public.title') }}</h1>
    <ul>
        <li><a href="{{ route('reports.consumption') }}">{{ __('reports.public.links.consumption') }}</a></li>
        <li><a href="{{ route('reports.revenue') }}">{{ __('reports.public.links.revenue') }}</a></li>
        <li><a href="{{ route('reports.outstanding') }}">{{ __('reports.public.links.outstanding') }}</a></li>
    </ul>
</body>
</html>
