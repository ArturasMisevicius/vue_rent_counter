<!DOCTYPE html>
<html>
<head>
    <title>Tenants</title>
</head>
<body>
    <h1>Tenants</h1>
    @foreach($tenants as $tenant)
        <p>{{ $tenant->name }} - {{ $tenant->email }}</p>
    @endforeach
</body>
</html>
