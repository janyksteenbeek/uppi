<!doctype html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{{ $statusPage->name }} - Status Page</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <meta name="robots" content="noindex,nofollow">
    @livewireStyles
</head>
<body>
	<livewire:status-page.show :statusPage="$statusPage" />
	@livewireScripts
</body>
</html>
