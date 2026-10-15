<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Login' }} — ICON Monitoring Tugas</title>

    <!-- Anti-flicker Theme Script: Set data-theme SEBELUM DOM dirender -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                document.documentElement.setAttribute('data-theme', savedTheme);
            } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full font-sans antialiased bg-[var(--bg)] text-[var(--text)] flex flex-col justify-center py-10 sm:py-12 sm:px-6 lg:px-8 relative overflow-x-hidden selection:bg-blue-600 selection:text-white transition-colors duration-200">

    <!-- Header Section: Logo & Judul -->
    <div class="sm:mx-auto sm:w-full sm:max-w-md relative z-10 px-4 sm:px-0 text-center">
        <!-- Logo PLN Icon Plus di dalam Kotak Membulat -->
        <div class="inline-flex items-center justify-center p-3 bg-[var(--surface)] rounded-2xl shadow-sm border border-[var(--border)] mb-3 transition-transform duration-200 hover:scale-105">
            <img src="{{ asset('images/logo-pln.png') }}" alt="Logo PLN Icon Plus" class="h-10 sm:h-12 w-auto object-contain">
        </div>

        <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-[#1e3a8a] dark:text-[#93c5fd]">
            PORTAL ICON
        </h2>
        <p class="mt-1 text-xs text-[var(--text-muted)] max-w-xs sm:max-w-sm mx-auto leading-relaxed">
            Sistem Monitoring Tugas Karyawan (SSO Open, BAA, BAI, Exception, Kontrak Exp)
        </p>
    </div>

    <!-- Kartu Form -->
    <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-md relative z-10 px-4 sm:px-0">
        <div class="relative bg-[var(--surface)] py-8 px-6 sm:px-10 shadow-xl shadow-blue-900/5 rounded-3xl border border-[var(--border)] text-[var(--text)] overflow-hidden transition-colors duration-200">
            <!-- Garis Tipis Biru di Atas Kartu -->
            <div class="h-1 w-full bg-[var(--primary)] absolute top-0 left-0 right-0"></div>

            @if(session('success'))
            <div class="mb-5 p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-300 text-xs font-medium flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
            @endif

            {{ $slot }}
        </div>

        <!-- Footer Copyright -->
        <p class="mt-6 text-center text-xs text-[var(--text-muted)] font-medium">
            &copy; {{ date('Y') }} PT PLN Icon Plus. All rights reserved.
        </p>
    </div>
</body>
</html>
