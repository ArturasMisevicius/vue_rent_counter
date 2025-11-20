<!DOCTYPE html>
<html>
<head>
    <title>Tenant Profile</title>
</head>
<body>
    <h1>Tenant Profile</h1>
    <p>Name: {{ $user->name }}</p>
    <p>Email: {{ $user->email }}</p>
    <p>Role: {{ $user->role->value }}</p>
</body>
</html>
