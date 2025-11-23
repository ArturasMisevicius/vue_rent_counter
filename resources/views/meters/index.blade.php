<!DOCTYPE html>
<html>
<head>
    <title>Meters</title>
</head>
<body>
    <h1>Meters</h1>
    @foreach($meters as $meter)
        <p>{{ $meter->serial_number }} - {{ enum_label($meter->type) }}</p>
    @endforeach
</body>
</html>
