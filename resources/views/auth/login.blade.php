<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk ke Portal — ICON Monitoring Tugas</title>

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
<body class="min-h-full font-sans antialiased bg-[var(--bg)] text-[var(--text)] flex flex-col justify-center py-10 sm:py-12 px-4 sm:px-6 relative selection:bg-blue-600 selection:text-white transition-colors duration-200">

    <!-- Toggle Dark Mode di Pojok Halaman Login (TAHAP 3) -->
    <div class="fixed top-4 right-4 z-50">
        <button type="button" onclick="toggleTheme()" id="themeToggleBtn"
            class="p-2.5 rounded-2xl bg-[var(--surface)] text-[var(--text)] border border-[var(--border)] shadow-xs hover:scale-105 transition-all cursor-pointer"
            title="Ganti Mode Terang / Gelap" aria-label="Toggle tema gelap/terang">
            <!-- Sun Icon (Tampil di Mode Gelap) -->
            <svg id="themeIconSun" class="w-4 h-4 text-amber-400 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            <!-- Moon Icon (Tampil di Mode Terang) -->
            <svg id="themeIconMoon" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
            </svg>
        </button>
    </div>

    <!-- Kontainer Utama: Lebar Maksimal ~400px & Responsif -->
    <div class="w-full max-w-[400px] mx-auto relative z-10">

        <!-- Bagian Atas / Header -->
        <div class="text-center mb-6">
            <!-- Kotak Kecil Logo Putih Membulat dengan Bayangan Tipis -->
            <div class="inline-flex items-center justify-center p-3 bg-[var(--surface)] rounded-2xl shadow-sm border border-[var(--border)] mb-3.5 transition-transform duration-200 hover:scale-105">
                <img src="{{ asset('images/logo-pln.png') }}" alt="Logo PLN Icon Plus" class="h-10 w-auto object-contain">
            </div>

            <!-- Judul PORTAL ICON -->
            <h1 class="text-2xl font-extrabold tracking-tight text-[#1e3a8a] dark:text-[#93c5fd] flex items-center justify-center gap-2">
                <span>PORTAL</span>
                <span class="bg-[#1d4ed8] text-white text-xs font-bold px-2.5 py-1 rounded-md tracking-wider">ICON</span>
            </h1>

            <!-- Badge Kecil Biru Muda -->
            <div class="mt-2.5 inline-flex items-center px-3 py-1 rounded-full bg-[var(--primary-soft)] text-[var(--primary)] text-[10.5px] font-bold tracking-wider uppercase border border-[var(--border)] shadow-xs">
                MONITORING TERPADU OPERASIONAL
            </div>

            <!-- Deskripsi Kecil Abu-abu -->
            <p class="mt-2 text-xs text-[var(--text-muted)] leading-relaxed max-w-sm mx-auto">
                Sistem Monitoring Tugas Karyawan: SO Open, BAA, BAI, Exception &amp; Kontrak Expired
            </p>
        </div>

        <!-- Kartu Login: Putih/Surface, Sudut Membulat Besar, Bayangan Lembut, Garis Tipis Biru di Atas -->
        <div class="bg-[var(--surface)] rounded-3xl shadow-xl shadow-blue-900/5 border border-[var(--border)] relative overflow-hidden p-6 sm:p-8 transition-colors duration-200">
            <!-- Garis Tipis Biru di Bagian Atas Kartu -->
            <div class="h-1 w-full bg-[var(--primary)] absolute top-0 left-0 right-0"></div>

            <!-- Header Kartu: Judul Masuk Akun dengan Ikon Perisai Kecil -->
            <div class="mb-5">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-6 h-6 rounded-lg bg-[var(--primary-soft)] flex items-center justify-center text-[var(--primary)] shrink-0 border border-[var(--border)]">
                        <!-- Ikon Perisai -->
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h2 class="text-base font-bold text-[var(--text)]">Masuk Akun</h2>
                </div>
                <p class="text-xs text-[var(--text-muted)] leading-relaxed">
                    Gunakan akun korporat PLN Icon Plus untuk mengakses dashboard monitoring tugas Anda.
                </p>
            </div>

            <!-- Pesan Notifikasi Sukses / Session -->
            @if(session('success'))
            <div class="mb-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-300 text-xs font-medium flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
            @endif

            @if(session('error'))
            <div class="mb-4 p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-700 dark:text-rose-300 text-xs font-medium flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ session('error') }}</span>
            </div>
            @endif

            <!-- Form Login -->
            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <!-- Field: Username Pegawai -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="username" class="text-[11px] font-bold text-[var(--text)] uppercase tracking-wider">
                            Username Pegawai
                        </label>
                        <span class="text-[9.5px] font-bold text-[var(--primary)] bg-[var(--primary-soft)] px-1.5 py-0.5 rounded border border-[var(--border)] tracking-wider">
                            ID ICON
                        </span>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[var(--text-muted)]">
                            <!-- Ikon User -->
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus
                            class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-[var(--surface-2)] text-[var(--text)] placeholder-[var(--text-muted)] border @error('username') border-rose-400 bg-rose-50/20 @else border-[var(--border)] @enderror text-sm focus:outline-none focus:bg-[var(--surface)] focus:ring-2 focus:ring-blue-500/20 focus:border-[var(--primary)] transition-all shadow-xs"
                            placeholder="cth: admin / karyawan.icon">
                    </div>
                    @error('username')
                        <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400 font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <!-- Field: Kata Sandi -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="text-[11px] font-bold text-[var(--text)] uppercase tracking-wider">
                            Kata Sandi
                        </label>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[var(--text-muted)]">
                            <!-- Ikon Gembok -->
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input id="password" type="password" name="password" required
                            class="w-full pl-10 pr-10 py-2.5 rounded-xl bg-[var(--surface-2)] text-[var(--text)] placeholder-[var(--text-muted)] border @error('password') border-rose-400 bg-rose-50/20 @else border-[var(--border)] @enderror text-sm focus:outline-none focus:bg-[var(--surface)] focus:ring-2 focus:ring-blue-500/20 focus:border-[var(--primary)] transition-all shadow-xs"
                            placeholder="••••••••">
                        <button type="button" onclick="togglePasswordVisibility()" aria-label="Lihat kata sandi"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-[var(--text-muted)] hover:text-[var(--text)] focus:outline-none transition-colors cursor-pointer">
                            <svg id="eyeIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg id="eyeOffIcon" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400 font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <!-- Baris: Ingat Saya & Lupa Password? -->
                <div class="flex items-center justify-between pt-0.5">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-[var(--border)] text-[var(--primary)] focus:ring-blue-500 focus:ring-offset-0">
                        <span class="text-xs text-[var(--text-muted)] font-medium">Ingat saya</span>
                    </label>
                    <a href="#" onclick="event.preventDefault();" class="text-xs font-bold text-[var(--primary)] hover:underline transition-colors">
                        Lupa Password?
                    </a>
                </div>

                <!-- Tombol Masuk ke Sistem -->
                <div class="pt-2">
                    <button type="submit"
                        class="theme-btn-primary w-full py-2.5 px-4 rounded-xl text-white font-bold text-sm shadow-md transition-all active:scale-[0.99] flex items-center justify-center gap-1.5 cursor-pointer">
                        <span>Masuk ke Sistem</span>
                        <span aria-hidden="true">&rarr;</span>
                    </button>
                </div>
            </form>

            <!-- Kotak Info Kecil Statis: Gateway Node -->
            <div class="mt-5 pt-3.5 border-t border-[var(--border)] flex items-center justify-center gap-2 text-[11px] text-[var(--text-muted)]">
                <svg class="w-3.5 h-3.5 text-[var(--text-muted)] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                </svg>
                <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                <span class="truncate">Gateway Node: Terhubung Normal / Sistem Task Dispatcher v4.12.0</span>
            </div>
        </div>

        <!-- Bagian Bawah Kartu: Pill Helpdesk IT & Footer -->
        <div class="mt-6 text-center space-y-3">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-[var(--surface)] text-[var(--text-muted)] text-[11px] font-medium border border-[var(--border)] shadow-xs">
                    <svg class="w-3.5 h-3.5 text-[var(--primary)] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Butuh otorisasi KP? Hubungi Helpdesk IT PLN</span>
                </span>
            </div>
            <div class="text-[11px] text-[var(--text-muted)] space-y-0.5">
                <p>&copy; 2026 PT PLN Icon Plus. All rights reserved.</p>
                <p class="font-semibold tracking-wider text-[10px] uppercase opacity-80">SUBHOLDING BEYOND KWH PLN GROUP</p>
            </div>
        </div>

    </div>

    <!-- Script Show/Hide Password & Theme Switcher -->
    <script>
        function togglePasswordVisibility() {
            const input = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            const eyeOffIcon = document.getElementById('eyeOffIcon');
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                eyeIcon?.classList.add('hidden');
                eyeOffIcon?.classList.remove('hidden');
            } else {
                input.type = 'password';
                eyeIcon?.classList.remove('hidden');
                eyeOffIcon?.classList.add('hidden');
            }
        }

        function updateThemeIcons() {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const sun = document.getElementById('themeIconSun');
            const moon = document.getElementById('themeIconMoon');
            if (sun && moon) {
                if (isDark) {
                    sun.classList.remove('hidden');
                    moon.classList.add('hidden');
                } else {
                    sun.classList.add('hidden');
                    moon.classList.remove('hidden');
                }
            }
        }

        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-theme') || 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
            updateThemeIcons();
            window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: next } }));
        }

        document.addEventListener('DOMContentLoaded', updateThemeIcons);
    </script>
</body>
</html>
