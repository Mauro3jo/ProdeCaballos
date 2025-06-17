<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Login</title>
  <link rel="icon" href="{{ '/' . env('IMAGES_PUBLIC_PATH') . '/favicon.jpg' }}" type="image/jpeg">
  @vite(['resources/css/app.css', 'resources/js/login.js'])
</head>
<body>
  <div id="app"></div>
</body>
</html>
