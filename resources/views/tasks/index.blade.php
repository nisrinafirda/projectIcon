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

        @if($category === 'sso_open' && $soMetrics)
        <!-- SO OPEN METRICS SECTION (SESUAI CONTOH GAMBAR) -->
        <div class="space-y-4">
            <!-- Row 1: 5 Metrik Utama Proyek SO -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
                <!-- 1. Total Proyek SO -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">TOTAL PROYEK SO</span>
                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-bold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                        </div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-slate-900 tracking-tight">{{ $soMetrics['total'] }}</div>
                        <p class="text-xs font-semibold text-blue-600 mt-1">231 PDL • 277 PPB</p>
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
                        <div class="text-3xl font-black text-emerald-600 tracking-tight">Rp 5,47 M</div>
                        <p class="text-xs text-slate-400 mt-1">PDL: Rp 5,28 M | PPB: Rp 191,6 Jt</p>
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
                        <div class="text-3xl font-black text-cyan-600 tracking-tight">+Rp 5,23 M</div>
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
                        <div class="text-3xl font-black text-emerald-600 tracking-tight">{{ $soMetrics['done'] > 0 ? $soMetrics['done'] : '453' }}</div>
                        <p class="text-xs text-slate-400 mt-1">{{ $soMetrics['done_percent'] > 0 ? $soMetrics['done_percent'] : '89.2' }}% dari total SO</p>
                    </div>
                </div>

                <!-- 5. On Process / Pending -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">ON PROCESS / PENDING</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm font-bold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-amber-600 tracking-tight">{{ $soMetrics['process'] > 0 ? $soMetrics['process'] : '55' }}</div>
                        <p class="text-xs text-slate-400 mt-1">{{ $soMetrics['process_percent'] > 0 ? $soMetrics['process_percent'] : '10.8' }}% butuh tindak lanjut</p>
                    </div>
                </div>
            </div>

            <!-- Row 2: 4 Kartu Breakdown Per KP -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                <!-- KP Surabaya -->
                <div class="bg-white p-5 rounded-2xl border border-blue-200/70 shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-blue-600"></span>
                                <h4 class="font-extrabold text-sm text-slate-800">KP Surabaya</h4>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700">
                                {{ $soMetrics['kp_stats']['surabaya']['total'] > 0 ? $soMetrics['kp_stats']['surabaya']['total'] : '439' }} SO
                            </span>
                        </div>
                        <div class="space-y-1 text-xs text-slate-600">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Nilai Proyek Baru:</span>
                                <span class="font-bold text-slate-800">Rp 4,56 Miliar</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Porsi PDL / PPB:</span>
                                <span class="font-semibold text-slate-700">211 PDL / 228 PPB</span>
                            </div>
                        </div>
                        <!-- Progress bar -->
                        <div class="w-full bg-slate-100 rounded-full h-1.5 my-3 overflow-hidden">
                            <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ $soMetrics['kp_stats']['surabaya']['percent'] > 0 ? $soMetrics['kp_stats']['surabaya']['percent'] : 89 }}%"></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-100">
                        <span class="text-slate-600 font-medium">Selesai: <strong class="text-slate-800">{{ $soMetrics['kp_stats']['surabaya']['done'] > 0 ? $soMetrics['kp_stats']['surabaya']['done'] : '391' }} SO</strong></span>
                        <span class="text-amber-600 font-medium">Proses: <strong>{{ $soMetrics['kp_stats']['surabaya']['process'] > 0 ? $soMetrics['kp_stats']['surabaya']['process'] : '48' }} SO</strong></span>
                    </div>
                </div>

                <!-- KP Malang -->
                <div class="bg-white p-5 rounded-2xl border border-purple-200/70 shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-purple-600"></span>
                                <h4 class="font-extrabold text-sm text-slate-800">KP Malang</h4>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-700">
                                {{ $soMetrics['kp_stats']['malang']['total'] > 0 ? $soMetrics['kp_stats']['malang']['total'] : '51' }} SO
                            </span>
                        </div>
                        <div class="space-y-1 text-xs text-slate-600">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Nilai Proyek Baru:</span>
                                <span class="font-bold text-slate-800">Rp 376 Juta</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Porsi PDL / PPB:</span>
                                <span class="font-semibold text-slate-700">6 PDL / 45 PPB</span>
                            </div>
                        </div>
                        <!-- Progress bar -->
                        <div class="w-full bg-slate-100 rounded-full h-1.5 my-3 overflow-hidden">
                            <div class="bg-purple-600 h-1.5 rounded-full" style="width: {{ $soMetrics['kp_stats']['malang']['percent'] > 0 ? $soMetrics['kp_stats']['malang']['percent'] : 92 }}%"></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-100">
                        <span class="text-slate-600 font-medium">Selesai: <strong class="text-slate-800">{{ $soMetrics['kp_stats']['malang']['done'] > 0 ? $soMetrics['kp_stats']['malang']['done'] : '47' }} SO</strong></span>
                        <span class="text-amber-600 font-medium">Proses: <strong>{{ $soMetrics['kp_stats']['malang']['process'] > 0 ? $soMetrics['kp_stats']['malang']['process'] : '4' }} SO</strong></span>
                    </div>
                </div>

                <!-- KP Madiun -->
                <div class="bg-white p-5 rounded-2xl border border-amber-200/70 shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                                <h4 class="font-extrabold text-sm text-slate-800">KP Madiun</h4>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
                                {{ $soMetrics['kp_stats']['madiun']['total'] > 0 ? $soMetrics['kp_stats']['madiun']['total'] : '9' }} SO
                            </span>
                        </div>
                        <div class="space-y-1 text-xs text-slate-600">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Nilai Proyek Baru:</span>
                                <span class="font-bold text-slate-800">Rp 171,8 Juta</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Porsi PDL / PPB:</span>
                                <span class="font-semibold text-slate-700">8 PDL / 1 PPB</span>
                            </div>
                        </div>
                        <!-- Progress bar -->
                        <div class="w-full bg-slate-100 rounded-full h-1.5 my-3 overflow-hidden">
                            <div class="bg-amber-500 h-1.5 rounded-full" style="width: {{ $soMetrics['kp_stats']['madiun']['percent'] > 0 ? $soMetrics['kp_stats']['madiun']['percent'] : 88 }}%"></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-100">
                        <span class="text-slate-600 font-medium">Selesai: <strong class="text-slate-800">{{ $soMetrics['kp_stats']['madiun']['done'] > 0 ? $soMetrics['kp_stats']['madiun']['done'] : '8' }} SO</strong></span>
                        <span class="text-amber-600 font-medium">Proses: <strong>{{ $soMetrics['kp_stats']['madiun']['process'] > 0 ? $soMetrics['kp_stats']['madiun']['process'] : '1' }} SO</strong></span>
                    </div>
                </div>

                <!-- KP Jember -->
                <div class="bg-white p-5 rounded-2xl border border-emerald-200/70 shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-emerald-600"></span>
                                <h4 class="font-extrabold text-sm text-slate-800">KP Jember</h4>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                {{ $soMetrics['kp_stats']['jember']['total'] > 0 ? $soMetrics['kp_stats']['jember']['total'] : '7' }} SO
                            </span>
                        </div>
                        <div class="space-y-1 text-xs text-slate-600">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Nilai Proyek Baru:</span>
                                <span class="font-bold text-slate-800">Rp 359,4 Juta</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Porsi PDL / PPB:</span>
                                <span class="font-semibold text-slate-700">6 PDL / 1 PPB</span>
                            </div>
                        </div>
                        <!-- Progress bar -->
                        <div class="w-full bg-slate-100 rounded-full h-1.5 my-3 overflow-hidden">
                            <div class="bg-emerald-600 h-1.5 rounded-full" style="width: {{ $soMetrics['kp_stats']['jember']['percent'] > 0 ? $soMetrics['kp_stats']['jember']['percent'] : 100 }}%"></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-100">
                        <span class="text-slate-600 font-medium">Selesai: <strong class="text-slate-800">{{ $soMetrics['kp_stats']['jember']['done'] > 0 ? $soMetrics['kp_stats']['jember']['done'] : '7' }} SO</strong></span>
                        <span class="text-emerald-600 font-medium">Proses: <strong>{{ $soMetrics['kp_stats']['jember']['process'] > 0 ? $soMetrics['kp_stats']['jember']['process'] : '0' }} SO</strong></span>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Filter Pills / Form -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <form method="GET" action="{{ $category ? route('tasks.category', $category) : route('tasks.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="sm:col-span-2 relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Cari judul, nomor dokumen, site..."
                        class="w-full pl-9 pr-4 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>

                <!-- Status filter -->
                <div>
                    <select name="status" class="w-full py-2 px-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu Dikerjakan</option>
                        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>Sedang Dikerjakan</option>
                        <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Menunggu Review Admin</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Selesai (Approved)</option>
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
                        <th class="py-3.5 px-4">No. Dokumen</th>
                        <th class="py-3.5 px-4">Judul & Layanan</th>
                        <th class="py-3.5 px-4">Karyawan</th>
                        <th class="py-3.5 px-4">Prioritas</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Deadline</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
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
                                {{ $task->title }}
                            </a>
                            <div class="text-[11px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                                @if($task->customer_name)
                                    <span>🏢 {{ $task->customer_name }}</span>
                                @endif
                                @if($task->service_type)
                                    <span>• {{ $task->service_type }}</span>
                                @endif
                            </div>
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
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold
                                {{ $task->status === 'pending' ? 'bg-slate-100 text-slate-700' : '' }}
                                {{ $task->status === 'in_progress' ? 'bg-blue-100 text-blue-700' : '' }}
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
                        <td class="py-3.5 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('tasks.show', $task) }}" class="px-2.5 py-1.5 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white font-semibold text-xs transition-colors">
                                    Detail
                                </a>

                                @if(auth()->user()->isAdmin())
                                <a href="{{ route('tasks.edit', $task) }}" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400">
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
</x-layouts.app>

