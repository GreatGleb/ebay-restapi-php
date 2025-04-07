<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Laravel App')</title>

    <!-- Ваши стили -->
    @stack('styles')

    <!-- CSRF-токен для AJAX -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>

<!-- Основное содержимое -->
<div class="container">
    @yield('content')
</div>

<!-- Подвал -->
<footer class="mt-5 text-center">
    <p>© {{ date('Y') }} Sugak Gleb, gwelbts@gmail.com</p>
</footer>

<!-- Ваши скрипты -->
@stack('scripts') <!-- Для подключения JS из дочерних шаблонов -->
</body>
</html>
