<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ICON Portal' }} — Monitoring Tugas Karyawan</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full text-slate-800 antialiased flex">

    <!-- Mobile Sidebar Backdrop -->
    <div id="sidebar-backdrop" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>

    <!-- Sidebar (Theme: Vibrant ICON Blue) -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-72 bg-gradient-to-b from-blue-950 via-slate-900 to-blue-950 text-white flex flex-col transition-transform duration-300 -translate-x-full lg:translate-x-0 lg:static lg:inset-auto">
        <!-- Brand Header -->
        <div class="p-5 flex items-center justify-between border-b border-blue-900/60">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-cyan-400 flex items-center justify-center font-black text-xl shadow-lg shadow-blue-500/30 group-hover:scale-105 transition-transform">
                    ⚡
                </div>
                <div>
                    <div class="flex items-center gap-1.5">
                        <span class="font-extrabold text-xl tracking-tight text-white">ICON</span>
                        <span class="text-xs px-1.5 py-0.5 rounded-md bg-blue-500/20 text-blue-300 font-semibold border border-blue-500/30">PORTAL</span>
                    </div>
                    <p class="text-[11px] text-blue-300/70 font-medium">Task Monitoring System</p>
                </div>
            </a>
            <button type="button" class="lg:hidden text-slate-400 hover:text-white" onclick="toggleSidebar()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Navigation Links -->
        <div class="flex-1 overflow-y-auto px-4 py-4 space-y-6">
            <!-- Main Menu -->
            <div>
                <p class="px-3 text-[11px] font-semibold text-blue-400/80 uppercase tracking-wider mb-2">Utama</p>
                <nav class="space-y-1">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-semibold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        Dashboard
                    </a>
                    @if(auth()->user()->isAdmin())
                    @php
                        $pendingVerifCount = \App\Models\Task::where('status', \App\Models\Task::STATUS_SUBMITTED)->count();
                    @endphp
                    <a href="{{ route('admin.verifikasi') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.verifikasi') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-semibold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 opacity-80 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>Verifikasi</span>
                        </div>
                        @if($pendingVerifCount > 0)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-purple-500/30 text-purple-200 font-bold border border-purple-400/30">
                            {{ $pendingVerifCount }}
                        </span>
                        @endif
                    </a>
                    @endif
                    <a href="{{ route('tasks.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('tasks.index') && !request()->route('category') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-semibold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        Semua Tugas
                    </a>
                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.users.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-semibold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        Kelola Karyawan
                    </a>
                    @endif
                </nav>
            </div>

            <!-- 5 Kategori ICON -->
            <div>
                <div class="px-3 flex items-center justify-between mb-2">
                    <p class="text-[11px] font-semibold text-blue-400/80 uppercase tracking-wider">Kategori Tugas</p>
                    <span class="text-[10px] bg-blue-900/80 text-blue-300 px-1.5 py-0.5 rounded font-bold">5 FITUR</span>
                </div>
                <nav class="space-y-1">
                    <!-- SO Open -->
                    <a href="{{ route('tasks.category', 'sso_open') }}" class="flex items-center justify-between px-3.5 py-2 rounded-xl text-sm font-medium transition-all {{ request()->is('tasks/category/sso_open') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <div class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                            <span>SO Open</span>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-900/60 text-blue-200">
                            {{ \App\Models\Task::where('category', 'sso_open')->when(!auth()->user()->isAdmin(), fn($q) => $q->where('user_id', auth()->id()))->count() }}
                        </span>
                    </a>

                    <!-- BAA -->
                    <a href="{{ route('tasks.category', 'baa') }}" class="flex items-center justify-between px-3.5 py-2 rounded-xl text-sm font-medium transition-all {{ request()->is('tasks/category/baa') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <div class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                            <span>BAA</span>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-900/60 text-blue-200">
                            {{ \App\Models\Task::where('category', 'baa')->when(!auth()->user()->isAdmin(), fn($q) => $q->where('user_id', auth()->id()))->count() }}
                        </span>
                    </a>

                    <!-- BAI -->
                    <a href="{{ route('tasks.category', 'bai') }}" class="flex items-center justify-between px-3.5 py-2 rounded-xl text-sm font-medium transition-all {{ request()->is('tasks/category/bai') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <div class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            <span>BAI</span>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-900/60 text-blue-200">
                            {{ \App\Models\Task::where('category', 'bai')->when(!auth()->user()->isAdmin(), fn($q) => $q->where('user_id', auth()->id()))->count() }}
                        </span>
                    </a>

                    <!-- EXCEPTION -->
                    <a href="{{ route('tasks.category', 'exception') }}" class="flex items-center justify-between px-3.5 py-2 rounded-xl text-sm font-medium transition-all {{ request()->is('tasks/category/exception') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <div class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                            <span>EXCEPTION</span>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-900/60 text-blue-200">
                            {{ \App\Models\Task::where('category', 'exception')->when(!auth()->user()->isAdmin(), fn($q) => $q->where('user_id', auth()->id()))->count() }}
                        </span>
                    </a>

                    <!-- KONTRAK EXP -->
                    <a href="{{ route('tasks.category', 'kontrak_exp') }}" class="flex items-center justify-between px-3.5 py-2 rounded-xl text-sm font-medium transition-all {{ request()->is('tasks/category/kontrak_exp') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <div class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full bg-purple-400"></span>
                            <span>Kontrak Exp</span>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-900/60 text-blue-200">
                            {{ \App\Models\Task::where('category', 'kontrak_exp')->when(!auth()->user()->isAdmin(), fn($q) => $q->where('user_id', auth()->id()))->count() }}
                        </span>
                    </a>
                </nav>
            </div>

            <!-- Admin Menu -->
            @if(auth()->user()->isAdmin())
            <div>
                <p class="px-3 text-[11px] font-semibold text-blue-400/80 uppercase tracking-wider mb-2">Manajemen Admin</p>
                <nav class="space-y-1">
                    <a href="{{ route('tasks.create') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('tasks.create') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Beri Tugas Baru
                    </a>
                    <a href="{{ route('tasks.import.view') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('tasks.import.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Import Data & Excel
                    </a>
                </nav>
            </div>
            @endif

            <!-- Quick Export -->
            <div>
                <p class="px-3 text-[11px] font-semibold text-blue-400/80 uppercase tracking-wider mb-2">Laporan & Ekspor</p>
                <div class="grid grid-cols-2 gap-2 px-1">
                    <a href="{{ route('tasks.export.excel') }}" class="flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-300 hover:text-emerald-200 text-xs font-semibold border border-emerald-500/30 transition-all shadow-xs">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span>Excel</span>
                    </a>
                    <a href="{{ route('tasks.export.pdf') }}" class="flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl bg-rose-500/15 hover:bg-rose-500/25 text-rose-300 hover:text-rose-200 text-xs font-semibold border border-rose-500/30 transition-all shadow-xs">
                        <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        <span>PDF</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Sidebar User Profile Footer -->
        <div class="p-4 border-t border-blue-900/60 bg-blue-950/80">
            <div class="flex items-center justify-between">
                <a href="{{ route('password.edit') }}" class="flex items-center gap-3 overflow-hidden group hover:opacity-90 transition-opacity" title="Klik untuk ganti kata sandi">
                    <div class="w-9 h-9 rounded-full bg-blue-700 border-2 border-blue-400/50 flex items-center justify-center font-bold text-white shadow group-hover:border-white transition-colors shrink-0">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                        <span class="inline-flex items-center px-1.5 py-0.2 text-[10px] font-bold rounded {{ auth()->user()->isAdmin() ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30' }}">
                            {{ auth()->user()->isAdmin() ? 'ADMIN' : 'KARYAWAN' }}
                        </span>
                    </div>
                </a>

                <div class="flex items-center gap-1 shrink-0">
                    <a href="{{ route('password.edit') }}" title="Ganti Kata Sandi" class="p-2 text-slate-400 hover:text-amber-300 hover:bg-white/5 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Keluar" class="p-2 text-slate-400 hover:text-rose-400 hover:bg-white/5 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <!-- Top Navbar -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
            <div class="px-4 sm:px-6 lg:px-8 py-3.5 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button type="button" class="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100" onclick="toggleSidebar()">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <div>
                        <h1 class="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2">
                            {{ $pageHeading ?? 'Portal Monitoring ICON' }}
                        </h1>
                    </div>
                </div>

                <!-- Right Header Actions -->
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex items-center gap-2 text-xs font-medium text-slate-500 bg-slate-100 px-3 py-1.5 rounded-full border border-slate-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>{{ now()->translatedFormat('l, d F Y') }}</span>
                    </div>

                    <a href="{{ route('password.edit') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 text-slate-600 hover:text-blue-600 hover:border-blue-300 hover:bg-blue-50 text-xs font-semibold transition-all" title="Ganti Kata Sandi">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                        <span class="hidden md:inline">Ganti Password</span>
                    </a>

                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('tasks.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm shadow-blue-500/20 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span>Tugas Baru</span>
                    </a>
                    @endif
                </div>
            </div>
        </header>

        <!-- Flash Messages -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6">
            @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-start gap-3 shadow-xs">
                <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div class="flex-1 text-sm font-medium">
                    {{ session('success') }}
                </div>
            </div>
            @endif

            @if(session('import_warnings') && count(session('import_warnings')) > 0)
            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200/80 text-amber-950 flex items-start gap-3 shadow-xs">
                <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 text-base font-bold">
                    ⚠️
                </div>
                <div class="flex-1 text-xs">
                    <h4 class="font-bold text-amber-900 text-sm mb-1">
                        Perlu Konfirmasi Penugasan Karyawan ({{ count(session('import_warnings')) }} Baris)
                    </h4>
                    <p class="text-amber-800/90 mb-2">
                        Beberapa nama PIC/Sales di file Excel memiliki kemiripan 70%–89% dengan karyawan terdaftar sehingga tidak di-assign otomatis demi keamanan data:
                    </p>
                    <ul class="space-y-1.5 list-disc list-inside bg-white/70 p-3 rounded-xl border border-amber-200/60 font-mono text-[11px] text-amber-900">
                        @foreach(session('import_warnings') as $warn)
                            <li>{{ $warn }}</li>
                        @endforeach
                    </ul>
                    <div class="mt-2.5 flex items-center gap-3">
                        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1 font-bold text-amber-900 underline hover:text-amber-950">
                            Buka Kelola Karyawan &rarr;
                        </a>
                    </div>
                </div>
            </div>
            @endif

            @if($errors->any())
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-start gap-3 shadow-xs">
                <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div class="flex-1 text-sm">
                    <p class="font-semibold mb-1">Terdapat beberapa catatan:</p>
                    <ul class="list-disc list-inside space-y-0.5 text-xs text-rose-700">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            {{ $slot }}
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            sidebar.classList.toggle('-translate-x-full');
            backdrop.classList.toggle('hidden');
        }
    </script>
    @stack('scripts')
</body>
</html>

