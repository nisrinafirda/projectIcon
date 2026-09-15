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
                <a href="{{ route('tasks.import.view') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold shadow-2xs transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Import
                </a>
                <a href="{{ route('tasks.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm shadow-blue-500/20 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Beri Tugas
                </a>
                @endif
            </div>
        </div>

        <!-- Filter Pills / Form -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <form method="GET" action="{{ $category ? route('tasks.category', $category) : route('tasks.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- Search input -->
                <div class="lg:col-span-2 relative">
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

                <!-- Deadline filter (e.g. Besok, Hari ini, Overdue) -->
                <div>
                    <select name="deadline_filter" class="w-full py-2 px-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        <option value="">Semua Batas Waktu</option>
                        <option value="tomorrow" {{ request('deadline_filter') === 'tomorrow' ? 'selected' : '' }}>⚠️ Deadline Besok</option>
                        <option value="today" {{ request('deadline_filter') === 'today' ? 'selected' : '' }}>⚡ Deadline Hari Ini</option>
                        <option value="overdue" {{ request('deadline_filter') === 'overdue' ? 'selected' : '' }}>🚨 Terlambat (Overdue)</option>
                    </select>
                </div>

                <!-- Filter Actions -->
                <div class="flex items-center gap-2">
                    <button type="submit" class="flex-1 py-2 px-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs transition-colors">
                        Filter
                    </button>
                    @if(request()->hasAny(['search', 'status', 'deadline_filter', 'user_id']))
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
                        <th class="py-3.5 px-4">Kategori</th>
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
                            <a href="{{ route('tasks.category', $task->category) }}" class="inline-block px-2 py-0.5 rounded text-[10px] font-bold
                                {{ $task->category === 'sso_open' ? 'bg-cyan-100 text-cyan-800 hover:bg-cyan-200' : '' }}
                                {{ $task->category === 'baa' ? 'bg-amber-100 text-amber-800 hover:bg-amber-200' : '' }}
                                {{ $task->category === 'bai' ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : '' }}
                                {{ $task->category === 'exception' ? 'bg-rose-100 text-rose-800 hover:bg-rose-200' : '' }}
                                {{ $task->category === 'kontrak_exp' ? 'bg-purple-100 text-purple-800 hover:bg-purple-200' : '' }}">
                                {{ $task->category_label }}
                            </a>
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

