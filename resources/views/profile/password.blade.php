<x-layouts.app>
    <x-slot:title>Ganti Kata Sandi — ICON</x-slot:title>
    <x-slot:pageHeading>Pengaturan Keamanan Akun</x-slot:pageHeading>

    <div class="max-w-2xl mx-auto space-y-6">
        <!-- Account Info Banner -->
        <div class="bg-gradient-to-r from-blue-900 to-indigo-900 rounded-2xl p-6 text-white shadow-md flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 flex items-center justify-center text-xl font-bold">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div>
                    <h2 class="text-base font-bold">{{ auth()->user()->name }}</h2>
                    <p class="text-xs text-blue-200 font-mono mt-0.5">
                        Username: <span class="font-bold text-white">@{{ auth()->user()->username ?: '-' }}</span>
                        {{ auth()->user()->nip ? ' • NIP: '.auth()->user()->nip : '' }}
                    </p>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider {{ auth()->user()->isAdmin() ? 'bg-amber-400/20 text-amber-300 border border-amber-400/30' : 'bg-cyan-400/20 text-cyan-300 border border-cyan-400/30' }}">
                {{ auth()->user()->isAdmin() ? 'Administrator' : 'Karyawan' }}
            </span>
        </div>

        <!-- Change Password Form Card -->
        <div class="theme-card border border-[var(--border)] shadow-xs p-6 sm:p-8">
            <div class="mb-6 pb-4 border-b border-[var(--border)]">
                <h3 class="text-base font-bold text-[var(--text)] flex items-center gap-2">
                    <svg class="w-5 h-5 text-[var(--primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Ganti Kata Sandi
                </h3>
                <p class="text-xs text-[var(--text-muted)] mt-1">Perbarui kata sandi akun Anda secara berkala untuk menjaga keamanan data.</p>
            </div>

            <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="block text-xs font-bold text-[var(--text)] uppercase tracking-wider mb-1.5">
                        Kata Sandi Saat Ini
                    </label>
                    <input type="password" id="current_password" name="current_password" required
                        class="w-full px-4 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-sm text-[var(--text)] placeholder-[var(--text-muted)] focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-[var(--primary)] focus:bg-[var(--surface)] transition-all"
                        placeholder="Masukkan kata sandi lama Anda">
                    @error('current_password')
                        <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-xs font-bold text-[var(--text)] uppercase tracking-wider mb-1.5">
                            Kata Sandi Baru
                        </label>
                        <input type="password" id="password" name="password" required minlength="8"
                            class="w-full px-4 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-sm text-[var(--text)] placeholder-[var(--text-muted)] focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-[var(--primary)] focus:bg-[var(--surface)] transition-all"
                            placeholder="Minimal 8 karakter">
                        @error('password')
                            <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold text-[var(--text)] uppercase tracking-wider mb-1.5">
                            Konfirmasi Kata Sandi Baru
                        </label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8"
                            class="w-full px-4 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-sm text-[var(--text)] placeholder-[var(--text-muted)] focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-[var(--primary)] focus:bg-[var(--surface)] transition-all"
                            placeholder="Ketik ulang kata sandi baru">
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-between border-t border-[var(--border)]">
                    <a href="{{ route('dashboard') }}" class="px-4 py-2.5 rounded-xl text-[var(--text-muted)] hover:text-[var(--text)] hover:bg-[var(--surface-2)] text-xs font-semibold transition-colors">
                        Kembali ke Dashboard
                    </a>
                    <button type="submit"
                        class="theme-btn-primary px-6 py-2.5 text-xs">
                        Simpan Kata Sandi Baru
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
