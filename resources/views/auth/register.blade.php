<x-layouts.guest title="Daftar Akun Baru">
    <div class="mb-6">
        <h3 class="text-xl font-bold text-white">Daftar Karyawan Baru</h3>
        <p class="text-xs text-slate-400 mt-1">Lengkapi data diri untuk mengakses portal monitoring</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-3.5">
        @csrf

        <div>
            <label for="name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Nama Lengkap</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                class="w-full px-3.5 py-2 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                placeholder="cth: Ahmad Pratama">
            @error('name')
                <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-2.5">
            <div>
                <label for="nip" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">NIP Pegawai</label>
                <input id="nip" type="text" name="nip" value="{{ old('nip') }}"
                    class="w-full px-3.5 py-2 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                    placeholder="cth: NIP104">
                @error('nip')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="department" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Departemen / Divisi</label>
                <input id="department" type="text" name="department" value="{{ old('department') }}"
                    class="w-full px-3.5 py-2 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                    placeholder="cth: Service Delivery">
                @error('department')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Email Kantor</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                class="w-full px-3.5 py-2 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                placeholder="nama@icon.co.id">
            @error('email')
                <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-2.5">
            <div>
                <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Kata Sandi</label>
                <input id="password" type="password" name="password" required
                    class="w-full px-3.5 py-2 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                    placeholder="Minimal 8 karakter">
                @error('password')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Ulangi Sandi</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                    class="w-full px-3.5 py-2 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                    placeholder="••••••••">
            </div>
        </div>

        <div class="pt-3">
            <button type="submit"
                class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 text-white font-semibold text-sm shadow-lg shadow-blue-600/30 transition-all">
                Daftar Akun
            </button>
        </div>
    </form>

    <p class="mt-5 text-center text-xs text-slate-400">
        Sudah memiliki akun?
        <a href="{{ route('login') }}" class="font-semibold text-blue-400 hover:text-blue-300 hover:underline">Masuk disini</a>
    </p>
</x-layouts.guest>

