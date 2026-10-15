<x-layouts.app>
    <x-slot:title>{{ $currentCategoryLabel }} — Daftar Tugas</x-slot:title>
    <x-slot:pageHeading>{{ $currentCategoryLabel }}</x-slot:pageHeading>

    <!-- Header Actions & Filters -->
    <div class="space-y-4">
        <!-- Top Toolbar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    <span>{{ 
                    $currentCategoryLabel }}</span>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-700">
                        {{ $totalTaskCount }} Tugas • {{ $customerGroups->total() }} Pelanggan
                    </span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    {{ auth()->user()->isAdmin() ? 'Menampilkan seluruh tugas karyawan' : 'Menampilkan tugas yang ditugaskan kepada Anda' }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Export Excel button passing current query string -->
                <a href="{{ route('tasks.export.excel', request()->query()) }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white hover:bg-slate-50 text-emerald-700 border border-emerald-300 text-xs font-semibold shadow-2xs transition-colors">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Export Excel
                </a>

                @if(auth()->user()->isAdmin())
                <button type="button" onclick="openWhatsAppReportModal()" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-2xs transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    <span>Laporan Update</span>
                </button>
                <a href="{{ route('tasks.import.view', $category ? ['category' => $category] : []) }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold shadow-2xs transition-colors">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <span>{{ $category ? "Import {$currentCategoryLabel}" : 'Import Data' }}</span>
                </a>
                <a href="{{ route('tasks.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm shadow-blue-500/20 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Beri Tugas
                </a>
                @endif
            </div>
        </div>

        @if(isset($categoryMetrics) && $categoryMetrics)
        <!-- METRICS & KP BREAKDOWN SECTION (TERSEDIA UNTUK SEMUA MENU & ROLE) -->
        <div class="space-y-4">
            @if($category === 'kontrak_exp')
            <!-- Row 1: 3 Metrik Utama Proyek (Khusus Kontrak Expired) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-3 gap-3.5">
                <!-- 1. Total Proyek / Data -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">TOTAL {{ $category ? "PROYEK {$currentCategoryLabel}" : 'SEMUA TUGAS' }}</span>
                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-bold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                        </div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-slate-900 tracking-tight">{{ $categoryMetrics['total'] }}</div>
                        <p class="text-xs font-semibold text-blue-600 mt-1">{{ $categoryMetrics['pln_count'] }} PLN • {{ $categoryMetrics['publik_count'] }} Publik</p>
                    </div>
                </div>

                <!-- 2. Status Selesai (Done) -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">STATUS SELESAI (DONE)</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-emerald-600 tracking-tight">{{ $categoryMetrics['done'] }}</div>
                        <p class="text-xs text-slate-400 mt-1">{{ $categoryMetrics['done_percent'] }}% dari total {{ $categoryMetrics['unit'] }}</p>
                    </div>
                </div>

                <!-- 3. On Process / Aktif -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">ON PROCESS / AKTIF</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm font-bold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-amber-600 tracking-tight">{{ $categoryMetrics['process'] }}</div>
                        <p class="text-xs text-slate-400 mt-1">{{ $categoryMetrics['process_percent'] }}% sedang dikerjakan</p>
                    </div>
                </div>
            </div>
            @else
            <!-- Row 1: Metrik Utama Proyek -->
            <div class="grid grid-cols-1 sm:grid-cols-2 {{ $category === 'sso_open' ? 'lg:grid-cols-4' : 'lg:grid-cols-5' }} gap-3.5">
                <!-- 1. Total Proyek / Data -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">TOTAL {{ $category ? "PROYEK {$currentCategoryLabel}" : 'SEMUA TUGAS' }}</span>
                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-bold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                        </div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-slate-900 tracking-tight">{{ $categoryMetrics['total'] }}</div>
                        <p class="text-xs font-semibold text-blue-600 mt-1">{{ $categoryMetrics['pln_count'] }} PLN • {{ $categoryMetrics['publik_count'] }} Publik</p>
                    </div>
                </div>

                <!-- 2. Total Nilai Baru / Total Rp Exception -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">{{ $category === 'exception' ? 'TOTAL RP EXCEPTION' : 'TOTAL NILAI BARU' }}</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-emerald-600 tracking-tight">{{ $categoryMetrics['total_nilai'] }}</div>
                        <p class="text-xs text-slate-400 mt-1">{{ $categoryMetrics['nilai_sub'] }}</p>
                    </div>
                </div>

                @if($category !== 'sso_open')
                <!-- 3. Net Selisih / Delta / Exception Close -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">{{ $category === 'exception' ? 'EXCEPTION CLOSE' : 'NET SELISIH / DELTA' }}</span>
                        <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center text-sm font-bold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        </div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-cyan-600 tracking-tight">{{ $categoryMetrics['net_delta'] }}</div>
                        <p class="text-xs text-slate-400 mt-1">{{ $categoryMetrics['net_delta_sub'] ?? 'Pertumbuhan Nilai Proyek' }}</p>
                    </div>
                </div>
                @endif

                <!-- 4. Status Selesai (Done) -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">STATUS SELESAI (DONE)</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-emerald-600 tracking-tight">{{ $categoryMetrics['done'] }}</div>
                        <p class="text-xs text-slate-400 mt-1">{{ $categoryMetrics['done_percent'] }}% dari total {{ $categoryMetrics['unit'] }}</p>
                    </div>
                </div>

                <!-- 5. On Process / Aktif -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">ON PROCESS / AKTIF</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm font-bold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-amber-600 tracking-tight">{{ $categoryMetrics['process'] }}</div>
                        <p class="text-xs text-slate-400 mt-1">{{ $categoryMetrics['process_percent'] }}% sedang dikerjakan</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Row 2: 4 Kartu Breakdown Per KP -->
            @php
                $kpColorMap = [
                    'surabaya' => ['border' => 'border-blue-200/70', 'dot' => 'bg-blue-600', 'badge' => 'bg-blue-100 text-blue-700', 'bar' => 'bg-blue-600'],
                    'malang' => ['border' => 'border-purple-200/70', 'dot' => 'bg-purple-600', 'badge' => 'bg-purple-100 text-purple-700', 'bar' => 'bg-purple-600'],
                    'madiun' => ['border' => 'border-amber-200/70', 'dot' => 'bg-amber-500', 'badge' => 'bg-amber-100 text-amber-700', 'bar' => 'bg-amber-500'],
                    'jember' => ['border' => 'border-emerald-200/70', 'dot' => 'bg-emerald-600', 'badge' => 'bg-emerald-100 text-emerald-700', 'bar' => 'bg-emerald-600'],
                ];
            @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                @foreach(App\Models\Task::kpList() as $kpKey => $kpName)
                @php
                    $kpData = $categoryMetrics['kp_stats'][$kpKey] ?? [
                        'total' => 0, 'done' => 0, 'process' => 0, 'percent' => 0,
                        'nilai' => 'Rp 0', 'porsi' => '0 PLN / 0 Publik', 'pln' => 0, 'publik' => 0
                    ];
                    $colors = $kpColorMap[$kpKey] ?? $kpColorMap['surabaya'];
                @endphp
                <div class="bg-white p-5 rounded-2xl border {{ $colors['border'] }} shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full {{ $colors['dot'] }}"></span>
                                <h4 class="font-extrabold text-sm text-slate-800">KP {{ $kpName }}</h4>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $colors['badge'] }}">
                                {{ $kpData['total'] }} {{ $categoryMetrics['unit'] }}
                            </span>
                        </div>
                        <div class="space-y-1 text-xs text-slate-600">
                            @if($category !== 'kontrak_exp')
                            <div class="flex justify-between">
                                <span class="text-slate-500">{{ $category === 'exception' ? 'Total Rp Exception:' : 'Nilai Proyek Baru:' }}</span>
                                <span class="font-bold text-slate-800">{{ $kpData['nilai'] }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between">
                                <span class="text-slate-500">{{ $category === 'sso_open' ? 'Porsi PDL / PPB:' : 'Porsi PLN / Publik:' }}</span>
                                <span class="font-semibold text-slate-700">
                                    @if($category === 'sso_open')
                                        {{ $kpData['porsi'] }}
                                    @else
                                        {{ $kpData['pln'] }} PLN / {{ $kpData['publik'] }} Publik
                                    @endif
                                </span>
                            </div>
                        </div>
                        <!-- Progress bar -->
                        <div class="w-full bg-slate-100 rounded-full h-1.5 my-3 overflow-hidden">
                            <div class="{{ $colors['bar'] }} h-1.5 rounded-full" style="width: {{ $kpData['percent'] }}%"></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-100">
                        <span class="text-slate-600 font-medium">Selesai: <strong class="text-slate-800">{{ $kpData['done'] }} {{ $categoryMetrics['unit'] }}</strong></span>
                        <span class="text-amber-600 font-medium">Proses: <strong>{{ $kpData['process'] }} {{ $categoryMetrics['unit'] }}</strong></span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Filter Pills / Form -->
        <!-- Filter Pills / Form -->
        <div class="theme-card p-4 border border-[var(--border)] shadow-xs">
            <form method="GET" action="{{ $category ? route('tasks.category', $category) : route('tasks.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                @if(request()->filled('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                @endif
                <div class="sm:col-span-2 relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="{{ $category === 'kontrak_exp' ? 'Cari no kontrak, pelanggan, ID PA...' : 'Cari ID PA, layanan, pelanggan...' }}"
                        class="w-full pl-9 pr-4 py-2 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-xs text-[var(--text)] placeholder-[var(--text-muted)] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-[var(--surface)] transition-all">
                    <svg class="w-4 h-4 text-[var(--text-muted)] absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>

                <!-- Status filter (Tanpa Menunggu Dikerjakan) -->
                <div>
                    <select name="status" class="w-full py-2 px-3 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-xs text-[var(--text)] focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        <option value="">Semua Status</option>
                        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>Sedang Dikerjakan</option>
                        <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Menunggu Review Admin</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Selesai (Disetujui)</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak (Revisi)</option>
                    </select>
                </div>

                <!-- Deadline filter -->
                <div>
                    <select name="deadline_filter" class="w-full py-2 px-3 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-xs text-[var(--text)] focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        <option value="">Semua Batas Waktu</option>
                        <option value="tomorrow" {{ request('deadline_filter') === 'tomorrow' ? 'selected' : '' }}>⚠️ Deadline Besok</option>
                        <option value="today" {{ request('deadline_filter') === 'today' ? 'selected' : '' }}>⚡ Deadline Hari Ini</option>
                        <option value="overdue" {{ request('deadline_filter') === 'overdue' ? 'selected' : '' }}>🚨 Terlambat (Overdue)</option>
                    </select>
                </div>

                <!-- Filter Per Kategori (PLN & Publik) -->
                <div>
                    <select name="kategori_segmen" class="w-full py-2 px-3 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-xs text-[var(--text)] focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        <option value="">Semua Kategori</option>
                        @foreach($kategoriSegmenList as $segKey => $segLabel)
                            <option value="{{ $segKey }}" {{ request('kategori_segmen') === $segKey ? 'selected' : '' }}>
                                {{ $segLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Per KP (Surabaya, Malang, Madiun, Jember) -->
                <div>
                    <select name="kp" class="w-full py-2 px-3 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-xs text-[var(--text)] focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        <option value="">Semua KP</option>
                        @foreach($kpList as $kpKey => $kpLabel)
                            <option value="{{ $kpKey }}" {{ request('kp') === $kpKey ? 'selected' : '' }}>
                                KP {{ $kpLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if(auth()->user()->isAdmin() && isset($salesList) && $salesList->isNotEmpty())
                <!-- Filter Sales -->
                <div>
                    <select name="sales_name" class="w-full py-2 px-3 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-xs text-[var(--text)] focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        <option value="">Semua Sales</option>
                        @foreach($salesList as $salesPerson)
                            <option value="{{ $salesPerson }}" {{ request('sales_name') === $salesPerson ? 'selected' : '' }}>
                                {{ $salesPerson }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <!-- Filter Bulan -->
                <div>
                    <x-month-filter :selected-month="request('month')" :selected-year="request('year')" />
                </div>

                <!-- Filter Actions -->
                <div class="flex items-center gap-2">
                    <button type="submit" class="theme-btn-primary flex-1 py-2 px-3 text-xs font-semibold">
                        Filter
                    </button>
                    @if(request()->hasAny(['search', 'status', 'deadline_filter', 'user_id', 'kp', 'kategori_segmen', 'sales_name']) || request()->filled('month') || request()->filled('year'))
                    <a href="{{ $category ? route('tasks.category', $category) : route('tasks.index') }}" class="py-2 px-3 rounded-xl bg-[var(--surface-2)] hover:bg-[var(--surface)] text-[var(--text-muted)] border border-[var(--border)] font-semibold text-xs transition-colors">
                        Reset
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Task Table (Grouped by Customer Accordion) -->
    <div class="theme-card border border-[var(--border)] shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-[var(--text)] border-collapse">
                <thead class="bg-[var(--surface-2)] text-[var(--text-muted)] font-semibold uppercase tracking-wider text-[11px] border-b border-[var(--border)]">
                    <tr>
                        <th class="py-3.5 px-4 w-10 text-center"></th>
                        <th class="py-3.5 px-4 {{ $category === 'kontrak_exp' ? 'w-[28%]' : ($category === 'sso_open' ? 'w-[28%]' : ($category === 'exception' ? 'w-[28%]' : 'w-[35%]')) }}">Nama Pelanggan</th>
                        @if($category === 'kontrak_exp')
                        <th class="py-3.5 px-4 w-[22%]">No Kontrak</th>
                        @endif
                        <th class="py-3.5 px-4 {{ $category === 'kontrak_exp' ? 'w-[20%]' : ($category === 'sso_open' ? 'w-[18%]' : ($category === 'exception' ? 'w-[18%]' : 'w-[25%]')) }}">Sales</th>
                        @if($category === 'sso_open')
                        <th class="py-3.5 px-4 w-36 text-right">Harga Baru</th>
                        @endif
                        @if($category === 'exception')
                        <th class="py-3.5 px-4 w-40 text-right">Total Rp Exception</th>
                        @endif
                        <th class="py-3.5 px-4 w-36">Deadline Terdekat</th>
                        <th class="py-3.5 px-4 text-right w-24">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border)]">
                    @forelse($customerGroups as $group)
                    @php
                        $customerKey = $group->customer_key;
                        $displayName = $group->display_name ?: 'Tanpa Nama Pelanggan';
                        $nearestDeadline = $group->nearest_deadline ? \Carbon\Carbon::parse($group->nearest_deadline) : null;
                        $isOverdue = $nearestDeadline && $nearestDeadline->isPast() && !$nearestDeadline->isToday();
                        $isDueSoon = $nearestDeadline && !$isOverdue && \Carbon\Carbon::today()->diffInDays($nearestDeadline, false) <= 1;

                        $kpLabels = ($customerKpMap->get($customerKey, collect()))->map(fn ($k) => $kpList[$k] ?? $k)->values();
                        $salesNames = $customerSalesMap->get($customerKey, collect())->filter()->unique()->values();

                        $detailParams = [
                            'customer' => \Illuminate\Support\Str::slug($displayName) ?: 'tanpa-nama-pelanggan',
                            'back_to' => request()->fullUrl(),
                        ];
                        if (request()->filled('month')) {
                            $detailParams['month'] = request('month');
                        }
                        if (request()->filled('year')) {
                            $detailParams['year'] = request('year');
                        }

                        if ($category === 'exception') {
                            $customerDetailUrl = route('tasks.exception.customer', $detailParams);
                        } elseif ($category) {
                            $detailParams['category'] = $category;
                            $customerDetailUrl = route('tasks.category.customer', $detailParams);
                        } else {
                            $customerDetailUrl = route('tasks.customer', $detailParams);
                        }

                        $contractNumbers = collect();
                        $firstContract = null;
                        $remainingContractsCount = 0;
                        if ($category === 'kontrak_exp') {
                            $contractNumbers = $customerContractMap->get($customerKey, collect())->filter()->unique()->values();
                            $firstContract = $contractNumbers->first();
                            $remainingContractsCount = max(0, $contractNumbers->count() - 1);
                        }
                    @endphp

                    <!-- Baris Induk Pelanggan -->
                    <tr class="hover:bg-blue-50/40 transition-colors cursor-pointer select-none border-b border-[var(--border)]"
                        onclick="window.location.href='{{ $customerDetailUrl }}'">
                        <td class="py-4 px-4 text-center">
                            <span class="inline-block text-[var(--text-muted)]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                            </span>
                        </td>
                        <td class="py-4 px-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg {{ $category === 'exception' ? 'bg-rose-100/70 text-rose-700' : ($category === 'kontrak_exp' ? 'bg-purple-100/70 text-purple-700' : 'bg-blue-100/70 text-blue-700') }} flex items-center justify-center font-bold text-xs shrink-0">
                                    {{ $category === 'kontrak_exp' ? '📄' : '🏢' }}
                                </div>
                                <div>
                                    <div class="font-extrabold text-[var(--text)] text-sm tracking-tight {{ $category === 'exception' ? 'hover:text-rose-600' : ($category === 'kontrak_exp' ? 'hover:text-purple-600' : 'hover:text-blue-600') }} transition-colors">
                                        {{ $displayName }}
                                    </div>
                                    @if($kpLabels->isNotEmpty())
                                    <div class="text-[11px] text-[var(--text-muted)] mt-0.5">
                                        KP {{ $kpLabels->implode(', ') }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        @if($category === 'kontrak_exp')
                        <td class="py-4 px-4 font-mono text-xs">
                            @if($firstContract)
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="font-bold text-[var(--text)] tracking-tight">{{ $firstContract }}</span>
                                    @if($remainingContractsCount > 0)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-purple-100 text-purple-700 border border-purple-200/80">
                                            +{{ $remainingContractsCount }} lainnya
                                        </span>
                                    @endif
                                </div>
                            @else
                                <span class="text-[var(--text-muted)] italic">-</span>
                            @endif
                        </td>
                        @endif
                        <td class="py-4 px-4 text-[var(--text)] font-medium">
                            @if($salesNames->isEmpty())
                                <span class="text-[var(--text-muted)] italic">-</span>
                            @else
                                <span title="{{ $salesNames->implode(', ') }}">
                                    {{ $salesNames->take(2)->implode(', ') }}@if($salesNames->count() > 2) <span class="text-[var(--text-muted)] font-normal">+{{ $salesNames->count() - 2 }} lainnya</span>@endif
                                </span>
                            @endif
                        </td>
                        @if($category === 'sso_open')
                        <td class="py-4 px-4 text-right font-mono font-bold text-[var(--text)]">
                            Rp {{ number_format((float) ($group->total_harga_baru ?? 0), 0, ',', '.') }}
                        </td>
                        @endif
                        @if($category === 'exception')
                        <td class="py-4 px-4 text-right font-mono font-bold text-[var(--text)]">
                            Rp {{ number_format((float) ($customerRpTotals[$customerKey] ?? ($group->total_rp_exception ?? 0)), 0, ',', '.') }}
                        </td>
                        @endif
                        <td class="py-4 px-4">
                            @if($nearestDeadline)
                                <div class="flex flex-col">
                                    <span class="font-bold text-xs {{ $isOverdue ? 'text-rose-600' : ($isDueSoon ? 'text-amber-600' : 'text-[var(--text)]') }}">
                                        {{ $nearestDeadline->format('d/m/Y') }}
                                    </span>
                                    @if($isOverdue)
                                        <span class="text-[10px] font-bold text-rose-600">🚨 Terlambat</span>
                                    @elseif($isDueSoon)
                                        <span class="text-[10px] font-bold text-amber-600">⚠️ Besok / Segera</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-[var(--text-muted)] italic text-xs">-</span>
                            @endif
                        </td>
                        <td class="py-4 px-4 text-right">
                            <a href="{{ $customerDetailUrl }}" class="inline-flex items-center gap-1 text-[11px] font-semibold {{ $category === 'exception' ? 'text-rose-700 bg-rose-50 hover:bg-rose-100' : ($category === 'kontrak_exp' ? 'text-purple-700 bg-purple-50 hover:bg-purple-100' : 'text-blue-600 bg-blue-50 hover:bg-blue-100') }} px-2.5 py-1 rounded-lg transition-colors">
                                <span>Rincian</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ in_array($category, ['kontrak_exp', 'sso_open', 'exception']) ? 6 : 5 }}" class="py-12 text-center text-[var(--text-muted)]">
                            @if(request()->filled('month') || request()->filled('year'))
                                Tidak ada tugas pada bulan ini.
                            @else
                                Tidak ada data tugas yang sesuai dengan filter atau pencarian Anda.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 border-t border-[var(--border)] bg-[var(--surface-2)] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-[var(--text-muted)]">
            <div class="flex items-center gap-3 flex-wrap">
                <span>Menampilkan {{ $customerGroups->firstItem() ?: 0 }} - {{ $customerGroups->lastItem() ?: 0 }} dari {{ $customerGroups->total() }} data</span>
                <form method="GET" action="{{ $category ? route('tasks.category', $category) : route('tasks.index') }}" class="inline-flex items-center gap-1.5">
                    @foreach(request()->except(['per_page', 'page']) as $k => $v)
                        @if(is_array($v))
                            @foreach($v as $arrVal)
                                <input type="hidden" name="{{ $k }}[]" value="{{ $arrVal }}">
                            @endforeach
                        @elseif(!is_null($v) && $v !== '')
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endif
                    @endforeach
                    <label for="per_page_select" class="text-[11px] font-semibold text-[var(--text-muted)]">Per halaman:</label>
                    <select id="per_page_select" name="per_page" onchange="this.form.submit()" class="py-1 px-2 rounded-lg bg-[var(--surface)] border border-[var(--border)] text-xs text-[var(--text)] focus:ring-1 focus:ring-blue-500">
                        @foreach([25, 50, 100, 200, 500] as $size)
                            <option value="{{ $size }}" {{ request('per_page', 25) == $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            @if($customerGroups->hasPages())
            <div>
                {{ $customerGroups->links() }}
            </div>
            @endif
        </div>
    </div>



    @if(auth()->user()->isAdmin())
    <x-whatsapp-report-modal />
    @endif
</x-layouts.app>






