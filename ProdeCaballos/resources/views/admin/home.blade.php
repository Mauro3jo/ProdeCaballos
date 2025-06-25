<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link rel="icon" href="/faviconprode.ico?v=9" type="image/x-icon">
    <link rel="shortcut icon" href="/faviconprode.ico?v=9" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
