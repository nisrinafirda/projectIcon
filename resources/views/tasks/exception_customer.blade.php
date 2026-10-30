@php
    $defaultBackRoute = route('tasks.category', 'exception');
    $backCandidate = request()->query('back_to') ?: url()->previous();
    $backUrl = \App\Services\UrlSecurity::getSafeInternalUrl($backCandidate, $defaultBackRoute);
    $safeBackTo = \App\Services\UrlSecurity::getSafeInternalUrl(request()->query('back_to'), '');
    $currentCleanUrl = $safeBackTo === '' && request()->filled('back_to')
        ? request()->fullUrlWithQuery(['back_to' => null])
        : request()->fullUrl();
@endphp

<x-layouts.app>
    <x-slot:title>{{ $customerName }} - Rincian Layanan Pelanggan</x-slot:title>
    <x-slot:pageHeading>Rincian Layanan Pelanggan</x-slot:pageHeading>

    <div class="space-y-6">
        <!-- Top Back Bar & Customer Header -->
        <div class="flex flex-col gap-4">
            <div>
                <a href="{{ $backUrl }}" 
                   onclick="if (window.history.length > 1 && document.referrer && document.referrer.includes(new URL('{{ $backUrl }}', window.location.origin).pathname)) { window.history.back(); return false; }"
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
            <form method="GET" action="{{ route('tasks.exception.customer', $customerSlug) }}" 
                  class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3"
                  data-auto-filter="true">
                @if($safeBackTo !== '')
                    <input type="hidden" name="back_to" value="{{ $safeBackTo }}">
                @endif
                @if(request()->filled('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                @endif

                <!-- Search -->
                <div class="relative">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-[var(--text-muted)] mb-1">Cari SID / Layanan</label>
                    <input type="text" name="search" value="{{ request()->query('search') }}" placeholder="Cari ID PA, Layanan, Sales..."
                           autocomplete="off"
                           class="w-full pl-3 pr-9 py-2 rounded-xl text-xs bg-[var(--surface-2)] text-[var(--text)] border border-[var(--border)] focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <svg class="search-spinner w-4 h-4 text-blue-500 animate-spin absolute right-3 top-7 hidden" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
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
                    <noscript>
                        <button type="submit" class="flex-1 py-2 rounded-xl theme-btn-primary text-white text-xs font-bold shadow-xs cursor-pointer">
                            Terapkan
                        </button>
                    </noscript>
                    <a href="{{ route('tasks.exception.customer', ['customer' => $customerSlug, 'back_to' => request()->query('back_to')]) }}" 
                       class="w-full flex items-center justify-center py-2 px-3 rounded-xl bg-[var(--surface-2)] text-[var(--text-muted)] hover:text-[var(--text)] border border-[var(--border)] text-xs font-semibold transition-colors shadow-2xs">
                        <svg class="w-3.5 h-3.5 mr-1.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Reset Filter
                    </a>
                </div>
            </form>
        </div>

        <!-- Table Card -->
        <div id="task-table-container" class="theme-card border border-[var(--border)] shadow-xs rounded-2xl overflow-hidden">
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
                            <th class="py-3 px-3.5 text-center">SELESAI</th>
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
                            @php
                                $docParts = explode('/', $task->document_number ?? '');
                                $docIdPa = trim($docParts[0] ?? '');
                                $periode = isset($docParts[1]) ? trim($docParts[1]) : null;
                            @endphp
                            <td class="py-3 px-3.5 font-mono text-xs">
                                <div class="font-bold text-[var(--text)]">{{ $docIdPa ?: ($task->document_number ?: '-') }}</div>
                                @if($periode)
                                    <div class="text-[11px] text-[var(--text-muted)] mt-0.5">{{ $periode }}</div>
                                @endif
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
                            <td class="py-3 px-3.5 text-center">
                                @include('tasks.partials.complete-checkbox', ['task' => $task, 'mode' => 'table'])
                            </td>
                            <td class="py-3 px-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('tasks.show', ['task' => $task, 'back_to' => $currentCleanUrl, 'return_to' => $currentCleanUrl]) }}" 
                                       class="px-2.5 py-1.5 rounded-lg bg-[var(--surface-2)] text-[var(--text)] hover:bg-blue-600 hover:text-white font-semibold text-xs border border-[var(--border)] transition-colors">
                                        Detail
                                    </a>
                                    @if(auth()->user()->isAdmin())
                                    <a href="{{ route('tasks.edit', ['task' => $task, 'back_to' => $currentCleanUrl, 'return_to' => $currentCleanUrl]) }}" 
                                       class="p-1.5 rounded-lg hover:bg-[var(--surface-2)] text-[var(--text-muted)] hover:text-[var(--text)] transition-colors" title="Edit Tugas">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="py-8 text-center text-[var(--text-muted)] italic">
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
                        @foreach(request()->except(['per_page', 'page', 'back_to']) as $k => $v)
                            @if(is_array($v))
                                @foreach($v as $arrVal)
                                    <input type="hidden" name="{{ $k }}[]" value="{{ $arrVal }}">
                                @endforeach
                            @elseif(!is_null($v) && $v !== '')
                                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                            @endif
                        @endforeach
                        @if($safeBackTo !== '')
                            <input type="hidden" name="back_to" value="{{ $safeBackTo }}">
                        @endif
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

