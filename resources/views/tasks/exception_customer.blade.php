<x-layouts.app>
    <x-slot:title>{{ $customerName }} - Rincian Layanan Pelanggan</x-slot:title>
    <x-slot:pageHeading>Rincian Layanan Pelanggan</x-slot:pageHeading>

    <div class="space-y-6">
        <!-- Top Back Bar & Customer Header -->
        <div class="flex flex-col gap-4">
            <div>
                <a href="{{ request()->query('back_to', route('tasks.category', 'exception')) }}" 
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[var(--surface-2)] hover:bg-[var(--surface)] text-[var(--text)] border border-[var(--border)] text-xs font-bold transition-all shadow-2xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span>Kembali ke Daftar Exception</span>
                </a>
            </div>

            <div class="theme-card p-5 border border-[var(--border)] shadow-xs rounded-2xl flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-rose-500/10 text-rose-500 flex items-center justify-center font-bold text-xl shrink-0">
                        🏢
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-lg md:text-xl font-black text-[var(--text)] tracking-tight">
                                {{ $customerName }}
                            </h1>
                            @if($kpLabels->isNotEmpty())
                                @foreach($kpLabels as $kp)
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-500/10 text-blue-500 border border-blue-500/20">
                                    KP {{ $kp }}
                                </span>
                                @endforeach
                            @endif
                        </div>
                        <p class="text-xs text-[var(--text-muted)] mt-1">
                            Rincian seluruh Surat Izin Dinas (SID) dan layanan aktif kategori Exception pelanggan ini.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <div class="px-4 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-right">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-[var(--text-muted)]">Total Dokumen / SID</div>
                        <div class="text-base font-black text-[var(--text)]">{{ number_format($totalAllSidCount, 0, ',', '.') }} Dokumen</div>
                    </div>
                    <div class="px-4 py-2.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-right">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-rose-500">Total Rp Exception</div>
                        <div class="text-base font-black text-rose-500">Rp {{ number_format($totalCustomerRp, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="theme-card p-4 border border-[var(--border)] shadow-xs">
            <form method="GET" action="{{ route('tasks.exception.customer', $customerSlug) }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                @if(request()->filled('back_to'))
                    <input type="hidden" name="back_to" value="{{ request()->query('back_to') }}">
                @endif
                @if(request()->filled('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                @endif

                <!-- Search -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-[var(--text-muted)] mb-1">Cari SID / Layanan</label>
                    <input type="text" name="search" value="{{ request()->query('search') }}" placeholder="Cari ID PA, Layanan, Sales..."
                           class="w-full px-3 py-2 rounded-xl text-xs bg-[var(--surface-2)] text-[var(--text)] border border-[var(--border)] focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>

                <!-- Bulan Filter -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-[var(--text-muted)]">Bulan</label>
                        @if(request()->filled('month') || request()->filled('year'))
                            <a href="{{ request()->fullUrlWithQuery(['month' => null, 'year' => null]) }}" class="text-[10px] text-rose-500 hover:underline font-semibold" title="Reset filter bulan">
                                Tampilkan semua bulan
                            </a>
                        @endif
                    </div>
                    <x-month-filter :selected-month="request('month')" :selected-year="request('year')" />
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-[var(--text-muted)] mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 rounded-xl text-xs bg-[var(--surface-2)] text-[var(--text)] border border-[var(--border)] focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request()->query('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ request()->query('status') === 'in_progress' ? 'selected' : '' }}>Sedang Dikerjakan</option>
                        <option value="submitted" {{ request()->query('status') === 'submitted' ? 'selected' : '' }}>Menunggu Review</option>
                        <option value="approved" {{ request()->query('status') === 'approved' ? 'selected' : '' }}>Selesai</option>
                        <option value="rejected" {{ request()->query('status') === 'rejected' ? 'selected' : '' }}>Revisi</option>
                    </select>
                </div>

                <!-- Deadline Filter -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-[var(--text-muted)] mb-1">Deadline</label>
                    <select name="deadline_filter" class="w-full px-3 py-2 rounded-xl text-xs bg-[var(--surface-2)] text-[var(--text)] border border-[var(--border)] focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">Semua Deadline</option>
                        <option value="overdue" {{ request()->query('deadline_filter') === 'overdue' ? 'selected' : '' }}>🚨 Terlambat</option>
                        <option value="today" {{ request()->query('deadline_filter') === 'today' ? 'selected' : '' }}>⚠️ Hari Ini</option>
                        <option value="tomorrow" {{ request()->query('deadline_filter') === 'tomorrow' ? 'selected' : '' }}>⏳ Besok</option>
                    </select>
                </div>

                <!-- KP Filter -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-[var(--text-muted)] mb-1">Kantor Perwakilan</label>
                    <select name="kp" class="w-full px-3 py-2 rounded-xl text-xs bg-[var(--surface-2)] text-[var(--text)] border border-[var(--border)] focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">Semua KP</option>
                        @foreach($kpList as $kKey => $kLabel)
                            <option value="{{ $kKey }}" {{ request()->query('kp') === $kKey ? 'selected' : '' }}>{{ $kLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Filter -->
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 py-2 rounded-xl theme-btn-primary text-white text-xs font-bold shadow-xs">
                        Terapkan
                    </button>
                    <a href="{{ route('tasks.exception.customer', ['customer' => $customerSlug, 'back_to' => request()->query('back_to')]) }}" 
                       class="px-3 py-2 rounded-xl bg-[var(--surface-2)] text-[var(--text-muted)] hover:text-[var(--text)] border border-[var(--border)] text-xs font-semibold">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Table Card -->
        <div class="theme-card border border-[var(--border)] shadow-xs rounded-2xl overflow-hidden">
            <!-- Header Table -->
            <div class="px-5 py-4 border-b border-[var(--border)] bg-[var(--surface-2)] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="text-sm">📄</span>
                    <h3 class="text-xs font-black uppercase tracking-wider text-[var(--text)]">
                        DAFTAR LAYANAN / SID — {{ strtoupper($customerName) }} ({{ $tasks->total() }} DOKUMEN)
                    </h3>
                </div>
                <div class="text-[11px] text-[var(--text-muted)]">
                    Halaman {{ $tasks->currentPage() }} dari {{ $tasks->lastPage() }}
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-[var(--text)]">
                    <thead class="bg-[var(--surface-2)] text-[var(--text-muted)] font-semibold uppercase tracking-wider text-[10px] border-b border-[var(--border)]">
                        <tr>
                            <th class="py-3 px-3.5">KATEGORI & KP</th>
                            <th class="py-3 px-3.5">ID PA / SID</th>
                            <th class="py-3 px-3.5">NAMA LAYANAN</th>
                            <th class="py-3 px-3.5">SALES</th>
                            <th class="py-3 px-3.5 text-right">RP EXCEPTION</th>
                            <th class="py-3 px-3.5">STATUS</th>
                            <th class="py-3 px-3.5">DEADLINE</th>
                            <th class="py-3 px-3.5 min-w-[200px] w-64">KOMENTAR</th>
                            @if(!auth()->user()->isAdmin())
                            <th class="py-3 px-3.5 text-center">SELESAI</th>
                            @endif
                            <th class="py-3 px-3.5 text-right">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)] bg-[var(--surface)]">
                        @forelse($tasks as $task)
                        <tr class="hover:bg-blue-50/30 transition-colors">
                            <td class="py-3 px-3.5">
                                <div class="flex flex-col gap-1 items-start">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/15 text-rose-500 border border-rose-500/20">
                                        EXCEPTION
                                    </span>
                                    <div class="flex items-center gap-1">
                                        @if($task->kp)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-semibold bg-[var(--surface-2)] text-[var(--text-muted)] border border-[var(--border)]">
                                            KP {{ $task->kp_label }}
                                        </span>
                                        @endif
                                        @if($task->kategori_segmen)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-semibold {{ $task->kategori_segmen === 'pln' ? 'bg-sky-500/15 text-sky-500' : 'bg-emerald-500/15 text-emerald-500' }}">
                                            {{ $task->kategori_segmen_label }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-3.5 font-mono text-xs font-bold text-[var(--text)]">
                                {{ $task->document_number ?: '-' }}
                            </td>
                            <td class="py-3 px-3.5 max-w-xs">
                                <div class="font-bold text-[var(--text)] truncate" title="{{ $task->service_type ?: $task->title }}">
                                    {{ $task->service_type ?: $task->title }}
                                </div>
                            </td>
                            <td class="py-3 px-3.5 text-[var(--text)] font-medium">
                                {{ $task->sales_name ?: (optional($task->user)->name ?: '-') }}
                            </td>
                            <td class="py-3 px-3.5 text-right font-mono font-bold text-[var(--text)]">
                                Rp {{ number_format((float) $task->rp_exception, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3.5">
                                @if($task->status === 'approved')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-500">
                                        Selesai
                                    </span>
                                @elseif($task->status === 'submitted')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/15 text-purple-500">
                                        Menunggu Review
                                    </span>
                                @elseif($task->status === 'rejected')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/15 text-rose-500">
                                        Revisi
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/15 text-blue-500">
                                        Sedang Dikerjakan
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3.5">
                                @if($task->due_date)
                                    <div class="flex flex-col">
                                        <span class="font-bold text-xs {{ $task->is_overdue ? 'text-rose-500' : 'text-[var(--text)]' }}">
                                            {{ $task->due_date->format('d/m/Y') }}
                                        </span>
                                        @if($task->is_overdue)
                                            <span class="text-[10px] font-bold text-rose-500">Terlambat</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-[var(--text-muted)] italic text-xs">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-3.5 min-w-[200px] max-w-xs">
                                @include('tasks.partials.comment-cell', ['task' => $task])
                            </td>
                            @if(!auth()->user()->isAdmin())
                            <td class="py-3 px-3.5 text-center">
                                @include('tasks.partials.complete-checkbox', ['task' => $task, 'mode' => 'table'])
                            </td>
                            @endif
                            <td class="py-3 px-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('tasks.show', ['task' => $task, 'return_to' => request()->fullUrl()]) }}" 
                                       class="px-2.5 py-1.5 rounded-lg bg-[var(--surface-2)] text-[var(--text)] hover:bg-blue-600 hover:text-white font-semibold text-xs border border-[var(--border)] transition-colors">
                                        Detail
                                    </a>
                                    @if(auth()->user()->isAdmin())
                                    <a href="{{ route('tasks.edit', ['task' => $task, 'return_to' => request()->fullUrl()]) }}" 
                                       class="p-1.5 rounded-lg hover:bg-[var(--surface-2)] text-[var(--text-muted)] hover:text-[var(--text)] transition-colors" title="Edit Tugas">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ 9 + (!auth()->user()->isAdmin() ? 1 : 0) }}" class="py-8 text-center text-[var(--text-muted)] italic">
                                @if(request()->filled('month') || request()->filled('year'))
                                    Tidak ada tugas pada bulan ini.
                                @else
                                    Tidak ada dokumen tugas untuk pelanggan ini yang cocok dengan filter.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination & Per Page -->
            @if($tasks->hasPages() || $tasks->total() > 0)
            <div class="px-5 py-4 border-t border-[var(--border)] bg-[var(--surface-2)] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-[var(--text-muted)]">
                <div class="flex items-center gap-3 flex-wrap">
                    <span>Menampilkan {{ $tasks->firstItem() ?: 0 }} - {{ $tasks->lastItem() ?: 0 }} dari {{ $tasks->total() }} data</span>
                    <form method="GET" action="{{ route('tasks.exception.customer', $customerSlug) }}" class="inline-flex items-center gap-1.5">
                        @foreach(request()->except(['per_page', 'page']) as $k => $v)
                            @if(is_array($v))
                                @foreach($v as $arrVal)
                                    <input type="hidden" name="{{ $k }}[]" value="{{ $arrVal }}">
                                @endforeach
                            @elseif(!is_null($v) && $v !== '')
                                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                            @endif
                        @endforeach
                        <label for="detail_per_page_select" class="text-[11px] font-semibold text-[var(--text-muted)]">Per halaman:</label>
                        <select id="detail_per_page_select" name="per_page" onchange="this.form.submit()" class="py-1 px-2 rounded-lg bg-[var(--surface)] border border-[var(--border)] text-xs text-[var(--text)] focus:ring-1 focus:ring-blue-500">
                            @foreach([25, 50, 100, 200, 500] as $size)
                                <option value="{{ $size }}" {{ request('per_page', 25) == $size ? 'selected' : '' }}>{{ $size }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                @if($tasks->hasPages())
                <div>
                    {{ $tasks->links() }}
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>
</x-layouts.app>

