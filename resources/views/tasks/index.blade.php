<x-layouts.app>
    <x-slot:title>{{ $currentCategoryLabel }} — Daftar Tugas</x-slot:title>
    <x-slot:pageHeading>{{ $currentCategoryLabel }}</x-slot:pageHeading>

    <!-- Header Actions & Filters -->
    <div class="space-y-4">
        <!-- Top Toolbar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    <span>{{ $currentCategoryLabel }}</span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">
                        {{ $tasks->total() }} Data
                    </span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    {{ auth()->user()->isAdmin() ? 'Menampilkan seluruh tugas karyawan' : 'Menampilkan tugas yang ditugaskan kepada Anda' }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Export buttons passing current query string -->
                <a href="{{ route('tasks.export.excel', request()->query()) }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white hover:bg-slate-50 text-emerald-700 border border-emerald-300 text-xs font-semibold shadow-2xs transition-colors">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Export Excel
                </a>

                <a href="{{ route('tasks.export.pdf', request()->query()) }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white hover:bg-slate-50 text-rose-700 border border-rose-300 text-xs font-semibold shadow-2xs transition-colors">
                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    Export PDF
                </a>

                @if(auth()->user()->isAdmin())
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
            <!-- Row 1: 5 Metrik Utama Proyek -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
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

                <!-- 2. Total Nilai Baru -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">TOTAL NILAI BARU</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-emerald-600 tracking-tight">{{ $categoryMetrics['total_nilai'] }}</div>
                        <p class="text-xs text-slate-400 mt-1">{{ $categoryMetrics['nilai_sub'] }}</p>
                    </div>
                </div>

                <!-- 3. Net Selisih / Delta -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">NET SELISIH / DELTA</span>
                        <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center text-sm font-bold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        </div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-cyan-600 tracking-tight">{{ $categoryMetrics['net_delta'] }}</div>
                        <p class="text-xs text-slate-400 mt-1">Pertumbuhan Nilai Proyek</p>
                    </div>
                </div>

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
                            <div class="flex justify-between">
                                <span class="text-slate-500">Nilai Proyek Baru:</span>
                                <span class="font-bold text-slate-800">{{ $kpData['nilai'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Porsi PLN / Publik:</span>
                                <span class="font-semibold text-slate-700">{{ $kpData['pln'] }} PLN / {{ $kpData['publik'] }} Publik</span>
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
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <form method="GET" action="{{ $category ? route('tasks.category', $category) : route('tasks.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="sm:col-span-2 relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Cari ID PA, layanan, pelanggan..."
                        class="w-full pl-9 pr-4 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>

                <!-- Status filter (Tanpa Menunggu Dikerjakan) -->
                <div>
                    <select name="status" class="w-full py-2 px-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        <option value="">Semua Status</option>
                        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>Sedang Dikerjakan</option>
                        <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Menunggu Review Admin</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Selesai (Disetujui)</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak (Revisi)</option>
                    </select>
                </div>

                <!-- Deadline filter -->
                <div>
                    <select name="deadline_filter" class="w-full py-2 px-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        <option value="">Semua Batas Waktu</option>
                        <option value="tomorrow" {{ request('deadline_filter') === 'tomorrow' ? 'selected' : '' }}>⚠️ Deadline Besok</option>
                        <option value="today" {{ request('deadline_filter') === 'today' ? 'selected' : '' }}>⚡ Deadline Hari Ini</option>
                        <option value="overdue" {{ request('deadline_filter') === 'overdue' ? 'selected' : '' }}>🚨 Terlambat (Overdue)</option>
                    </select>
                </div>

                <!-- Filter Per Kategori (PLN & Publik) -->
                <div>
                    <select name="kategori_segmen" class="w-full py-2 px-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
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
                    <select name="kp" class="w-full py-2 px-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        <option value="">Semua KP</option>
                        @foreach($kpList as $kpKey => $kpLabel)
                            <option value="{{ $kpKey }}" {{ request('kp') === $kpKey ? 'selected' : '' }}>
                                KP {{ $kpLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Actions -->
                <div class="flex items-center gap-2">
                    <button type="submit" class="flex-1 py-2 px-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs transition-colors">
                        Filter
                    </button>
                    @if(request()->hasAny(['search', 'status', 'deadline_filter', 'user_id', 'kp', 'kategori_segmen']))
                    <a href="{{ $category ? route('tasks.category', $category) : route('tasks.index') }}" class="py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold text-xs transition-colors">
                        Reset
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Task Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-[11px] border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-4">Kategori & KP</th>
                        <th class="py-3.5 px-4">ID PA</th>
                        <th class="py-3.5 px-4">Layanan</th>
                        <th class="py-3.5 px-4">Karyawan</th>
                        <th class="py-3.5 px-4">Prioritas</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Deadline</th>
                        <th class="py-3.5 px-4">Komentar</th>
                        <th class="py-3.5 px-4 {{ auth()->user()->isAdmin() ? 'text-right' : 'text-center' }}">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tasks as $task)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3.5 px-4">
                            <div class="flex flex-col gap-1 items-start">
                                <a href="{{ route('tasks.category', $task->category) }}" class="inline-block px-2 py-0.5 rounded text-[10px] font-bold
                                    {{ $task->category === 'sso_open' ? 'bg-cyan-100 text-cyan-800 hover:bg-cyan-200' : '' }}
                                    {{ $task->category === 'baa' ? 'bg-amber-100 text-amber-800 hover:bg-amber-200' : '' }}
                                    {{ $task->category === 'bai' ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : '' }}
                                    {{ $task->category === 'exception' ? 'bg-rose-100 text-rose-800 hover:bg-rose-200' : '' }}
                                    {{ $task->category === 'kontrak_exp' ? 'bg-purple-100 text-purple-800 hover:bg-purple-200' : '' }}">
                                    {{ $task->category_label }}
                                </a>
                                <div class="flex items-center gap-1">
                                    @if($task->kp)
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                        KP {{ $task->kp_label }}
                                    </span>
                                    @endif
                                    @if($task->kategori_segmen)
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-semibold {{ $task->kategori_segmen === 'pln' ? 'bg-sky-100 text-sky-700' : 'bg-emerald-100 text-emerald-700' }}">
                                        {{ $task->kategori_segmen_label }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                            {{ $task->document_number }}
                        </td>
                        <td class="py-3.5 px-4 max-w-xs">
                            <a href="{{ route('tasks.show', $task) }}" class="font-bold text-slate-900 hover:text-blue-600 truncate block">
                                {{ $task->service_type ?: ($task->title ?: '-') }}
                            </a>
                            @if($task->customer_name)
                            <div class="text-[11px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                                <span>🏢 {{ $task->customer_name }}</span>
                            </div>
                            @endif
                        </td>
                        <td class="py-3.5 px-4">
                            @if($task->user)
                                <span class="font-medium text-slate-800">{{ $task->user->name }}</span>
                                @if($task->user->nip)
                                    <span class="block text-[10px] text-slate-400">{{ $task->user->nip }}</span>
                                @endif
                            @else
                                <span class="text-slate-400 italic">Belum ditugaskan</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                {{ $task->priority === 'urgent' ? 'bg-rose-100 text-rose-700 border border-rose-200' : '' }}
                                {{ $task->priority === 'high' ? 'bg-orange-100 text-orange-700' : '' }}
                                {{ $task->priority === 'medium' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $task->priority === 'low' ? 'bg-slate-100 text-slate-600' : '' }}">
                                {{ $task->priority }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4">
                            <span id="status-badge-{{ $task->id }}" class="px-2.5 py-1 rounded-full text-[10px] font-bold
                                {{ in_array($task->status, ['pending', 'in_progress']) ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $task->status === 'submitted' ? 'bg-purple-100 text-purple-700' : '' }}
                                {{ $task->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                {{ $task->status === 'rejected' ? 'bg-rose-100 text-rose-700' : '' }}">
                                {{ $task->status_label }}
                            </span>
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
                        <td class="py-3.5 px-4 min-w-[180px]">
                            @if(!auth()->user()->isAdmin() && !in_array($task->status, ['submitted', 'approved']))
                                {{-- Karyawan: bisa isi/edit komentar --}}
                                <div class="flex flex-col gap-1.5">
                                    <div class="flex items-center gap-1">
                                        <input type="text" id="comment-{{ $task->id }}"
                                            class="flex-1 text-[11px] px-2 py-1.5 rounded-lg border focus:ring-1 focus:ring-blue-500 focus:border-blue-500 placeholder-slate-400
                                            {{ $task->is_overdue && !$task->comments ? 'border-rose-400 bg-rose-50' : 'border-slate-300' }}"
                                            placeholder="{{ $task->is_overdue ? 'Wajib diisi...' : 'Opsional...' }}"
                                            value="{{ $task->comments }}">
                                        @if($task->is_overdue)
                                        <button type="button"
                                            onclick="saveTaskComment({{ $task->id }}, '{{ route('tasks.save-comment', $task) }}')"
                                            id="save-comment-btn-{{ $task->id }}"
                                            class="shrink-0 px-2 py-1.5 rounded-lg text-[10px] font-bold transition-colors
                                            {{ $task->comments ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-rose-100 text-rose-700 hover:bg-rose-200 animate-pulse' }}"
                                            title="Simpan komentar">
                                            {{ $task->comments ? '✓' : 'Simpan' }}
                                        </button>
                                        @endif
                                    </div>
                                    @if($task->is_overdue && !$task->comments)
                                    <span class="text-[10px] text-rose-500 font-medium">⚠ Wajib isi alasan keterlambatan</span>
                                    @elseif($task->is_overdue && $task->comments)
                                    <span class="text-[10px] text-emerald-600 font-medium">✓ Komentar tersimpan</span>
                                    @endif
                                </div>
                            @else
                                {{-- Admin atau tugas sudah submitted/approved: tampilkan read-only --}}
                                @if($task->comments)
                                    <div class="flex flex-col gap-1">
                                        @if($task->is_overdue)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-rose-600">
                                            🚨 Terlambat
                                        </span>
                                        @endif
                                        <span class="text-[11px] text-slate-700 block max-w-[180px]" title="{{ $task->comments }}">
                                            {{ Str::limit($task->comments, 50) }}
                                        </span>
                                    </div>
                                @else
                                    @if($task->is_overdue && auth()->user()->isAdmin())
                                    <span class="text-[10px] text-rose-500 font-medium italic">⚠ Belum ada komentar</span>
                                    @else
                                    <span class="text-[11px] text-slate-400 italic">-</span>
                                    @endif
                                @endif
                            @endif
                        </td>
                        <td class="py-3.5 px-4 {{ auth()->user()->isAdmin() ? 'text-right' : 'text-center' }}">
                            @if(auth()->user()->isAdmin())
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('tasks.show', $task) }}" class="px-2.5 py-1.5 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white font-semibold text-xs transition-colors">
                                    Detail
                                </a>

                                <a href="{{ route('tasks.edit', $task) }}" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                            </div>
                            @else
                            <!-- Box Centang Selesai untuk Karyawan (Role User) -->
                            <form method="POST" action="{{ route('tasks.toggle-complete', $task) }}" class="inline-flex items-center justify-center task-toggle-form">
                                @csrf
                                <label class="relative inline-flex items-center justify-center cursor-pointer p-1 rounded-lg hover:bg-slate-100 transition-colors"
                                    title="{{ in_array($task->status, ['submitted', 'approved']) ? 'Tugas Selesai (Klik untuk batalkan)' : 'Klik untuk menandai tugas selesai' }}">
                                    <input type="checkbox"
                                        name="completed"
                                        value="1"
                                        {{ in_array($task->status, ['submitted', 'approved']) ? 'checked' : '' }}
                                        onchange="handleTaskToggle(this, {{ $task->id }}, '{{ route('tasks.toggle-complete', $task) }}')"
                                        class="w-5 h-5 text-emerald-600 bg-white border-2 border-slate-300 rounded-md focus:ring-emerald-500 focus:ring-2 cursor-pointer transition-all">
                                    <noscript>
                                        <button type="submit" class="ml-1 text-[10px] text-blue-600 underline">Simpan</button>
                                    </noscript>
                                </label>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-12 text-center text-slate-400">
                            Tidak ada data tugas yang sesuai dengan filter atau pencarian Anda.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tasks->hasPages())
        <div class="p-4 border-t border-slate-100">
            {{ $tasks->links() }}
        </div>
        @endif
    </div>

    @push('scripts')
    <script>
    function saveTaskComment(taskId, url) {
        const commentInput = document.getElementById('comment-' + taskId);
        const saveBtn = document.getElementById('save-comment-btn-' + taskId);
        const comment = commentInput ? commentInput.value.trim() : '';

        if (!comment) {
            alert('Silakan isi komentar alasan keterlambatan terlebih dahulu.');
            if (commentInput) {
                commentInput.focus();
                commentInput.classList.add('border-rose-500', 'ring-1', 'ring-rose-500');
            }
            return;
        }

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Disable button while saving
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.textContent = '...';
        }

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ comments: comment })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => { throw data; });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Update UI to show saved state
                commentInput.classList.remove('border-rose-400', 'bg-rose-50', 'border-rose-500', 'ring-1', 'ring-rose-500');
                commentInput.classList.add('border-emerald-400', 'bg-emerald-50');

                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.textContent = '✓';
                    saveBtn.classList.remove('bg-rose-100', 'text-rose-700', 'hover:bg-rose-200', 'animate-pulse');
                    saveBtn.classList.add('bg-emerald-100', 'text-emerald-700', 'hover:bg-emerald-200');
                }

                // Update hint text below input
                const parentDiv = commentInput.closest('.flex.flex-col');
                if (parentDiv) {
                    const hintSpan = parentDiv.querySelector('span:last-child');
                    if (hintSpan) {
                        hintSpan.className = 'text-[10px] text-emerald-600 font-medium';
                        hintSpan.textContent = '✓ Komentar tersimpan';
                    }
                }

                // Brief green flash
                setTimeout(() => {
                    commentInput.classList.remove('border-emerald-400', 'bg-emerald-50');
                    commentInput.classList.add('border-slate-300');
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Error saving comment:', error);
            alert(error.message || 'Gagal menyimpan komentar.');
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Simpan';
            }
        });
    }

    function handleTaskToggle(checkbox, taskId, url) {
        const isChecked = checkbox.checked;
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                      checkbox.form.querySelector('input[name="_token"]')?.value;

        const bodyData = {};
        
        // Grab comment from the input field if it exists
        const commentInput = document.getElementById('comment-' + taskId);
        if (commentInput && isChecked) {
            bodyData.comments = commentInput.value.trim();
        }

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(bodyData)
        })
        .then(response => {
            if (response.status === 422) {
                return response.json().then(data => {
                    if (data.requires_reason) {
                        checkbox.checked = !isChecked; // revert checkbox
                        alert(data.message); // show error (e.g. "Silakan isi komentar alasan keterlambatan.")
                        if(commentInput) {
                            commentInput.focus();
                            commentInput.classList.add('border-rose-500', 'ring-1', 'ring-rose-500');
                        }
                        throw new Error('Reason required');
                    }
                    throw data;
                });
            }
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data && data.success) {
                const badge = document.getElementById('status-badge-' + taskId);
                if (badge) {
                    if (data.is_completed) {
                        badge.className = 'px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700';
                        badge.textContent = 'Selesai (Disetujui)';
                        checkbox.parentElement.title = 'Tugas Selesai (Klik untuk batalkan)';
                    } else if (data.is_submitted) {
                        badge.className = 'px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-100 text-purple-700';
                        badge.textContent = 'Menunggu Verifikasi Admin';
                        checkbox.parentElement.title = 'Menunggu Verifikasi (Klik untuk batalkan)';
                        // Also lock comment input or replace it with text
                        const commentInput = document.getElementById('comment-' + taskId);
                        if (commentInput) {
                            const span = document.createElement('span');
                            span.className = 'text-[11px] text-slate-500 italic block max-w-[150px] truncate';
                            span.title = commentInput.value;
                            span.textContent = commentInput.value || '-';
                            commentInput.parentNode.replaceChild(span, commentInput);
                        }
                    } else {
                        badge.className = 'px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700';
                        badge.textContent = 'Sedang Dikerjakan';
                        checkbox.parentElement.title = 'Klik untuk menandai tugas selesai';
                        // Ideally we would change the span back to an input, but for simplicity a page refresh might be needed for full reverting. 
                        // It will just stay as span until refresh for now.
                    }
                }
            } else if (data && !data.success) {
                checkbox.checked = !isChecked;
                alert(data.message || 'Gagal memperbarui status tugas.');
            }
        })
        .catch(error => {
            if (error.message !== 'Reason required') {
                console.error('Error updating task status:', error);
                // checkbox.form.submit(); // Don't submit normally if it's API based to avoid page reload losing state without prompt
            }
        });
    }
    </script>
    @endpush
</x-layouts.app>

