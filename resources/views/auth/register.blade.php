<x-layouts.guest title="Daftar Akun Baru">
    <div class="mb-6">
        <h3 class="text-xl font-bold text-[var(--text)]">Daftar Karyawan Baru</h3>
        <p class="text-xs text-[var(--text-muted)] mt-1">Lengkapi data diri untuk mengakses portal monitoring</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-3.5">
        @csrf

        <div>
            <label for="name" class="block text-xs font-semibold text-[var(--text)] uppercase tracking-wider mb-1">Nama Lengkap</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-[var(--text)] placeholder-[var(--text-muted)] text-sm focus:outline-none focus:bg-[var(--surface)] focus:ring-2 focus:ring-blue-500/20 focus:border-[var(--primary)] transition-all"
                placeholder="cth: Ahmad Pratama">
            @error('name')
                <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="username" class="block text-xs font-semibold text-[var(--text)] uppercase tracking-wider mb-1">Username (Opsional, dibuat otomatis jika kosong)</label>
            <input id="username" type="text" name="username" value="{{ old('username') }}"
                class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-[var(--text)] placeholder-[var(--text-muted)] text-sm focus:outline-none focus:bg-[var(--surface)] focus:ring-2 focus:ring-blue-500/20 focus:border-[var(--primary)] transition-all"
                placeholder="cth: ahmad.pratama">
            @error('username')
                <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-2.5">
            <div>
                <label for="nip" class="block text-xs font-semibold text-[var(--text)] uppercase tracking-wider mb-1">NIP Pegawai</label>
                <input id="nip" type="text" name="nip" value="{{ old('nip') }}"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-[var(--text)] placeholder-[var(--text-muted)] text-sm focus:outline-none focus:bg-[var(--surface)] focus:ring-2 focus:ring-blue-500/20 focus:border-[var(--primary)] transition-all"
                    placeholder="cth: NIP104">
                @error('nip')
                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="department" class="block text-xs font-semibold text-[var(--text)] uppercase tracking-wider mb-1">Departemen / Divisi</label>
                <input id="department" type="text" name="department" value="{{ old('department') }}"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-[var(--text)] placeholder-[var(--text-muted)] text-sm focus:outline-none focus:bg-[var(--surface)] focus:ring-2 focus:ring-blue-500/20 focus:border-[var(--primary)] transition-all"
                    placeholder="cth: Service Delivery">
                @error('department')
                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="email" class="block text-xs font-semibold text-[var(--text)] uppercase tracking-wider mb-1">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-[var(--text)] placeholder-[var(--text-muted)] text-sm focus:outline-none focus:bg-[var(--surface)] focus:ring-2 focus:ring-blue-500/20 focus:border-[var(--primary)] transition-all"
                placeholder="nama@email.com">
            @error('email')
                <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-2.5">
            <div>
                <label for="password" class="block text-xs font-semibold text-[var(--text)] uppercase tracking-wider mb-1">Kata Sandi</label>
                <input id="password" type="password" name="password" required
                    class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-[var(--text)] placeholder-[var(--text-muted)] text-sm focus:outline-none focus:bg-[var(--surface)] focus:ring-2 focus:ring-blue-500/20 focus:border-[var(--primary)] transition-all"
                    placeholder="Minimal 8 karakter">
                @error('password')
                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-semibold text-[var(--text)] uppercase tracking-wider mb-1">Ulangi Sandi</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                    class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-[var(--text)] placeholder-[var(--text-muted)] text-sm focus:outline-none focus:bg-[var(--surface)] focus:ring-2 focus:ring-blue-500/20 focus:border-[var(--primary)] transition-all"
                    placeholder="••••••••">
            </div>
        </div>

        <div class="pt-3">
            <button type="submit"
                class="theme-btn-primary w-full py-2.5 px-4 rounded-xl text-white font-bold text-sm shadow-md transition-all">
                Daftar Akun
            </button>
        </div>
    </form>

    <p class="mt-5 text-center text-xs text-[var(--text-muted)]">
        Sudah memiliki akun?
        <a href="{{ route('login') }}" class="font-semibold text-[var(--primary)] hover:underline">Masuk disini</a>
    </p>
</x-layouts.guest>
