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
                <a href="{{ route('tasks.create') }}" class="theme-btn-primary inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-bold shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Beri Tugas Baru
                </a>
                <a href="{{ route('tasks.import.view') }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-[var(--surface-2)] hover:bg-[var(--surface)] text-[var(--text)] text-xs font-semibold border border-[var(--border)] shadow-xs transition-colors">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Import Excel
                </a>
                <button type="button" onclick="openWhatsAppReportModal()" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    <span>Laporan Update (WA)</span>
                </button>
                <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-[var(--surface-2)] hover:bg-[var(--surface)] text-[var(--text)] text-xs font-semibold border border-[var(--border)] shadow-xs transition-colors">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Karyawan
                </a>
            </div>
        </div>
    </div>

    <!-- 2. SPECIAL MONITORING: STATUS DEADLINE PER KATEGORI -->
    <div>
        <div class="flex items-center justify-between mb-3">
            <div>
                <h3 class="text-sm font-bold text-[var(--text)] flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                    Monitoring Batas Waktu Tugas Per Kategori (Deadline Besok & Kritis)
                </h3>
                <p class="text-xs text-[var(--text-muted)]">Ringkasan status tugas yang belum selesai dan mendekati deadline</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            @foreach($categorySummary as $catKey => $summary)
            <div class="theme-card p-4 border {{ $summary['due_tomorrow'] > 0 ? 'border-amber-300 dark:border-amber-500/40' : 'border-[var(--border)]' }} shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-[var(--text)]">{{ $summary['label'] }}</span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-[var(--surface-2)] text-[var(--text-muted)] border border-[var(--border)]">Total: {{ $summary['total'] }}</span>
                    </div>

                    @if($summary['due_tomorrow'] > 0)
                    <div class="p-2 rounded-lg bg-amber-500/15 border border-amber-400/30 text-amber-800 dark:text-amber-300 text-xs font-semibold my-2">
                        ⚠️ <strong>{{ $summary['due_tomorrow'] }} tugas</strong> belum selesai & <u>deadline besok</u>!
                    </div>
                    @else
                    <div class="p-2 rounded-lg bg-[var(--surface-2)] text-[var(--text-muted)] text-[11px] my-2">
                        Tidak ada deadline besok
                    </div>
                    @endif

                    <div class="space-y-1 text-xs text-[var(--text-muted)] mt-2 pt-2 border-t border-[var(--border)]">
                        <div class="flex justify-between">
                            <span>Belum Selesai:</span>
                            <strong class="{{ $summary['unfinished'] > 0 ? 'text-amber-500 font-bold' : 'text-[var(--text)]' }}">{{ $summary['unfinished'] }}</strong>
                        </div>
                        @if($summary['overdue'] > 0)
                        <div class="flex justify-between text-rose-500 font-semibold">
                            <span>Terlambat / Overdue:</span>
                            <strong>{{ $summary['overdue'] }}</strong>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="mt-3 pt-2">
                    <a href="{{ route('tasks.category', $catKey) }}" class="block w-full text-center py-1.5 rounded-lg bg-[var(--primary-soft)] hover:bg-[var(--primary)] hover:text-white text-[var(--primary)] text-xs font-bold transition-colors">
                        Buka {{ $summary['label'] }} &rarr;
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- 3. TOP KPI CARDS -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="theme-card p-5 border border-[var(--border)] shadow-xs">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-[var(--text-muted)] uppercase tracking-wider">Seluruh Tugas</p>
                <span class="p-2 rounded-xl bg-blue-500/10 text-blue-500">📊</span>
            </div>
            <p class="text-3xl font-extrabold text-[var(--text)] mt-2">{{ $totalTasks }}</p>
            <p class="text-xs text-[var(--text-muted)] mt-1">Pada 5 kategori operasional</p>
        </div>

        <div class="theme-card p-5 border border-purple-300/40 dark:border-purple-500/30 shadow-xs">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-purple-600 dark:text-purple-400 uppercase tracking-wider">Antrean Verifikasi</p>
                <span class="p-2 rounded-xl bg-purple-500/10 text-purple-500">📩</span>
            </div>
            <p class="text-3xl font-extrabold text-purple-600 dark:text-purple-400 mt-2">{{ $submittedTasksCount }}</p>
            <p class="text-xs text-[var(--text-muted)] mt-1">Menunggu Approve / Reject Anda</p>
        </div>

        <div class="theme-card p-5 border border-amber-300/40 dark:border-amber-500/30 shadow-xs">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Sedang Dikerjakan</p>
                <span class="p-2 rounded-xl bg-amber-500/10 text-amber-500">⏳</span>
            </div>
            <p class="text-3xl font-extrabold text-amber-600 dark:text-amber-400 mt-2">{{ $pendingTasksCount }}</p>
            <p class="text-xs text-[var(--text-muted)] mt-1">Oleh berbagai karyawan</p>
        </div>

        <div class="theme-card p-5 border border-emerald-300/40 dark:border-emerald-500/30 shadow-xs">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Tugas Selesai</p>
                <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-500">✅</span>
            </div>
            <p class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-2">{{ $completedTasksCount }}</p>
            <p class="text-xs text-[var(--text-muted)] mt-1">Telah disetujui (Approved)</p>
        </div>
    </div>

    <!-- 4. CHARTS: DIAGRAM PER KATEGORI & DIAGRAM PER KP -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Diagram Per Kategori (PLN & Publik) -->
        <div class="theme-card p-6 border border-[var(--border)] shadow-xs flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-[var(--text)] flex items-center gap-2">
                        <span>🏷️</span>
                        Diagram Per Kategori
                    </h3>
                    <p class="text-xs text-[var(--text-muted)]">Perbandingan jumlah tugas kategori PLN dan Publik</p>
                </div>
            </div>
            <div class="flex-1 flex items-center justify-center min-h-[240px]">
                <canvas id="adminKategoriChart" class="max-h-[240px]"></canvas>
            </div>
        </div>

        <!-- Diagram Per KP (Surabaya, Malang, Madiun, Jember) -->
        <div class="theme-card p-6 border border-[var(--border)] shadow-xs flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-[var(--text)] flex items-center gap-2">
                        <span>🏢</span>
                        Diagram Per KP
                    </h3>
                    <p class="text-xs text-[var(--text-muted)]">Perbandingan jumlah tugas per Kantor Perwakilan (Surabaya, Malang, Madiun, Jember)</p>
                </div>
            </div>
            <div class="flex-1 flex items-center justify-center min-h-[220px]">
                <canvas id="adminKpChart" class="max-h-[220px]"></canvas>
            </div>

            <!-- Grid 4 Kartu Rincian Per KP -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 mt-5 pt-4 border-t border-[var(--border)]">
                @foreach($kpCardsData as $kp)
                    <div class="p-3 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] hover:border-blue-400/50 hover:shadow-xs transition-all flex flex-col justify-start">
                        <!-- Header KP: Pin Icon + Nama KP + Total Tugas -->
                        <div class="flex items-center gap-1.5 pb-2 mb-2 border-b border-[var(--border)]">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20" style="color: {{ $kp['color'] }}">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-[11px] font-bold text-[var(--text)] truncate">
                                {{ $kp['name'] }} &middot; {{ number_format($kp['total'], 0, ',', '.') }} tugas
                            </span>
                        </div>

                        <!-- Rincian Kategori (Kategori dengan 0 tugas tidak ditampilkan) -->
                        @if(count($kp['categories']) > 0)
                            <div class="space-y-1">
                                @foreach($kp['categories'] as $cat)
                                    <a href="{{ $cat['url'] }}" class="flex items-center justify-between px-1.5 py-1 -mx-1 rounded-md text-[11px] hover:bg-[var(--surface)] text-[var(--text)] transition-colors group">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <span class="w-2 h-2 rounded-full shrink-0 {{ $cat['dot_color'] }}"></span>
                                            <span class="truncate text-[var(--text-muted)] group-hover:text-[var(--text)] transition-colors">{{ $cat['label'] }}</span>
                                        </div>
                                        <span class="font-semibold tabular-nums text-[11px] ml-1.5 text-[var(--text)] shrink-0">{{ number_format($cat['count'], 0, ',', '.') }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const kategoriData = @json($kategoriChartData);
            const kpData = @json($kpChartData);

            function isDarkMode() {
                return document.documentElement.getAttribute('data-theme') === 'dark';
            }

            function getTextColor() {
                return isDarkMode() ? '#e5edff' : '#0f172a';
            }

            function getMutedColor() {
                return isDarkMode() ? '#94a3b8' : '#64748b';
            }

            function getGridColor() {
                return isDarkMode() ? '#223049' : '#e2e8f0';
            }

            function getSurfaceBorder() {
                return isDarkMode() ? '#111a2e' : '#ffffff';
            }

            let chartKategoriInstance = null;
            let chartKpInstance = null;

            // 1. Diagram Per Kategori (PLN & Publik)
            const ctxKategori = document.getElementById('adminKategoriChart');
            if (ctxKategori && window.Chart) {
                chartKategoriInstance = new window.Chart(ctxKategori, {
                    type: 'doughnut',
                    data: {
                        labels: kategoriData.labels,
                        datasets: [{
                            data: kategoriData.data,
                            backgroundColor: ['#0284C7', '#10B981'], // PLN (Sky Blue), Publik (Emerald Green)
                            borderWidth: 2,
                            borderColor: getSurfaceBorder(),
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 14,
                                    color: getTextColor(),
                                    font: { size: 11, weight: 'bold' }
                                }
                            }
                        },
                        cutout: '65%'
                    }
                });
            }

            // 2. Diagram Per KP (Surabaya, Malang, Madiun, Jember)
            const ctxKp = document.getElementById('adminKpChart');
            if (ctxKp && window.Chart) {
                chartKpInstance = new window.Chart(ctxKp, {
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
                            x: {
                                ticks: { color: getTextColor(), font: { size: 11, weight: '600' } },
                                grid: { color: getGridColor() }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1, color: getMutedColor() },
                                grid: { color: getGridColor() }
                            }
                        }
                    }
                });
            }

            // Update charts on theme change event (TAHAP 3)
            window.addEventListener('theme-changed', () => {
                if (chartKategoriInstance) {
                    chartKategoriInstance.data.datasets[0].borderColor = getSurfaceBorder();
                    chartKategoriInstance.options.plugins.legend.labels.color = getTextColor();
                    chartKategoriInstance.update();
                }
                if (chartKpInstance) {
                    chartKpInstance.options.scales.x.ticks.color = getTextColor();
                    chartKpInstance.options.scales.x.grid.color = getGridColor();
                    chartKpInstance.options.scales.y.ticks.color = getMutedColor();
                    chartKpInstance.options.scales.y.grid.color = getGridColor();
                    chartKpInstance.update();
                }
            });
        });
    </script>
    @endpush

    <x-whatsapp-report-modal />
</x-layouts.app>
