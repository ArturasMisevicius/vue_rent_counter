<!DOCTYPE html>
<html>
<head>
    <title>{{ __('tenants.headings.index') }}</title>
</head>
<body>
    <h1>{{ __('tenants.headings.index') }}</h1>
    @foreach($tenants as $tenant)
        <p>{{ $tenant->name }} - {{ $tenant->email }}</p>
    @endforeach
</body>
</html>
