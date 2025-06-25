<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prode de Caballos</title>

    <!-- Favicon máxima compatibilidad desde raíz / -->
    <link rel="icon" href="/faviconprode.ico?v=9" type="image/x-icon">
    <link rel="shortcut icon" href="/faviconprode.ico?v=9" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="180x180" href="/faviconprode.png?v=9">
    <link rel="icon" type="image/png" sizes="32x32" href="/faviconprode.png?v=9">
    <link rel="icon" type="image/png" sizes="16x16" href="/faviconprode.png?v=9">
    <meta name="msapplication-TileColor" content="#11101A">
    <meta name="theme-color" content="#11101A">

    @vite(['resources/css/app.css', 'resources/js/homeusuarios.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a]">
    <div id="app"></div>
</body>
</html>
