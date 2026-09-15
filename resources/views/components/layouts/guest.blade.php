<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Login' }} — ICON Monitoring Tugas</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full antialiased bg-gradient-to-tr from-slate-950 via-blue-950 to-blue-900 text-slate-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Background glowing accents -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-cyan-500/20 rounded-full blur-3xl pointer-events-none"></div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md relative z-10">
        <div class="flex justify-center mb-4">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-blue-600 to-cyan-400 flex items-center justify-center font-black text-3xl text-white shadow-xl shadow-blue-500/40">
                ⚡
            </div>
        </div>
        <h2 class="text-center text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
            PORTAL ICON
        </h2>
        <p class="mt-1 text-center text-xs sm:text-sm text-blue-200/80">
            Sistem Monitoring Tugas Karyawan (SSO Open, BAA, BAI, Exception, Kontrak Exp)
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md relative z-10 px-4 sm:px-0">
        <div class="bg-slate-900/85 backdrop-blur-md py-8 px-6 sm:px-10 shadow-2xl rounded-3xl border border-blue-800/40 text-slate-200">
            @if(session('success'))
            <div class="mb-5 p-3.5 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-xs font-medium">
                {{ session('success') }}
            </div>
            @endif

            {{ $slot }}
        </div>
    </div>
</body>
</html>

