<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link rel="icon" href="{{ '/' . env('IMAGES_PUBLIC_PATH') . '/favicon.jpg' }}" type="image/jpeg">
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
