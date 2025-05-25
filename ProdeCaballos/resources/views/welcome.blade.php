<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prode de Caballos</title>
    @vite(['resources/css/app.css', 'resources/js/homeusuarios.js']) <!-- solo la entrada que corresponde a esta vista -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a]">
    <div id="app"></div>
</body>
</html>
