<!DOCTYPE html>
<html>
<head>
    <title>Manager Profile</title>
</head>
<body>
    <h1>Manager Profile</h1>
    <p>Name: {{ $user->name }}</p>
    <p>Email: {{ $user->email }}</p>
    <p>Role: {{ enum_label($user->role) }}</p>
</body>
</html>
