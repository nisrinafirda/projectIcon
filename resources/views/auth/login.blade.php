<x-layouts.guest title="Masuk ke Portal">
    <div class="mb-6">
        <h3 class="text-xl font-bold text-white">Masuk Akun</h3>
        <p class="text-xs text-slate-400 mt-1">Silakan masukkan email dan kata sandi Anda</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                placeholder="nama@email.com">
            @error('email')
                <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Kata Sandi</label>
            </div>
            <input id="password" type="password" name="password" required
                class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                placeholder="••••••••">
            @error('password')
                <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between pt-1">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-slate-800 border-slate-700 text-blue-600 focus:ring-blue-500">
                <span class="text-xs text-slate-300">Ingat saya</span>
            </label>
        </div>

        <div class="pt-2">
            <button type="submit"
                class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 text-white font-semibold text-sm shadow-lg shadow-blue-600/30 transition-all active:scale-98">
                Masuk ke Sistem
            </button>
        </div>
    </form>

    <p class="mt-6 text-center text-xs text-slate-400">
        Belum punya akun?
        <a href="{{ route('register') }}" class="font-semibold text-blue-400 hover:text-blue-300 hover:underline">Daftar sekarang</a>
    </p>
</x-layouts.guest>

