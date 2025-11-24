<!DOCTYPE html>
<html>
<head>
    <title>Building Details</title>
</head>
<body>
    <h1>Building Details</h1>
    <p>Name: {{ $building->display_name }}</p>
    <p>Address: {{ $building->address }}</p>
    <p>Total Apartments: {{ $building->total_apartments }}</p>
</body>
</html>
