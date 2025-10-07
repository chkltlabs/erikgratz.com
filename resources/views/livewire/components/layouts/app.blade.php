<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        @vite(['resources/css/app.css','resources/sass/app.scss', 'resources/js/app.js'])
        @livewireStyles
        <title>{{ 'Erik Gratz' }}</title>
    </head>
    <body class="bg-gray-900">
        <livewire:components.header :page-title="$pageTitle">

            {{$slot}}
        </livewire:components.header>
        @livewireScripts
    </body>
</html>

{{--<!doctype html>--}}
{{--<html lang="{{ str_replace('_','-',app()->getLocale()) }}">--}}
{{--<head>--}}
{{--    <meta charset="utf-8" />--}}
{{--    <meta name="viewport" content="width=device-width,initial-scale=1" />--}}
{{--    <title>{{ $title ?? 'Erik Gratz' }}</title>--}}

{{--    --}}{{-- Tailwind entry (Vite) --}}
{{--    @vite(['resources/css/app.css', 'resources/js/app.js'])--}}

{{--    @livewireStyles--}}
{{--</head>--}}
{{--<body class="bg-slate-50 text-slate-900 antialiased">--}}
{{-- Mount Livewire page --}}
{{--{{ $slot ?? '' }}--}}

{{--@livewireScripts--}}
{{--</body>--}}
{{--</html>--}}

