<!doctype html>
<html x-data="{ darkMode: localStorage.getItem('monitoringWallDarkMode') === 'true' }"
      :class="{ 'dark': darkMode }"
      class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>monitoring wall</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
    </style>
    @livewireStyles
</head>
<body class="h-full overflow-hidden bg-neutral-100 dark:bg-neutral-950 transition-colors duration-300">
    {{ $slot }}
    @livewireScripts
</body>
</html>
