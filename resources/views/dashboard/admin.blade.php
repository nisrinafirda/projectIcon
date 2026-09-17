<x-layouts.app>
    <x-slot:title>Dashboard Administrator</x-slot:title>
    <x-slot:pageHeading>Monitoring & Kontrol Operasional ICON</x-slot:pageHeading>

    <!-- 1. ADMIN HEADER & QUICK ACTIONS -->
    <div class="rounded-2xl bg-gradient-to-r from-slate-900 via-blue-950 to-blue-900 p-6 sm:p-8 text-white shadow-lg shadow-blue-950/20">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-400/20 text-amber-300 border border-amber-400/30 mb-2">
                    ⚡ Mode Administrator ICON
                </span>
                <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                    Dashboard Monitoring Keseluruhan Tugas
                </h2>
                <p class="mt-1 text-sm text-blue-200/90 max-w-2xl">
                    Pantau kinerja penugasan karyawan pada 5 kategori (SO Open, BAA, BAI, Exception, Kontrak Exp), verifikasi tugas masuk, dan cegah data ganda.
                </p>
            </div>

            <!-- Admin Action Buttons -->
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('tasks.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-md shadow-blue-600/30 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Beri Tugas Baru
                </a>
                <a href="{{ route('tasks.import.view') }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-100 text-xs font-semibold border border-slate-700 shadow-xs transition-colors">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Import Excel
                </a>
                <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-100 text-xs font-semibold border border-slate-700 shadow-xs transition-colors">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Karyawan
                </a>
            </div>
        </div>
    </div>

    <!-- 2. SPECIAL MONITORING: STATUS DEADLINE PER KATEGORI (SESUAI REQUEST USER) -->
    <div>
        <div class="flex items-center justify-between mb-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                    Monitoring Batas Waktu Tugas Per Kategori (Deadline Besok & Kritis)
                </h3>
                <p class="text-xs text-slate-500">Ringkasan status tugas yang belum selesai dan mendekati deadline</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            @foreach($categorySummary as $catKey => $summary)
            <div class="bg-white p-4 rounded-xl border {{ $summary['due_tomorrow'] > 0 ? 'border-amber-300 bg-amber-50/20' : 'border-slate-200' }} shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-700">{{ $summary['label'] }}</span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">Total: {{ $summary['total'] }}</span>
                    </div>

                    <!-- Highlight Request User: Contoh "di BAA ada 3 tugas yg belum selesai dan deadline besok" -->
                    @if($summary['due_tomorrow'] > 0)
                    <div class="p-2 rounded-lg bg-amber-100/70 border border-amber-300/80 text-amber-900 text-xs font-semibold my-2">
                        ⚠️ <strong>{{ $summary['due_tomorrow'] }} tugas</strong> belum selesai & <u>deadline besok</u>!
                    </div>
                    @else
                    <div class="p-2 rounded-lg bg-slate-100 text-slate-600 text-[11px] my-2">
                        Tidak ada deadline besok
                    </div>
                    @endif

                    <div class="space-y-1 text-xs text-slate-500 mt-2 pt-2 border-t border-slate-100">
                        <div class="flex justify-between">
                            <span>Belum Selesai:</span>
                            <strong class="{{ $summary['unfinished'] > 0 ? 'text-amber-600' : 'text-slate-700' }}">{{ $summary['unfinished'] }}</strong>
                        </div>
                        @if($summary['overdue'] > 0)
                        <div class="flex justify-between text-rose-600 font-semibold">
                            <span>Terlambat / Overdue:</span>
                            <strong>{{ $summary['overdue'] }}</strong>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="mt-3 pt-2">
                    <a href="{{ route('tasks.category', $catKey) }}" class="block w-full text-center py-1.5 rounded-lg bg-blue-50 hover:bg-blue-600 hover:text-white text-blue-700 text-xs font-bold transition-colors">
                        Buka {{ $summary['label'] }} &rarr;
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- 3. TOP KPI CARDS -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Seluruh Tugas</p>
                <span class="p-2 rounded-xl bg-blue-50 text-blue-600">📊</span>
            </div>
            <p class="text-3xl font-extrabold text-slate-900 mt-2">{{ $totalTasks }}</p>
            <p class="text-xs text-slate-500 mt-1">Pada 5 kategori operasional</p>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-purple-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-purple-700 uppercase tracking-wider">Antrean Verifikasi</p>
                <span class="p-2 rounded-xl bg-purple-50 text-purple-600">📩</span>
            </div>
            <p class="text-3xl font-extrabold text-purple-600 mt-2">{{ $submittedTasksCount }}</p>
            <p class="text-xs text-slate-500 mt-1">Menunggu Approve / Reject Anda</p>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-amber-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-amber-700 uppercase tracking-wider">Sedang Dikerjakan</p>
                <span class="p-2 rounded-xl bg-amber-50 text-amber-600">⏳</span>
            </div>
            <p class="text-3xl font-extrabold text-amber-600 mt-2">{{ $pendingTasksCount }}</p>
            <p class="text-xs text-slate-500 mt-1">Oleh berbagai karyawan</p>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-emerald-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Tugas Selesai</p>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600">✅</span>
            </div>
            <p class="text-3xl font-extrabold text-emerald-600 mt-2">{{ $completedTasksCount }}</p>
            <p class="text-xs text-slate-500 mt-1">Telah disetujui (Approved)</p>
        </div>
    </div>


    <!-- 4. CHARTS: DIAGRAM PER KATEGORI & DIAGRAM PER KP -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Diagram Per Kategori (PLN & Publik) -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span>🏷️</span>
                        Diagram Per Kategori
                    </h3>
                    <p class="text-xs text-slate-500">Perbandingan jumlah tugas kategori PLN dan Publik</p>
                </div>
            </div>
            <div class="flex-1 flex items-center justify-center min-h-[240px]">
                <canvas id="adminKategoriChart" class="max-h-[240px]"></canvas>
            </div>
        </div>

        <!-- Diagram Per KP (Surabaya, Malang, Madiun, Jember) -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span>🏢</span>
                        Diagram Per KP
                    </h3>
                    <p class="text-xs text-slate-500">Perbandingan jumlah tugas per Kantor Perwakilan (Surabaya, Malang, Madiun, Jember)</p>
                </div>
            </div>
            <div class="flex-1 flex items-center justify-center min-h-[240px]">
                <canvas id="adminKpChart" class="max-h-[240px]"></canvas>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const kategoriData = @json($kategoriChartData);
            const kpData = @json($kpChartData);

            // 1. Diagram Per Kategori (PLN & Publik)
            const ctxKategori = document.getElementById('adminKategoriChart');
            if (ctxKategori && window.Chart) {
                new window.Chart(ctxKategori, {
                    type: 'doughnut',
                    data: {
                        labels: kategoriData.labels,
                        datasets: [{
                            data: kategoriData.data,
                            backgroundColor: ['#0284C7', '#10B981'], // PLN (Sky Blue), Publik (Emerald Green)
                            borderWidth: 2,
                            borderColor: '#ffffff',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 14, font: { size: 11, weight: 'bold' } } }
                        },
                        cutout: '65%'
                    }
                });
            }

            // 2. Diagram Per KP (Surabaya, Malang, Madiun, Jember)
            const ctxKp = document.getElementById('adminKpChart');
            if (ctxKp && window.Chart) {
                new window.Chart(ctxKp, {
                    type: 'bar',
                    data: {
                        labels: kpData.labels,
                        datasets: [{
                            label: 'Jumlah Tugas',
                            data: kpData.data,
                            backgroundColor: [
                                '#3B82F6', // Surabaya (Blue)
                                '#8B5CF6', // Malang (Purple)
                                '#F59E0B', // Madiun (Amber)
                                '#10B981', // Jember (Emerald)
                            ],
                            borderRadius: 8,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: { beginAtZero: true, ticks: { stepSize: 1 } }
                        }
                    }
                });
            }
        });
    </script>
    @endpush
</x-layouts.app>

