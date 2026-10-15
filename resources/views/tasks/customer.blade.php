@php
    $pageCategory = $category ?? request()->route('category');
    $defaultBackRoute = $pageCategory ? route('tasks.category', $pageCategory) : route('tasks.index');
    $backUrl = request()->query('back_to', $defaultBackRoute);
    $backLabel = $currentCategoryLabel ? 'Kembali ke ' . $currentCategoryLabel : 'Kembali ke Semua Tugas';
    $formAction = $pageCategory 
        ? route('tasks.category.customer', ['category' => $pageCategory, 'customer' => $customerSlug]) 
        : route('tasks.customer', ['customer' => $customerSlug]);
@endphp

<x-layouts.app>
    <x-slot:title>{{ $customerName ?: 'Tanpa Nama Pelanggan' }} - Rincian Layanan Pelanggan</x-slot:title>
    <x-slot:pageHeading>Rincian Layanan Pelanggan</x-slot:pageHeading>

    <div class="space-y-6">
        <!-- Top Back Bar & Customer Header -->
        <div class="flex flex-col gap-4">
            <div>
                <a href="{{ $backUrl }}" 
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[var(--surface-2)] hover:bg-[var(--surface)] text-[var(--text)] border border-[var(--border)] text-xs font-bold transition-all shadow-2xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span>{{ $backLabel }}</span>
                </a>
            </div>

            <div class="theme-card p-5 border border-[var(--border)] shadow-xs rounded-2xl flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl {{ $pageCategory === 'kontrak_exp' ? 'bg-purple-500/10 text-purple-500' : 'bg-blue-500/10 text-blue-500' }} flex items-center justify-center font-bold text-xl shrink-0">
                        {{ $pageCategory === 'kontrak_exp' ? '📄' : '🏢' }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-lg md:text-xl font-black text-[var(--text)] tracking-tight">
                                {{ $customerName ?: 'Tanpa Nama Pelanggan' }}
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
                            @if($currentCategoryLabel)
                                Rincian seluruh Surat Izin Dinas (SID) dan layanan aktif kategori {{ $currentCategoryLabel }} pelanggan ini.
                            @else
                                Rincian seluruh Surat Izin Dinas (SID) dan layanan aktif pelanggan ini.
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <div class="px-4 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-right">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-[var(--text-muted)]">Total Dokumen / SID</div>
                        <div class="text-base font-black text-[var(--text)]">{{ number_format($totalAllSidCount, 0, ',', '.') }} Dokumen</div>
                    </div>
                    @if($pageCategory === 'sso_open' || (isset($totalCustomerHargaBaru) && $totalCustomerHargaBaru > 0))
                    <div class="px-4 py-2.5 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-right">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-cyan-600">Total Harga Baru</div>
                        <div class="text-base font-black text-cyan-600">Rp {{ number_format((float) ($totalCustomerHargaBaru ?? 0), 0, ',', '.') }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="theme-card p-4 border border-[var(--border)] shadow-xs">
            <form method="GET" action="{{ $formAction }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 {{ $pageCategory ? 'lg:grid-cols-6' : 'lg:grid-cols-7' }} gap-3">
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

                <!-- Kategori Filter (hanya jika halaman Semua Tugas) -->
                @if(!$pageCategory)
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-[var(--text-muted)] mb-1">Kategori Tugas</label>
                    <select name="category" class="w-full px-3 py-2 rounded-xl text-xs bg-[var(--surface-2)] text-[var(--text)] border border-[var(--border)] focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $cKey => $cLabel)
                            <option value="{{ $cKey }}" {{ request()->query('category') === $cKey ? 'selected' : '' }}>{{ $cLabel }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <!-- Bulan Filter -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-[var(--text-muted)]">Bulan</label>
                        @if(request()->filled('month') || request()->filled('year'))
                            <a href="{{ request()->fullUrlWithQuery(['month' => null, 'year' => null]) }}" class="text-[10px] text-blue-500 hover:underline font-semibold" title="Reset filter bulan">
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
                    <a href="{{ $pageCategory ? route('tasks.category.customer', ['category' => $pageCategory, 'customer' => $customerSlug, 'back_to' => request()->query('back_to')]) : route('tasks.customer', ['customer' => $customerSlug, 'back_to' => request()->query('back_to')]) }}" 
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
                        DAFTAR LAYANAN / SID — {{ strtoupper($customerName ?: 'TANPA NAMA PELANGGAN') }} ({{ $tasks->total() }} DOKUMEN)
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
                            <th class="py-3 px-3.5">{{ $pageCategory === 'kontrak_exp' ? 'NO KONTRAK / ID PA' : 'ID PA / SID' }}</th>
                            <th class="py-3 px-3.5">NAMA LAYANAN</th>
                            <th class="py-3 px-3.5">SALES</th>
                            @if($pageCategory === 'sso_open')
                            <th class="py-3 px-3.5 text-right">HARGA BARU</th>
                            @endif
                            <th class="py-3 px-3.5">STATUS</th>
                            <th class="py-3 px-3.5">DEADLINE</th>
                            <th class="py-3 px-3.5 min-w-[200px] w-64">KOMENTAR</th>
                            @if(!auth()->user()->isAdmin())
                            <th class="py-3 px-3.5 text-center">SELESAI</th>
                            @endif
                            <th class="py-3 px-3.5 {{ auth()->user()->isAdmin() ? 'text-right' : 'text-center' }}">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)] bg-[var(--surface)]">
                        @forelse($tasks as $task)
                        <tr class="hover:bg-blue-50/30 transition-colors">
                            <td class="py-3 px-3.5">
                                <div class="flex flex-col gap-1 items-start">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold
                                        {{ $task->category === 'sso_open' ? 'bg-cyan-500/15 text-cyan-600 border border-cyan-500/20' : '' }}
                                        {{ $task->category === 'baa' ? 'bg-amber-500/15 text-amber-600 border border-amber-500/20' : '' }}
                                        {{ $task->category === 'bai' ? 'bg-emerald-500/15 text-emerald-600 border border-emerald-500/20' : '' }}
                                        {{ $task->category === 'exception' ? 'bg-rose-500/15 text-rose-600 border border-rose-500/20' : '' }}
                                        {{ $task->category === 'kontrak_exp' ? 'bg-purple-500/15 text-purple-600 border border-purple-500/20' : '' }}">
                                        {{ $task->category_label }}
                                    </span>
                                    <div class="flex items-center gap-1">
                                        @if($task->so_type)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-extrabold {{ $task->so_type === 'PDL' ? 'bg-indigo-100 text-indigo-700 border border-indigo-200' : 'bg-fuchsia-100 text-fuchsia-700 border border-fuchsia-200' }}">
                                            {{ $task->so_type }}
                                        </span>
                                        @endif
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
                            <td class="py-3 px-3.5 font-mono text-xs">
                                @if($pageCategory === 'kontrak_exp' && $task->nomor_kontrak)
                                    <div class="font-bold text-[var(--text)] tracking-tight">{{ $task->nomor_kontrak }}</div>
                                    @if($task->document_number)
                                        <div class="text-[11px] text-[var(--text-muted)] mt-0.5">ID PA: {{ $task->document_number }}</div>
                                    @endif
                                    @if($task->mutation)
                                        <div class="mt-1">
                                            <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold bg-[var(--surface-2)] text-[var(--text)] border border-[var(--border)] font-sans tracking-normal">
                                                {{ $task->mutation->nama }}
                                            </span>
                                        </div>
                                    @endif
                                @else
                                    <div class="font-bold text-[var(--text)]">{{ $task->document_number ?: '-' }}</div>
                                    @if($task->mutation)
                                        <div class="mt-1">
                                            <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold bg-[var(--surface-2)] text-[var(--text)] border border-[var(--border)] font-sans tracking-normal">
                                                {{ $task->mutation->nama }}
                                            </span>
                                        </div>
                                    @endif
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
                            @if($pageCategory === 'sso_open')
                            <td class="py-3 px-3.5 text-right font-mono font-bold text-[var(--text)] whitespace-nowrap">
                                Rp {{ number_format((float) $task->harga_baru, 0, ',', '.') }}
                            </td>
                            @endif
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
                            <td class="py-3 px-3.5 {{ auth()->user()->isAdmin() ? 'text-right' : 'text-center' }}">
                                <div class="flex items-center {{ auth()->user()->isAdmin() ? 'justify-end' : 'justify-center' }} gap-1.5">
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
                            <td colspan="{{ ($pageCategory === 'sso_open' ? 9 : 8) + (!auth()->user()->isAdmin() ? 1 : 0) }}" class="py-8 text-center text-[var(--text-muted)] italic">
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
                    <form method="GET" action="{{ $formAction }}" class="inline-flex items-center gap-1.5">
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

