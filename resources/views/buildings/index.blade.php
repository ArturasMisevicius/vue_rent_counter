<!DOCTYPE html>
<html>
<head>
    <title>Buildings</title>
</head>
<body>
    <h1>Buildings</h1>
    @foreach($buildings as $building)
        <p>{{ $building->address }} - {{ $building->total_apartments }} apartments</p>
    @endforeach
</body>
</html>
