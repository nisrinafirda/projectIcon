<x-layouts.app>
    <x-slot:title>Verifikasi Tugas Masuk — Administrator</x-slot:title>
    <x-slot:pageHeading>Antrean Verifikasi Tugas Masuk</x-slot:pageHeading>

    <div class="space-y-6">
        <!-- Header Banner -->
        <div class="rounded-2xl bg-gradient-to-r from-blue-950 via-indigo-950 to-slate-900 p-6 sm:p-7 text-white shadow-lg shadow-blue-950/20">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-500/20 text-blue-300 border border-blue-400/30 mb-2">
                        📥 Persetujuan & Tinjauan Administrator
                    </span>
                    <h2 class="text-2xl font-black tracking-tight text-white flex items-center gap-3">
                        <span>Antrean Verifikasi Tugas Masuk</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-600 text-white shadow-xs">
                            {{ $pendingTasks->total() }} Menunggu
                        </span>
                    </h2>
                    <p class="mt-1 text-xs sm:text-sm text-blue-200/90 max-w-2xl">
                        Karyawan telah menyerahkan tugas-tugas di bawah ini. Tinjau catatan hasil pengerjaan, periksa lampiran dokumen, dan tentukan untuk menyetujui (Approve) atau meminta revisi (Reject).
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('tasks.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-semibold backdrop-blur-xs border border-white/10 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Ke Semua Tugas
                    </a>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <form method="GET" action="{{ route('admin.verifikasi') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Search -->
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Cari judul, nomor dokumen, customer..."
                        class="w-full pl-9 pr-4 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>

                <!-- Kategori Filter -->
                <div>
                    <select name="category" class="w-full py-2 px-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        <option value="">Semua Modul Tugas</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- KP Filter -->
                <div>
                    <select name="kp" class="w-full py-2 px-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        <option value="">Semua Kantor Perwakilan (KP)</option>
                        @foreach($kpList as $kpKey => $kpLabel)
                            <option value="{{ $kpKey }}" {{ request('kp') === $kpKey ? 'selected' : '' }}>
                                KP {{ $kpLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2">
                    <button type="submit" class="flex-1 py-2 px-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition-colors cursor-pointer">
                        Filter
                    </button>
                    @if(request()->hasAny(['search', 'category', 'kp']))
                    <a href="{{ route('admin.verifikasi') }}" class="py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold text-xs transition-colors">
                        Reset
                    </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-[11px] border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-4">Kategori & KP</th>
                            <th class="py-3.5 px-4">ID PA</th>
                            <th class="py-3.5 px-4">Judul & Pelanggan</th>
                            <th class="py-3.5 px-4">Karyawan PIC</th>
                            <th class="py-3.5 px-4">Deadline</th>
                            <th class="py-3.5 px-4">Komentar / Catatan</th>
                            <th class="py-3.5 px-4 text-center">Tindakan Admin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($pendingTasks as $task)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="flex flex-col gap-1 items-start">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800">
                                        {{ $task->category_label }}
                                    </span>
                                    @if($task->kp)
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        KP {{ $task->kp_label }}
                                    </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                {{ $task->document_number }}
                            </td>
                            <td class="py-3.5 px-4 max-w-xs">
                                <a href="{{ route('tasks.show', $task) }}" class="font-bold text-slate-900 hover:text-blue-600 truncate block">
                                    {{ $task->title }}
                                </a>
                                @if($task->customer_name)
                                <p class="text-[11px] text-slate-400 mt-0.5 truncate">
                                    🏢 {{ $task->customer_name }}
                                </p>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-medium text-slate-800">
                                @if($task->user)
                                    <span>{{ $task->user->name }}</span>
                                    @if($task->user->nip)
                                        <span class="block text-[10px] text-slate-400">{{ $task->user->nip }}</span>
                                    @endif
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold {{ $task->is_overdue ? 'text-rose-600' : ($task->is_due_soon ? 'text-amber-600' : 'text-slate-700') }}">
                                    {{ $task->due_date ? $task->due_date->format('d/m/Y') : '-' }}
                                </span>
                                @if($task->is_due_soon)
                                    <span class="block text-[10px] font-bold text-amber-600">Besok / Segera</span>
                                @elseif($task->is_overdue)
                                    <span class="block text-[10px] font-bold text-rose-600">Terlambat</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 max-w-xs">
                                <p class="text-slate-700 italic text-[11px] truncate" title="{{ $task->comments ?: 'Tidak ada komentar' }}">
                                    Komentar: "{{ $task->comments ?: '-' }}"
                                </p>
                                <p class="text-slate-700 italic text-[11px] truncate mt-1" title="{{ $task->submission_notes ?: 'Tidak ada catatan' }}">
                                    Catatan: "{{ $task->submission_notes ?: '-' }}"
                                </p>
                                @if($task->submission_attachment)
                                <a href="{{ asset('storage/' . $task->submission_attachment) }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-blue-600 hover:underline mt-1 font-medium">
                                    📎 Lampiran Dokumen
                                </a>
                                @endif
                                <p class="text-slate-400 text-[10px] mt-1">Diserahkan: {{ $task->completed_at ? $task->completed_at->translatedFormat('d M Y, H:i') : '-' }}</p>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <form method="POST" action="{{ route('tasks.toggle-complete', $task) }}" class="inline-block">
                                        @csrf
                                        <input type="hidden" name="admin_notes" value="Disetujui cepat (Quick Approve) dari halaman verifikasi.">
                                        <label class="relative inline-flex items-center justify-center cursor-pointer p-1 rounded-lg hover:bg-slate-100 transition-colors" title="Setujui Tugas ini (Quick Approve)">
                                            <input type="checkbox" name="completed" value="1" onchange="this.form.submit()" class="w-5 h-5 text-emerald-600 bg-white border-2 border-slate-300 rounded-md focus:ring-emerald-500 focus:ring-2 cursor-pointer transition-all">
                                        </label>
                                    </form>
                                    <a href="{{ route('tasks.show', $task) }}" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold text-[10px] shadow-xs transition-all w-full">
                                        <span>Tinjau Detail</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="max-w-sm mx-auto">
                                    <div class="text-3xl mb-2">✨</div>
                                    <p class="font-semibold text-slate-700">Tidak ada tugas yang menunggu verifikasi</p>
                                    <p class="text-xs text-slate-400 mt-1">Semua kiriman tugas dari karyawan telah ditinjau dan disetujui!</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($pendingTasks->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $pendingTasks->links() }}
            </div>
            @endif
        </div>
    </div>
</x-layouts.app>

