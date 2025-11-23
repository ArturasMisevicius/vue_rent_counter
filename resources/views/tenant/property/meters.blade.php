<!DOCTYPE html>
<html>
<head>
    <title>Property Meters</title>
</head>
<body>
    <h1>Property Meters</h1>
    @if($meters->count() > 0)
        @foreach($meters as $meter)
            <p>{{ enum_label($meter->type) }} - {{ $meter->serial_number }}</p>
        @endforeach
    @else
        <p>No meters found</p>
    @endif
</body>
</html>
