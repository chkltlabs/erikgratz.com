@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/sass/app.scss', 'resources/js/livewire.js'])
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-md">
    {{ $slot }}
</div>
</body>
</html>
