<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SI RPL UPI — Portal Magang')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=lexend:500,600,700|plus-jakarta-sans:400,500,600,700" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: #F2F7FF; color: #0F172A; margin: 0; }
            h1, h2, h3 { font-family: 'Lexend', system-ui, sans-serif; }
        </style>
    @endif
</head>
<body class="min-h-screen bg-[#F2F7FF] text-slate-900 antialiased">
    @yield('content')
</body>
</html>
