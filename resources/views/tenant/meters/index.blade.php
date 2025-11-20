<!DOCTYPE html>
<html>
<head>
    <title>My Meters</title>
</head>
<body>
    <h1>My Meters</h1>
    @foreach($meters as $meter)
        <p>{{ $meter->type->value }} - {{ $meter->serial_number }}</p>
    @endforeach
</body>
</html>
