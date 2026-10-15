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
        <div class="theme-card p-4 border border-[var(--border)] shadow-xs">
            <form method="GET" action="{{ route('admin.verifikasi') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Search -->
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Cari judul, nomor dokumen, customer..."
                        class="w-full pl-9 pr-4 py-2 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-xs text-[var(--text)] placeholder-[var(--text-muted)] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-[var(--surface)] transition-all">
                    <svg class="w-4 h-4 text-[var(--text-muted)] absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>

                <!-- Kategori Filter -->
                <div>
                    <select name="category" class="w-full py-2 px-3 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-xs text-[var(--text)] focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
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
                    <select name="kp" class="w-full py-2 px-3 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-xs text-[var(--text)] focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
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
                    <button type="submit" class="theme-btn-primary flex-1 py-2 px-3 text-xs font-semibold cursor-pointer">
                        Filter
                    </button>
                    @if(request()->hasAny(['search', 'category', 'kp']))
                    <a href="{{ route('admin.verifikasi') }}" class="py-2 px-3 rounded-xl bg-[var(--surface-2)] hover:bg-[var(--surface)] text-[var(--text-muted)] border border-[var(--border)] font-semibold text-xs transition-colors">
                        Reset
                    </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="theme-card border border-[var(--border)] shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-[var(--text)]">
                    <thead class="bg-[var(--surface-2)] text-[var(--text-muted)] font-semibold uppercase tracking-wider text-[11px] border-b border-[var(--border)]">
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
                    <tbody class="divide-y divide-[var(--border)]">
                        @forelse($pendingTasks as $task)
                        <tr class="hover:bg-[var(--surface-2)] transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="flex flex-col gap-1 items-start">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                        {{ $task->category_label }}
                                    </span>
                                    @if($task->kp)
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-semibold bg-[var(--surface-2)] text-[var(--text-muted)] border border-[var(--border)]">
                                        KP {{ $task->kp_label }}
                                    </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-xs">
                                <div class="font-bold text-[var(--text)]">{{ $task->document_number ?: '-' }}</div>
                                @if($task->mutation)
                                <div class="mt-1">
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold bg-[var(--surface-2)] text-[var(--text)] border border-[var(--border)] font-sans tracking-normal">
                                        {{ $task->mutation->nama }}
                                    </span>
                                </div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 max-w-xs">
                                <a href="{{ route('tasks.show', $task) }}" class="font-bold text-[var(--text)] hover:text-[var(--primary)] truncate block">
                                    {{ $task->title }}
                                </a>
                                @if($task->customer_name)
                                <p class="text-[11px] text-[var(--text-muted)] mt-0.5 truncate">
                                    🏢 {{ $task->customer_name }}
                                </p>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-medium text-[var(--text)]">
                                @if($task->user)
                                    <span>{{ $task->user->name }}</span>
                                    @if($task->user->nip)
                                        <span class="block text-[10px] text-[var(--text-muted)]">{{ $task->user->nip }}</span>
                                    @endif
                                @else
                                    <span class="text-[var(--text-muted)]">-</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold {{ $task->is_overdue ? 'text-rose-500' : ($task->is_due_soon ? 'text-amber-500' : 'text-[var(--text)]') }}">
                                    {{ $task->due_date ? $task->due_date->format('d/m/Y') : '-' }}
                                </span>
                                @if($task->is_due_soon)
                                    <span class="block text-[10px] font-bold text-amber-500">Besok / Segera</span>
                                @elseif($task->is_overdue)
                                    <span class="block text-[10px] font-bold text-rose-500">Terlambat</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 max-w-xs">
                                @if($task->late_reason)
                                <p class="text-rose-500 font-semibold text-[11px] truncate mb-1" title="{{ $task->late_reason }}">
                                    🚨 Alasan Terlambat: "{{ $task->late_reason }}"
                                </p>
                                @endif
                                <p class="text-[var(--text)] italic text-[11px] truncate" title="{{ $task->comments ?: 'Tidak ada komentar' }}">
                                    Komentar: "{{ $task->comments ?: '-' }}"
                                </p>
                                <p class="text-[var(--text)] italic text-[11px] truncate mt-1" title="{{ $task->submission_notes ?: 'Tidak ada catatan' }}">
                                    Catatan: "{{ $task->submission_notes ?: '-' }}"
                                </p>
                                @if($task->submission_attachment)
                                <a href="{{ asset('storage/' . $task->submission_attachment) }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-[var(--primary)] hover:underline mt-1 font-medium">
                                    📎 Lampiran Dokumen
                                </a>
                                @endif
                                <p class="text-[var(--text-muted)] text-[10px] mt-1">Diserahkan: {{ $task->completed_at ? $task->completed_at->translatedFormat('d M Y, H:i') : '-' }}</p>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex flex-col items-center gap-1.5 min-w-[120px]">
                                    <!-- Konfirmasi (Approve) -->
                                    <form method="POST" action="{{ route('tasks.review', $task) }}" class="w-full">
                                        @csrf
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">
                                        <button type="submit" class="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition-colors cursor-pointer">
                                            <span>✓ Konfirmasi</span>
                                        </button>
                                    </form>

                                    <!-- Kembalikan (Reject / Revisi) -->
                                    <button type="button" onclick="openRejectDialog({{ $task->id }})" class="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-lg bg-rose-500/15 hover:bg-rose-500 text-rose-600 hover:text-white font-bold text-xs border border-rose-500/30 transition-colors cursor-pointer">
                                        <span>↩ Kembalikan</span>
                                    </button>

                                    <a href="{{ route('tasks.show', ['task' => $task, 'return_to' => request()->fullUrl()]) }}" class="inline-flex items-center justify-center gap-1 px-2 py-0.5 text-[10px] font-semibold text-[var(--text-muted)] hover:text-[var(--text)] transition-colors">
                                        <span>Tinjau Detail &rarr;</span>
                                    </a>

                                    <!-- Reject Dialog (Modal) -->
                                    <dialog id="rejectDialog-{{ $task->id }}" onclick="if (event.target === this) this.close()" class="fixed inset-0 z-50 p-0 m-auto bg-transparent backdrop:bg-black/60 backdrop:backdrop-blur-xs max-w-md w-[95%] sm:w-full rounded-2xl shadow-2xl overflow-hidden text-left text-[var(--text)]">
                                        <div class="theme-card border border-[var(--border)] shadow-xl overflow-hidden rounded-2xl">
                                            <div class="px-5 py-4 bg-[var(--surface-2)] border-b border-[var(--border)] flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-rose-500 text-base">↩</span>
                                                    <h3 class="font-bold text-sm text-[var(--text)]">Kembalikan Tugas ke Karyawan</h3>
                                                </div>
                                                <button type="button" onclick="document.getElementById('rejectDialog-{{ $task->id }}').close()" class="p-1 rounded-lg hover:bg-[var(--surface)] text-[var(--text-muted)] hover:text-[var(--text)]">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                </button>
                                            </div>
                                            <form method="POST" action="{{ route('tasks.review', $task) }}" class="p-5 space-y-4">
                                                @csrf
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">
                                                <div>
                                                    <p class="text-xs text-[var(--text-muted)] mb-2">
                                                        Tugas <strong>{{ $task->title }}</strong> akan dikembalikan kepada <strong>{{ optional($task->user)->name ?: 'karyawan' }}</strong> untuk perbaikan.
                                                    </p>
                                                    <label for="admin_notes_{{ $task->id }}" class="block text-xs font-bold text-[var(--text)] uppercase tracking-wider mb-1.5">
                                                        Catatan Perbaikan (Opsional)
                                                    </label>
                                                    <textarea id="admin_notes_{{ $task->id }}" name="admin_notes" rows="3"
                                                        placeholder="Tuliskan alasan pengembalian atau hal yang perlu diperbaiki..."
                                                        class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-xs text-[var(--text)] focus:outline-none focus:ring-2 focus:ring-rose-500"></textarea>
                                                </div>
                                                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[var(--border)]">
                                                    <button type="button" onclick="document.getElementById('rejectDialog-{{ $task->id }}').close()" class="px-4 py-2 rounded-xl bg-[var(--surface-2)] text-[var(--text-muted)] hover:text-[var(--text)] text-xs font-semibold border border-[var(--border)]">
                                                        Batal
                                                    </button>
                                                    <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs transition-colors">
                                                        Kembalikan Tugas
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </dialog>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-[var(--text-muted)]">
                                <div class="max-w-sm mx-auto">
                                    <div class="text-3xl mb-2">✨</div>
                                    <p class="font-semibold text-[var(--text)]">Tidak ada tugas yang menunggu verifikasi</p>
                                    <p class="text-xs text-[var(--text-muted)] mt-1">Semua kiriman tugas dari karyawan telah ditinjau dan disetujui!</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($pendingTasks->hasPages())
            <div class="p-4 border-t border-[var(--border)]">
                {{ $pendingTasks->links() }}
            </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        function openRejectDialog(id) {
            const d = document.getElementById('rejectDialog-' + id);
            if (d && typeof d.showModal === 'function') {
                d.showModal();
            }
        }
    </script>
    @endpush
</x-layouts.app>
