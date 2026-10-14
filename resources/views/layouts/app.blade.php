@php
    $pageTitle = $attributes->get('title', $title ?? config('app.name'));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="{{ $description ?? 'Convert raster images into optimized SVG files with optional AI background removal.' }}">

        <title>{{ $pageTitle }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="flex min-h-screen" x-data="{ mobileOpen: false }">
            @include('layouts.sidebar')

            <div class="flex-1 flex flex-col min-w-0">
                @include('layouts.topbar')

                @if (session('impersonate'))
                    <div class="bg-amber-500 text-white text-sm font-medium px-4 py-2 flex items-center justify-between">
                        <span>You are impersonating User #{{ session('impersonate') }}</span>
                        <form method="POST" action="{{ route('admin.users.stop-impersonating') }}">
                            @csrf
                            <button class="underline font-semibold">Stop</button>
                        </form>
                    </div>
                @endif

                @if (session('flash'))
                    <div class="mx-6 mt-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm animate-in" x-data="{ show: true }" x-show="show">
                        <div class="flex items-center justify-between gap-3">
                            <span>{{ session('flash') }}</span>
                            <button @click="show = false" class="text-emerald-500 hover:text-emerald-700" aria-label="Dismiss">&times;</button>
                        </div>
                    </div>
                @endif

                @isset($header)
                    <header class="border-b border-slate-200 bg-white/60 backdrop-blur px-6 lg:px-8 py-5">
                        <div class="max-w-6xl mx-auto">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="flex-1 px-6 lg:px-8 py-6">
                    <div class="max-w-6xl mx-auto">
                        {{ $slot }}
                    </div>
                </main>

                <footer class="border-t border-slate-200 px-6 py-4 text-center text-xs text-slate-400">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </footer>
            </div>
        </div>
    </body>
</html>