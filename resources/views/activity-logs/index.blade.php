<x-layouts.app>
    <x-slot:title>Riwayat Aktivitas — Monitoring PPB</x-slot:title>
    <x-slot:pageHeading>Riwayat Aktivitas</x-slot:pageHeading>

    <div class="space-y-6 max-w-7xl mx-auto">
        <!-- Top Bar with Quick Presets -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-[var(--text)] tracking-tight">Riwayat Aktivitas</h2>
                <p class="text-xs text-[var(--text-muted)] mt-0.5">
                    Catatan permanen seluruh aktivitas pekerjaan, perubahan status, verifikasi, komentar, dan pertukaran data.
                </p>
            </div>

            <!-- Quick Buttons -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
                <a href="{{ route('activity-logs.index', ['preset' => 'today']) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all shadow-xs {{ $activePreset === 'today' ? 'bg-blue-600 text-white shadow-blue-500/25 ring-2 ring-blue-500/30' : 'bg-[var(--surface-2)] text-[var(--text)] hover:bg-[var(--surface)] border border-[var(--border)]' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Hari ini</span>
                </a>
                <a href="{{ route('activity-logs.index', ['preset' => 'yesterday']) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all shadow-xs {{ $activePreset === 'yesterday' ? 'bg-blue-600 text-white shadow-blue-500/25 ring-2 ring-blue-500/30' : 'bg-[var(--surface-2)] text-[var(--text)] hover:bg-[var(--surface)] border border-[var(--border)]' }}">
                    <span>Kemarin</span>
                </a>
                <a href="{{ route('activity-logs.index', ['preset' => 'all']) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all shadow-xs {{ $activePreset === 'all' ? 'bg-blue-600 text-white shadow-blue-500/25 ring-2 ring-blue-500/30' : 'bg-[var(--surface-2)] text-[var(--text)] hover:bg-[var(--surface)] border border-[var(--border)]' }}">
                    <span>Semua Waktu</span>
                </a>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="theme-card p-5 border border-[var(--border)] shadow-xs rounded-2xl">
            <form method="GET" action="{{ route('activity-logs.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Search by SID / Customer -->
                    <div class="lg:col-span-2">
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Cari Pelanggan / SID / Info</label>
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}"
                                   placeholder="Ketik nama pelanggan / SID..."
                                   class="w-full pl-9 pr-4 py-2 rounded-xl bg-[var(--surface-2)] text-[var(--text)] placeholder-[var(--text-muted)] border border-[var(--border)] text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <svg class="w-4 h-4 text-[var(--text-muted)] absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                    </div>

                    <!-- Filter Kategori Tugas -->
                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Kategori Tugas</label>
                        <select name="category" class="w-full px-2.5 py-2 rounded-xl bg-[var(--surface-2)] text-[var(--text)] border border-[var(--border)] text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $catKey => $catLabel)
                                <option value="{{ $catKey }}" {{ request('category') === $catKey ? 'selected' : '' }}>{{ $catLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Jenis Aksi -->
                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Jenis Aksi</label>
                        <select name="action" class="w-full px-2.5 py-2 rounded-xl bg-[var(--surface-2)] text-[var(--text)] border border-[var(--border)] text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <option value="">Semua Aksi</option>
                            @foreach($actions as $actKey => $actLabel)
                                <option value="{{ $actKey }}" {{ request('action') === $actKey ? 'selected' : '' }}>{{ $actLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Secondary Row (Admin User Filter + Date Range) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2 border-t border-[var(--border)]">
                    @if(auth()->user()->isAdmin())
                    <!-- User Dropdown (Admin only) -->
                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Pengguna / Karyawan</label>
                        <select name="user_id" class="w-full px-2.5 py-2 rounded-xl bg-[var(--surface-2)] text-[var(--text)] border border-[var(--border)] text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <option value="">Semua Pengguna</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }} ({{ $u->isAdmin() ? 'Admin' : 'Karyawan' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <!-- Rentang Tanggal Mulai -->
                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Dari Tanggal</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}"
                               class="w-full px-2.5 py-2 rounded-xl bg-[var(--surface-2)] text-[var(--text)] border border-[var(--border)] text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>

                    <!-- Rentang Tanggal Sampai -->
                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Sampai Tanggal</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}"
                               class="w-full px-2.5 py-2 rounded-xl bg-[var(--surface-2)] text-[var(--text)] border border-[var(--border)] text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>

                    <!-- Action Buttons -->
                    <div class="{{ auth()->user()->isAdmin() ? '' : 'lg:col-span-2' }} flex items-end justify-end gap-2 pt-1 sm:pt-0">
                        <a href="{{ route('activity-logs.index') }}"
                           class="px-3.5 py-2 rounded-xl bg-[var(--surface-2)] hover:bg-[var(--surface)] text-[var(--text)] border border-[var(--border)] text-xs font-semibold transition-all">
                            Reset
                        </a>
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-semibold shadow-xs transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                            <span>Terapkan Filter</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Activity Timeline / List Grouped by Day -->
        @if($groupedLogs->isEmpty())
            <div class="theme-card p-12 text-center rounded-2xl border border-[var(--border)] shadow-xs space-y-3">
                <div class="w-14 h-14 mx-auto rounded-full bg-blue-500/10 text-blue-500 flex items-center justify-center text-2xl font-bold">
                    📋
                </div>
                <h3 class="text-base font-bold text-[var(--text)]">Belum ada riwayat aktivitas</h3>
                <p class="text-xs text-[var(--text-muted)] max-w-md mx-auto">
                    Tidak ditemukan catatan aktivitas untuk filter yang Anda pilih. Coba gunakan tombol "Hari ini", "Kemarin", atau pilih "Semua Waktu".
                </p>
                <div class="pt-2">
                    <a href="{{ route('activity-logs.index', ['preset' => 'all']) }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[var(--surface-2)] hover:bg-[var(--surface)] text-[var(--text)] border border-[var(--border)] text-xs font-semibold transition-all">
                        Tampilkan Semua Riwayat
                    </a>
                </div>
            </div>
        @else
            <div class="space-y-6">
                @foreach($groupedLogs as $dayLabel => $dayLogs)
                    <div class="space-y-3">
                        <!-- Day Section Header -->
                        <div class="flex items-center gap-3">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-[var(--surface-2)] border border-[var(--border)] text-xs font-bold text-[var(--text)] shadow-2xs">
                                <span>📅</span>
                                <span>{{ $dayLabel }}</span>
                            </div>
                            <div class="h-px flex-1 bg-[var(--border)]"></div>
                            <span class="text-[11px] font-medium text-[var(--text-muted)]">
                                {{ $dayLogs->count() }} aktivitas
                            </span>
                        </div>

                        <!-- Day Entries Card -->
                        <div class="theme-card border border-[var(--border)] rounded-2xl shadow-xs overflow-hidden divide-y divide-[var(--border)]">
                            @foreach($dayLogs as $log)
                                @php
                                    $time = $log->created_at->timezone('Asia/Jakarta')->format('H:i');
                                    
                                    // Badge styling based on action type
                                    $actionMeta = match($log->action) {
                                        'task_completed_checked' => [
                                            'label' => 'Centang Selesai',
                                            'badge' => 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30',
                                            'icon' => '✓',
                                        ],
                                        'task_unchecked' => [
                                            'label' => 'Batal Selesai',
                                            'badge' => 'bg-slate-500/15 text-slate-600 dark:text-slate-400 border-slate-500/30',
                                            'icon' => '↺',
                                        ],
                                        'task_verified' => [
                                            'label' => 'Diverifikasi Disetujui',
                                            'badge' => 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
                                            'icon' => '★',
                                        ],
                                        'task_rejected' => [
                                            'label' => 'Verifikasi Ditolak',
                                            'badge' => 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/30',
                                            'icon' => '✕',
                                        ],
                                        'comment_updated' => [
                                            'label' => 'Ubah Komentar',
                                            'badge' => 'bg-blue-500/15 text-blue-600 dark:text-blue-400 border-blue-500/30',
                                            'icon' => '💬',
                                        ],
                                        'status_changed' => [
                                            'label' => 'Ubah Status',
                                            'badge' => 'bg-purple-500/15 text-purple-600 dark:text-purple-400 border-purple-500/30',
                                            'icon' => '🔄',
                                        ],
                                        'task_created' => [
                                            'label' => 'Tugas Baru',
                                            'badge' => 'bg-teal-500/15 text-teal-600 dark:text-teal-400 border-teal-500/30',
                                            'icon' => '+',
                                        ],
                                        'task_deleted' => [
                                            'label' => 'Hapus Tugas',
                                            'badge' => 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/30',
                                            'icon' => '🗑',
                                        ],
                                        'import_done' => [
                                            'label' => 'Import Data',
                                            'badge' => 'bg-sky-500/15 text-sky-600 dark:text-sky-400 border-sky-500/30',
                                            'icon' => '📥',
                                        ],
                                        'export_done' => [
                                            'label' => 'Export Data',
                                            'badge' => 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border-indigo-500/30',
                                            'icon' => '📤',
                                        ],
                                        'report_updated' => [
                                            'label' => 'Laporan Update',
                                            'badge' => 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
                                            'icon' => '📊',
                                        ],
                                        default => [
                                            'label' => $log->action,
                                            'badge' => 'bg-slate-500/15 text-slate-600 dark:text-slate-400 border-slate-500/30',
                                            'icon' => '•',
                                        ],
                                    };
                                @endphp

                                <div class="p-4 sm:p-5 hover:bg-[var(--surface-2)]/60 transition-colors">
                                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                                        <!-- Main Entry Content -->
                                        <div class="space-y-1.5 flex-1 min-w-0">
                                            <!-- Top Line: Time + User + Action Badge + Category Badge -->
                                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                                <!-- Time -->
                                                <span class="font-mono font-bold text-[var(--text)] bg-[var(--surface-2)] px-2 py-0.5 rounded border border-[var(--border)]">
                                                    {{ $time }} WIB
                                                </span>

                                                <!-- User Name & Role -->
                                                <span class="font-semibold text-[var(--text)]">
                                                    {{ $log->user_name }}
                                                </span>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded font-bold uppercase {{ $log->user_role === 'admin' ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30' : 'bg-cyan-500/15 text-cyan-600 dark:text-cyan-400 border border-cyan-500/30' }}">
                                                    {{ $log->user_role === 'admin' ? 'ADMIN' : 'KARYAWAN' }}
                                                </span>

                                                <!-- Action Badge -->
                                                <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full font-semibold border {{ $actionMeta['badge'] }}">
                                                    <span>{{ $actionMeta['icon'] }}</span>
                                                    <span>{{ $actionMeta['label'] }}</span>
                                                </span>

                                                <!-- Category Badge (if any) -->
                                                @if($log->category)
                                                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/30">
                                                        {{ $categories[$log->category] ?? strtoupper($log->category) }}
                                                    </span>
                                                @endif
                                            </div>

                                            <!-- Description -->
                                            <p class="text-sm font-medium text-[var(--text)] leading-snug">
                                                {{ $log->description }}
                                            </p>

                                            <!-- Import Detail Changes (if available) -->
                                            @if($log->action === 'import_done' && ($log->details_count ?? 0) > 0)
                                                @php
                                                    $isExpanded = ($activePreset === 'today' && $dayLabel === 'Hari ini');
                                                @endphp
                                                <div class="pt-1.5 space-y-2" id="import-details-box-{{ $log->id }}" data-log-id="{{ $log->id }}" data-total="{{ $log->details_count }}" data-expanded="{{ $isExpanded ? 'true' : 'false' }}">
                                                    <button type="button"
                                                            onclick="toggleImportDetails({{ $log->id }})"
                                                            id="toggle-btn-{{ $log->id }}"
                                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-all border shadow-2xs {{ $isExpanded ? 'bg-blue-600 text-white border-blue-600 shadow-blue-500/20' : 'bg-[var(--surface-2)] hover:bg-[var(--surface)] text-blue-600 dark:text-blue-400 border-blue-500/30' }}">
                                                        <svg class="w-3.5 h-3.5 transition-transform duration-200 {{ $isExpanded ? 'rotate-180' : '' }}" id="toggle-icon-{{ $log->id }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                        </svg>
                                                        <span id="toggle-text-{{ $log->id }}">
                                                            {{ $isExpanded ? 'Sembunyikan perubahan (' . $log->details_count . ')' : 'Lihat perubahan (' . $log->details_count . ')' }}
                                                        </span>
                                                    </button>

                                                    <div id="details-wrapper-{{ $log->id }}" class="{{ $isExpanded ? '' : 'hidden' }} space-y-2">
                                                        <div class="rounded-xl border border-[var(--border)] bg-[var(--surface)] overflow-hidden shadow-2xs">
                                                            <div class="overflow-x-auto">
                                                                <table class="w-full text-left text-xs border-collapse">
                                                                    <thead>
                                                                        <tr class="bg-[var(--surface-2)] text-[var(--text-muted)] border-b border-[var(--border)] text-[11px] font-semibold">
                                                                            <th class="py-2.5 px-3.5 w-1/4">Pelanggan / SID</th>
                                                                            <th class="py-2.5 px-3.5 w-1/4">Field</th>
                                                                            <th class="py-2.5 px-3.5">Sebelum → Sesudah</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody id="details-tbody-{{ $log->id }}" class="divide-y divide-[var(--border)]/60">
                                                                        <!-- Rows injected via AJAX -->
                                                                    </tbody>
                                                                </table>
                                                            </div>

                                                            <!-- Loading Spinner / Message -->
                                                            <div id="details-loading-{{ $log->id }}" class="p-4 text-center text-xs text-[var(--text-muted)] flex items-center justify-center gap-2">
                                                                <svg class="animate-spin h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24">
                                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                                                </svg>
                                                                <span>Memuat detail perubahan...</span>
                                                            </div>

                                                            <!-- Footer with "Muat lebih banyak" -->
                                                            <div id="details-footer-{{ $log->id }}" class="hidden p-2.5 bg-[var(--surface-2)]/60 border-t border-[var(--border)] text-center">
                                                                <button type="button"
                                                                        onclick="loadMoreDetails({{ $log->id }})"
                                                                        id="load-more-btn-{{ $log->id }}"
                                                                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-[var(--surface)] hover:bg-[var(--surface-2)] text-[var(--text)] border border-[var(--border)] shadow-2xs transition-all">
                                                                    <svg class="w-3.5 h-3.5 text-[var(--text-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                                    <span id="load-more-text-{{ $log->id }}">Muat lebih banyak</span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- Document / Customer Info -->
                                            @if($log->customer_name || $log->document_number)
                                                <div class="flex flex-wrap items-center gap-3 text-xs text-[var(--text-muted)] pt-0.5">
                                                    @if($log->customer_name)
                                                        <div class="flex items-center gap-1">
                                                            <span class="opacity-70">Pelanggan:</span>
                                                            <span class="font-semibold text-[var(--text)]">{{ $log->customer_name }}</span>
                                                        </div>
                                                    @endif
                                                    @if($log->document_number)
                                                        <div class="flex items-center gap-1">
                                                            <span class="opacity-70">SID / Dokumen:</span>
                                                            <span class="font-mono font-semibold text-[var(--text)]">{{ $log->document_number }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif

                                            <!-- Value Difference (Comments / Status) -->
                                            @if($log->old_value !== null || $log->new_value !== null)
                                                <div class="mt-2.5 p-3 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-xs space-y-1.5">
                                                    @if($log->action === 'comment_updated')
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                            <div>
                                                                <span class="block text-[10px] uppercase font-bold text-rose-500/80 mb-0.5">Sebelumnya:</span>
                                                                <div class="p-2 rounded-lg bg-[var(--surface)] text-[var(--text-muted)] border border-[var(--border)] italic">
                                                                    {{ $log->old_value ?: '(kosong)' }}
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <span class="block text-[10px] uppercase font-bold text-emerald-500/80 mb-0.5">Menjadi:</span>
                                                                <div class="p-2 rounded-lg bg-[var(--surface)] text-[var(--text)] border border-[var(--border)] font-medium">
                                                                    {{ $log->new_value ?: '(kosong)' }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <span class="text-[11px] text-[var(--text-muted)]">Perubahan:</span>
                                                            @if($log->old_value)
                                                                <span class="px-2 py-0.5 rounded bg-rose-500/10 text-rose-600 dark:text-rose-400 line-through">
                                                                    {{ $log->old_value }}
                                                                </span>
                                                                <span class="text-[var(--text-muted)]">→</span>
                                                            @endif
                                                            <span class="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-semibold">
                                                                {{ $log->new_value }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="pt-4">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <script>
        const importLogsState = {};

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            const div = document.createElement('div');
            div.textContent = String(str);
            return div.innerHTML;
        }

        function renderValueChange(detail) {
            if (detail.change_type === 'added') {
                return `<span class="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-medium">${escapeHtml(detail.new_value || '-')}</span>`;
            }

            const oldVal = (detail.old_value !== null && detail.old_value !== '')
                ? `<span class="px-1.5 py-0.5 rounded bg-rose-500/10 text-rose-600 dark:text-rose-400 line-through font-mono text-[11px]">${escapeHtml(detail.old_value)}</span>`
                : `<span class="italic text-[var(--text-muted)] text-[11px]">(kosong)</span>`;

            const newVal = (detail.new_value !== null && detail.new_value !== '')
                ? `<span class="px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-semibold font-mono text-[11px]">${escapeHtml(detail.new_value)}</span>`
                : `<span class="italic text-[var(--text-muted)] text-[11px]">(kosong)</span>`;

            return `<div class="flex flex-wrap items-center gap-1.5">${oldVal} <span class="text-[var(--text-muted)] text-xs">→</span> ${newVal}</div>`;
        }

        function toggleImportDetails(logId) {
            const wrapper = document.getElementById('details-wrapper-' + logId);
            const btn = document.getElementById('toggle-btn-' + logId);
            const icon = document.getElementById('toggle-icon-' + logId);
            const textSpan = document.getElementById('toggle-text-' + logId);
            const container = document.getElementById('import-details-box-' + logId);
            const total = container ? container.getAttribute('data-total') : '';

            if (!wrapper) return;

            const isHidden = wrapper.classList.contains('hidden');

            if (isHidden) {
                wrapper.classList.remove('hidden');
                icon.classList.add('rotate-180');
                btn.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-all border shadow-2xs bg-blue-600 text-white border-blue-600 shadow-blue-500/20';
                textSpan.textContent = 'Sembunyikan perubahan (' + total + ')';

                const state = importLogsState[logId];
                if (!state || (!state.loaded && !state.loading)) {
                    fetchImportDetails(logId);
                }
            } else {
                wrapper.classList.add('hidden');
                icon.classList.remove('rotate-180');
                btn.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-all border shadow-2xs bg-[var(--surface-2)] hover:bg-[var(--surface)] text-blue-600 dark:text-blue-400 border-blue-500/30';
                textSpan.textContent = 'Lihat perubahan (' + total + ')';
            }
        }

        function fetchImportDetails(logId) {
            if (!importLogsState[logId]) {
                importLogsState[logId] = {
                    offset: 0,
                    limit: 50,
                    loaded: false,
                    loading: false,
                    hasMore: false,
                    lastCustomer: null
                };
            }

            const state = importLogsState[logId];
            if (state.loading) return;
            state.loading = true;

            const loadingEl = document.getElementById('details-loading-' + logId);
            const footerEl = document.getElementById('details-footer-' + logId);
            const tbody = document.getElementById('details-tbody-' + logId);

            if (loadingEl) loadingEl.classList.remove('hidden');

            const url = `{{ url('/activity-logs') }}/${logId}/details?offset=${state.offset}&limit=${state.limit}`;

            fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(data => {
                state.loading = false;
                state.loaded = true;
                state.offset = data.next_offset;
                state.hasMore = data.has_more;

                if (loadingEl) loadingEl.classList.add('hidden');

                if (data.details && data.details.length > 0) {
                    let html = '';
                    data.details.forEach(detail => {
                        const custName = detail.customer_name || 'Tanpa Pelanggan';
                        if (state.lastCustomer !== custName) {
                            state.lastCustomer = custName;
                            html += `
                                <tr class="bg-blue-500/5 dark:bg-blue-500/10 border-t border-[var(--border)] font-semibold">
                                    <td colspan="3" class="px-3.5 py-1.5 text-xs text-[var(--text)]">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <span class="flex items-center gap-1.5 font-bold">
                                                <span class="text-blue-500">🏢</span>
                                                <span>${escapeHtml(custName)}</span>
                                            </span>
                                            ${detail.sid ? `<span class="font-mono text-[10px] px-2 py-0.5 rounded bg-[var(--surface-2)] text-[var(--text-muted)] border border-[var(--border)]">SID: ${escapeHtml(detail.sid)}</span>` : ''}
                                        </div>
                                    </td>
                                </tr>
                            `;
                        }

                        html += `
                            <tr class="hover:bg-[var(--surface-2)]/50 transition-colors">
                                <td class="px-3.5 py-2 text-xs text-[var(--text-muted)] font-mono pl-6">
                                    ${escapeHtml(detail.sid || '-')}
                                </td>
                                <td class="px-3.5 py-2 text-xs text-[var(--text)]">
                                    ${detail.change_type === 'added' ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-teal-500/15 text-teal-600 dark:text-teal-400 border border-teal-500/30 mr-1.5">Baru</span>' : ''}
                                    <span class="font-medium">${escapeHtml(detail.field_label || detail.field)}</span>
                                </td>
                                <td class="px-3.5 py-2 text-xs">
                                    ${renderValueChange(detail)}
                                </td>
                            </tr>
                        `;
                    });

                    tbody.insertAdjacentHTML('beforeend', html);
                } else if (state.offset === 0) {
                    tbody.innerHTML = `<tr><td colspan="3" class="p-4 text-center text-xs text-[var(--text-muted)]">Tidak ada detail perubahan yang tersimpan.</td></tr>`;
                }

                if (footerEl) {
                    if (data.has_more) {
                        footerEl.classList.remove('hidden');
                        const moreBtnText = document.getElementById('load-more-text-' + logId);
                        if (moreBtnText) {
                            moreBtnText.textContent = `Muat lebih banyak (tersisa ${data.remaining})`;
                        }
                    } else {
                        footerEl.classList.add('hidden');
                    }
                }
            })
            .catch(err => {
                state.loading = false;
                if (loadingEl) {
                    loadingEl.classList.remove('hidden');
                    loadingEl.innerHTML = `<span class="text-rose-500">Gagal memuat detail: ${escapeHtml(err.message)}</span>`;
                }
            });
        }

        function loadMoreDetails(logId) {
            fetchImportDetails(logId);
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-log-id]').forEach(function(el) {
                const isExpanded = el.getAttribute('data-expanded') === 'true';
                const logId = el.getAttribute('data-log-id');
                if (isExpanded && logId) {
                    fetchImportDetails(logId);
                }
            });
        });
    </script>
</x-layouts.app>

