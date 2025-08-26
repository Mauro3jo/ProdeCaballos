<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Formularios de Usuarios</title>
    <link rel="icon" href="/faviconprode.ico?v=9" type="image/x-icon">
    <link rel="shortcut icon" href="/faviconprode.ico?v=9" type="image/x-icon">
    <script>
        window.prodeId = {{ $prodeId }};
        window.prodeNombre = @json($prodeNombre); // ✅ así pasa el nombre al Vue
    </script>
    @vite(['resources/css/app.css', 'resources/js/GestionFormulariosUsuariosModal.js'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
