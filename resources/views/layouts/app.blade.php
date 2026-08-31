<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <style>
            [x-cloak] { display: none !important; }
        </style>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-800 bg-[#F7F5F0]">
        <div x-data="{ sidebarOpen: false }" class="relative min-h-screen bg-[#F7F5F0]">

            <!-- Navigation -->
            @include('layouts.navigation')

            <!-- Main Content: Tambahkan pt-24 agar banner tidak tertutup topbar -->
            <main class="w-full pt-28 pb-12">
                {{ $slot }}
            </main>

        </div>
    </body>
</html>
