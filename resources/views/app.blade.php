<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Wangdoodle') }}</title>

        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

        <!-- Styles -->
        <link rel="stylesheet" href="{{ mix('css/app.css') }}">

        <!-- Scripts -->
        @routes
        <script src="{{ mix('js/app.js') }}" defer></script>

        <script>
            window._asset = '{{ asset('') }}';
        </script>
    </head>
    <body class="font-sans antialiased">
    @if (\Session::has('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4" role="alert">
            <p class="font-bold">Woo!</p>
            <p>{!! \Session::get('success') !!}</p>
        </div>
    @elseif (\Session::has('error'))
        <div class="bg-orange-100 border-l-4 border-orange-500 text-orange-700 p-4" role="alert">
            <p class="font-bold">AAAAAHHHHHH, GET THE EXTINGUISHER! FIRE! FIRE! FIRE!</p>
            <p>{!! \Session::get('error') !!}</p>
        </div>
    @endif
        @inertia
    </body>
</html>
