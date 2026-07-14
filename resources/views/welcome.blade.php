<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Test</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    Test Grid
    <div>
        <h1>Resorts</h1>

        <x-laragrid :grid="$this->gridDefinition('resorts')" />
    </div>
</body>

</html>
