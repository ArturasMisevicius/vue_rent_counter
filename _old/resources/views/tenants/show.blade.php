<!DOCTYPE html>
<html>
<head>
    <title>{{ __('tenants.headings.show') }}</title>
</head>
<body>
    <h1>{{ __('tenants.headings.show') }}</h1>
    <p>{{ __('tenants.labels.name') }}: {{ $tenant->name }}</p>
    <p>{{ __('tenants.labels.email') }}: {{ $tenant->email }}</p>
</body>
</html>
