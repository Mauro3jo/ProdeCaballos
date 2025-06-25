<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Login</title>
  <link rel="icon" href="/faviconprode.ico?v=9" type="image/x-icon">
  <link rel="shortcut icon" href="/faviconprode.ico?v=9" type="image/x-icon">
  @vite(['resources/css/app.css', 'resources/js/login.js'])
</head>
<body>
  <div id="app"></div>
</body>
</html>
