<x-layouts.app>
    <x-slot:title>Dashboard Karyawan</x-slot:title>
    <x-slot:pageHeading>Dashboard Karyawan</x-slot:pageHeading>

    <!-- 1. GREETING BANNER -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-700 via-blue-800 to-indigo-900 p-6 sm:p-8 text-white shadow-lg shadow-blue-900/20">
        <div class="absolute -right-10 -bottom-10 w-56 h-56 rounded-full bg-cyan-400/10 blur-2xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-white/15 text-blue-100 backdrop-blur-xs mb-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-300"></span>
                    Portal Monitoring Tugas ICON
                </span>
                <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    Halo, {{ $user->name }}! 👋
                </h2>
                <p class="mt-1 text-sm text-blue-100/90 max-w-xl">
                    Berikut adalah ringkasan progres tugas Anda hari ini. Pastikan untuk menyelesaikan tugas yang mendekati batas waktu (deadline).
                </p>
                @if($user->department || $user->nip)
                <div class="mt-3 flex flex-wrap gap-2 text-xs text-blue-200">
                    @if($user->nip)<span class="bg-blue-900/50 px-2 py-0.5 rounded border border-blue-400/20">NIP: {{ $user->nip }}</span>@endif
                    @if($user->department)<span class="bg-blue-900/50 px-2 py-0.5 rounded border border-blue-400/20">Divisi: {{ $user->department }}</span>@endif
                </div>
                @endif
            </div>

            <!-- Quick Jump to Category -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('tasks.index') }}" class="px-4 py-2.5 rounded-xl bg-white text-blue-900 text-xs font-bold hover:bg-blue-50 shadow-md shadow-slate-900/10 transition-colors">
                    Lihat Semua Tugas
                </a>
            </div>
        </div>
    </div>

    <!-- 2. DEADLINE ALERT (If any task is due today or tomorrow) -->
    @if($dueSoonTasks->count() > 0)
    <div class="rounded-xl bg-amber-50 border-l-4 border-amber-500 p-4 shadow-xs">
        <div class="flex items-start gap-3">
            <span class="p-1 rounded-lg bg-amber-100 text-amber-600 text-lg">⚠️</span>
            <div class="flex-1">
                <h4 class="text-sm font-bold text-amber-900">Perhatian: Ada {{ $dueSoonTasks->count() }} Tugas Mendekati Batas Waktu (Deadline Besok / Hari Ini)!</h4>
                <div class="mt-2 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach($dueSoonTasks->take(3) as $urgent)
                    <div class="p-2.5 rounded-lg bg-white border border-amber-200 text-xs flex items-center justify-between shadow-xs">
                        <div class="truncate mr-2">
                            <span class="font-bold uppercase text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-800">{{ $urgent->category_label }}</span>
                            <span class="font-medium text-slate-800 ml-1 truncate">{{ $urgent->title }}</span>
                        </div>
                        <span class="shrink-0 font-bold {{ $urgent->is_overdue ? 'text-rose-600' : 'text-amber-600' }}">
                            {{ $urgent->due_date ? $urgent->due_date->diffForHumans() : '-' }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- 3. MAIN STATS CARDS -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Tugas -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Tugas Anda</p>
                <span class="p-2 rounded-xl bg-blue-50 text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                </span>
            </div>
            <p class="text-3xl font-extrabold text-slate-900 mt-2">{{ $totalTasks }}</p>
            <p class="text-xs text-slate-500 mt-1">Tugas terdaftar di semua kategori</p>
        </div>

        <!-- Card 2: Belum Selesai (Pending + In Progress) -->
        <div class="bg-white p-5 rounded-2xl border border-amber-200/80 shadow-xs hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-amber-700 uppercase tracking-wider">Belum Selesai</p>
                <span class="p-2 rounded-xl bg-amber-50 text-amber-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
            </div>
            <p class="text-3xl font-extrabold text-amber-600 mt-2">{{ $unfinishedCount }}</p>
            <div class="flex items-center gap-2 text-[11px] text-slate-500 mt-1">
                <span>{{ $pendingCount }} Menunggu</span> • <span>{{ $inProgressCount }} Sedang Kerja</span>
            </div>
        </div>

        <!-- Card 3: Menunggu Review Admin -->
        <div class="bg-white p-5 rounded-2xl border border-purple-200/80 shadow-xs hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-purple-700 uppercase tracking-wider">Menunggu Admin</p>
                <span class="p-2 rounded-xl bg-purple-50 text-purple-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </span>
            </div>
            <p class="text-3xl font-extrabold text-purple-600 mt-2">{{ $submittedCount }}</p>
            <p class="text-xs text-slate-500 mt-1">Telah Anda serahkan</p>
        </div>

        <!-- Card 4: Selesai (Approved) -->
        <div class="bg-white p-5 rounded-2xl border border-emerald-200/80 shadow-xs hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Selesai (Disetujui)</p>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </span>
            </div>
            <p class="text-3xl font-extrabold text-emerald-600 mt-2">{{ $completedCount }}</p>
            <p class="text-xs text-slate-500 mt-1">Tugas selesai dan diverifikasi</p>
        </div>
    </div>

    <!-- 4. DIAGRAM CHART & 5 KATEGORI SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Diagram Status Tugas (Chart.js) -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Diagram Status Pengerjaan</h3>
                    <p class="text-xs text-slate-500">Distribusi seluruh tugas Anda</p>
                </div>
                <span class="text-xs bg-blue-50 text-blue-700 font-semibold px-2 py-1 rounded-lg">Realtime</span>
            </div>

            <div class="flex-1 flex items-center justify-center min-h-[220px]">
                @if($totalTasks > 0)
                <canvas id="userStatusChart" class="max-h-[220px]"></canvas>
                @else
                <div class="text-center py-8 text-slate-400 text-xs">
                    <p>Belum ada data tugas untuk ditampilkan.</p>
                </div>
                @endif
            </div>
        </div>

        <!-- 5 Fitur / Kategori Cards -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Tugas Berdasarkan Kategori</h3>
                    <p class="text-xs text-slate-500">Akses cepat ke 5 kelompok tugas ICON</p>
                </div>
                <span class="text-xs text-slate-400 font-medium">Klik untuk membuka</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <!-- SO Open Card -->
                <a href="{{ route('tasks.category', 'sso_open') }}" class="group p-4 rounded-xl border border-cyan-200/80 bg-gradient-to-br from-cyan-50/50 to-white hover:border-cyan-400 hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <span class="w-8 h-8 rounded-lg bg-cyan-500/10 text-cyan-600 flex items-center justify-center font-bold text-sm">⚡</span>
                        <span class="text-xs font-bold text-cyan-700 bg-cyan-100/60 px-2 py-0.5 rounded-full">
                            {{ $userCategories['sso_open']['total'] }} Tugas
                        </span>
                    </div>
                    <h4 class="mt-2 text-sm font-bold text-slate-900 group-hover:text-cyan-700 transition-colors">SO Open</h4>
                    <div class="mt-2 pt-2 border-t border-cyan-100 flex items-center justify-between text-xs text-slate-500">
                        <span>Belum: <strong class="text-amber-600">{{ $userCategories['sso_open']['unfinished'] }}</strong></span>
                        <span>Selesai: <strong class="text-emerald-600">{{ $userCategories['sso_open']['completed'] }}</strong></span>
                    </div>
                </a>

                <!-- BAA Card -->
                <a href="{{ route('tasks.category', 'baa') }}" class="group p-4 rounded-xl border border-amber-200/80 bg-gradient-to-br from-amber-50/50 to-white hover:border-amber-400 hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <span class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center font-bold text-sm">📋</span>
                        <span class="text-xs font-bold text-amber-700 bg-amber-100/60 px-2 py-0.5 rounded-full">
                            {{ $userCategories['baa']['total'] }} Tugas
                        </span>
                    </div>
                    <h4 class="mt-2 text-sm font-bold text-slate-900 group-hover:text-amber-700 transition-colors">BAA</h4>
                    <div class="mt-2 pt-2 border-t border-amber-100 flex items-center justify-between text-xs text-slate-500">
                        <span>Belum: <strong class="text-amber-600">{{ $userCategories['baa']['unfinished'] }}</strong></span>
                        <span>Selesai: <strong class="text-emerald-600">{{ $userCategories['baa']['completed'] }}</strong></span>
                    </div>
                </a>

                <!-- BAI Card -->
                <a href="{{ route('tasks.category', 'bai') }}" class="group p-4 rounded-xl border border-emerald-200/80 bg-gradient-to-br from-emerald-50/50 to-white hover:border-emerald-400 hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <span class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold text-sm">📝</span>
                        <span class="text-xs font-bold text-emerald-700 bg-emerald-100/60 px-2 py-0.5 rounded-full">
                            {{ $userCategories['bai']['total'] }} Tugas
                        </span>
                    </div>
                    <h4 class="mt-2 text-sm font-bold text-slate-900 group-hover:text-emerald-700 transition-colors">BAI</h4>
                    <div class="mt-2 pt-2 border-t border-emerald-100 flex items-center justify-between text-xs text-slate-500">
                        <span>Belum: <strong class="text-amber-600">{{ $userCategories['bai']['unfinished'] }}</strong></span>
                        <span>Selesai: <strong class="text-emerald-600">{{ $userCategories['bai']['completed'] }}</strong></span>
                    </div>
                </a>

                <!-- EXCEPTION Card -->
                <a href="{{ route('tasks.category', 'exception') }}" class="group p-4 rounded-xl border border-rose-200/80 bg-gradient-to-br from-rose-50/50 to-white hover:border-rose-400 hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <span class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-600 flex items-center justify-center font-bold text-sm">⚠️</span>
                        <span class="text-xs font-bold text-rose-700 bg-rose-100/60 px-2 py-0.5 rounded-full">
                            {{ $userCategories['exception']['total'] }} Tugas
                        </span>
                    </div>
                    <h4 class="mt-2 text-sm font-bold text-slate-900 group-hover:text-rose-700 transition-colors">EXCEPTION</h4>
                    <div class="mt-2 pt-2 border-t border-rose-100 flex items-center justify-between text-xs text-slate-500">
                        <span>Belum: <strong class="text-amber-600">{{ $userCategories['exception']['unfinished'] }}</strong></span>
                        <span>Selesai: <strong class="text-emerald-600">{{ $userCategories['exception']['completed'] }}</strong></span>
                    </div>
                </a>

                <!-- KONTRAK EXP Card -->
                <a href="{{ route('tasks.category', 'kontrak_exp') }}" class="group p-4 rounded-xl border border-purple-200/80 bg-gradient-to-br from-purple-50/50 to-white hover:border-purple-400 hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <span class="w-8 h-8 rounded-lg bg-purple-500/10 text-purple-600 flex items-center justify-center font-bold text-sm">⏳</span>
                        <span class="text-xs font-bold text-purple-700 bg-purple-100/60 px-2 py-0.5 rounded-full">
                            {{ $userCategories['kontrak_exp']['total'] }} Tugas
                        </span>
                    </div>
                    <h4 class="mt-2 text-sm font-bold text-slate-900 group-hover:text-purple-700 transition-colors">Kontrak Exp</h4>
                    <div class="mt-2 pt-2 border-t border-purple-100 flex items-center justify-between text-xs text-slate-500">
                        <span>Belum: <strong class="text-amber-600">{{ $userCategories['kontrak_exp']['unfinished'] }}</strong></span>
                        <span>Selesai: <strong class="text-emerald-600">{{ $userCategories['kontrak_exp']['completed'] }}</strong></span>
                    </div>
                </a>

                <!-- Quick Export Excel/PDF Card -->
                <div class="p-4 rounded-xl border border-blue-200/80 bg-blue-50/40 flex flex-col justify-between">
                    <div>
                        <span class="text-[10px] font-bold tracking-wider uppercase text-blue-700">Laporan Saya</span>
                        <h4 class="text-xs font-bold text-slate-900 mt-1">Unduh Rekap Tugas</h4>
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        <a href="{{ route('tasks.export.excel') }}" class="flex-1 py-1.5 px-2 text-center rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-[11px] transition-colors">
                            Excel
                        </a>
                        <a href="{{ route('tasks.export.pdf') }}" class="flex-1 py-1.5 px-2 text-center rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-semibold text-[11px] transition-colors">
                            PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. DAFTAR TUGAS AKTIF ANDA -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Tugas Perlu Anda Kerjakan</h3>
                <p class="text-xs text-slate-500">Daftar tugas pending dan in-progress diurutkan berdasarkan batas waktu terdekat</p>
            </div>
            <a href="{{ route('tasks.index') }}" class="text-xs font-bold text-blue-600 hover:text-blue-800">
                Lihat Semua &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-[11px] border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Kategori</th>
                        <th class="py-3 px-4">No. Dokumen</th>
                        <th class="py-3 px-4">Judul Tugas</th>
                        <th class="py-3 px-4">Pelanggan / Site</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Deadline</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($activeTasks as $task)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3.5 px-4 font-semibold">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold
                                {{ $task->category === 'sso_open' ? 'bg-cyan-100 text-cyan-800' : '' }}
                                {{ $task->category === 'baa' ? 'bg-amber-100 text-amber-800' : '' }}
                                {{ $task->category === 'bai' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                {{ $task->category === 'exception' ? 'bg-rose-100 text-rose-800' : '' }}
                                {{ $task->category === 'kontrak_exp' ? 'bg-purple-100 text-purple-800' : '' }}">
                                {{ $task->category_label }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 font-mono font-medium text-slate-900">{{ $task->document_number }}</td>
                        <td class="py-3.5 px-4 font-semibold text-slate-900 max-w-xs truncate">{{ $task->title }}</td>
                        <td class="py-3.5 px-4 text-slate-600">{{ $task->customer_name ?: '-' }}</td>
                        <td class="py-3.5 px-4">
                            <span class="px-2 py-1 rounded-full text-[10px] font-bold
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
                                <span class="block text-[10px] text-amber-600 font-semibold">Besok / Segera!</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            <a href="{{ route('tasks.show', $task) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white font-semibold text-xs transition-colors">
                                Kerjakan &rarr;
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">
                            🎉 Hebat! Tidak ada tugas pending atau semua tugas Anda telah selesai dikerjakan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const chartData = @json($statusChartData);
            const ctx = document.getElementById('userStatusChart');
            if (ctx && window.Chart) {
                new window.Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            data: chartData.data,
                            backgroundColor: [
                                '#94A3B8', // Menunggu (slate-400)
                                '#3B82F6', // Sedang Dikerjakan (blue-500)
                                '#A855F7', // Menunggu Review (purple-500)
                                '#10B981', // Selesai (emerald-500)
                                '#F43F5E', // Revisi (rose-500)
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 12,
                                    font: { size: 10 }
                                }
                            }
                        },
                        cutout: '65%'
                    }
                });
            }
        });
    </script>
    @endpush
</x-layouts.app>

