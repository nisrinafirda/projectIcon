<x-layouts.app>
    <x-slot:title>{{ $task->document_number }} — {{ $task->title }}</x-slot:title>
    <x-slot:pageHeading>Rincian Tugas {{ $task->category_label }}</x-slot:pageHeading>

    <div class="max-w-5xl mx-auto space-y-6">
        <!-- Top Navigation -->
        <div class="flex items-center justify-between">
            <a href="{{ route('tasks.category', $task->category) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-800">
                &larr; Kembali ke {{ $task->category_label }}
            </a>

            @if(auth()->user()->isAdmin())
            <div class="flex items-center gap-2">
                <a href="{{ route('tasks.edit', $task) }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                    Edit Tugas
                </a>
                <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus tugas ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-semibold transition-colors">
                        Hapus
                    </button>
                </form>
            </div>
            @endif
        </div>

        <!-- Task Main Header Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Category Badge -->
                        <span class="px-2.5 py-1 rounded-md text-xs font-extrabold uppercase
                            {{ $task->category === 'sso_open' ? 'bg-cyan-100 text-cyan-800' : '' }}
                            {{ $task->category === 'baa' ? 'bg-amber-100 text-amber-800' : '' }}
                            {{ $task->category === 'bai' ? 'bg-emerald-100 text-emerald-800' : '' }}
                            {{ $task->category === 'exception' ? 'bg-rose-100 text-rose-800' : '' }}
                            {{ $task->category === 'kontrak_exp' ? 'bg-purple-100 text-purple-800' : '' }}">
                            {{ $task->category_label }}
                        </span>

                        <!-- Document Number -->
                        <span class="px-2.5 py-1 rounded-md bg-slate-100 font-mono text-xs font-bold text-slate-800">
                            {{ $task->document_number }}
                        </span>

                        <!-- Priority Badge -->
                        <span class="px-2 py-0.5 rounded text-[11px] font-bold uppercase
                            {{ $task->priority === 'urgent' ? 'bg-rose-100 text-rose-700' : '' }}
                            {{ $task->priority === 'high' ? 'bg-orange-100 text-orange-700' : '' }}
                            {{ $task->priority === 'medium' ? 'bg-blue-100 text-blue-700' : '' }}
                            {{ $task->priority === 'low' ? 'bg-slate-100 text-slate-600' : '' }}">
                            Prioritas: {{ $task->priority }}
                        </span>

                        @if($task->kp)
                        <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            🏢 KP {{ $task->kp_label }}
                        </span>
                        @endif

                        @if($task->kategori_segmen)
                        <span class="px-2 py-0.5 rounded text-[11px] font-bold {{ $task->kategori_segmen === 'pln' ? 'bg-sky-100 text-sky-700' : 'bg-emerald-100 text-emerald-700' }}">
                            🏷️ {{ $task->kategori_segmen_label }}
                        </span>
                        @endif
                    </div>

                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 leading-tight">
                        {{ $task->title }}
                    </h1>

                    @if($task->customer_name || $task->service_type)
                    <div class="flex flex-wrap items-center gap-4 text-xs text-slate-600 pt-1">
                        @if($task->customer_name)
                            <span class="flex items-center gap-1 font-medium">
                                🏢 Pelanggan: <strong>{{ $task->customer_name }}</strong>
                            </span>
                        @endif
                        @if($task->service_type)
                            <span class="flex items-center gap-1 font-medium">
                                🌐 Layanan: <strong>{{ $task->service_type }}</strong>
                            </span>
                        @endif
                    </div>
                    @endif
                </div>

                <!-- Status Pill Box -->
                <div class="text-left md:text-right shrink-0">
                    <span class="inline-block px-3.5 py-1.5 rounded-full text-xs font-bold
                        {{ $task->status === 'pending' ? 'bg-slate-100 text-slate-700' : '' }}
                        {{ $task->status === 'in_progress' ? 'bg-blue-100 text-blue-700' : '' }}
                        {{ $task->status === 'submitted' ? 'bg-purple-100 text-purple-700' : '' }}
                        {{ $task->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : '' }}
                        {{ $task->status === 'rejected' ? 'bg-rose-100 text-rose-700' : '' }}">
                        {{ $task->status_label }}
                    </span>
                    <p class="text-[11px] text-slate-400 mt-1">Dibuat: {{ $task->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            <!-- Description -->
            @if($task->description)
            <div class="mt-6 pt-5 border-t border-slate-100">
                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Instruksi Pengerjaan</h4>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60 text-sm text-slate-800 whitespace-pre-line">
                    {{ $task->description }}
                </div>
            </div>
            @endif
        </div>

        <!-- Task Metadata Grid: PIC, Dates, Timeline -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- PIC Card -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Karyawan Penanggung Jawab</p>
                @if($task->user)
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-sm shadow-xs">
                        {{ strtoupper(substr($task->user->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-900">{{ $task->user->name }}</p>
                        <p class="text-xs text-slate-500">{{ $task->user->nip ? 'NIP: '.$task->user->nip : $task->user->email }}</p>
                        @if($task->user->department)
                            <p class="text-[11px] text-blue-600">{{ $task->user->department }}</p>
                        @endif
                    </div>
                </div>
                @else
                <p class="text-xs text-slate-400 italic">Belum ditugaskan ke karyawan</p>
                @endif
            </div>

            <!-- Due Date Card -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Batas Waktu (Deadline)</p>
                <div class="space-y-1">
                    <p class="text-base font-extrabold {{ $task->is_overdue ? 'text-rose-600' : ($task->is_due_soon ? 'text-amber-600' : 'text-slate-900') }}">
                        {{ $task->due_date ? $task->due_date->translatedFormat('l, d F Y') : 'Tidak ditentukan' }}
                    </p>
                    @if($task->status !== 'approved' && $task->due_date)
                    <p class="text-xs font-semibold {{ $task->is_overdue ? 'text-rose-600' : ($task->is_due_soon ? 'text-amber-600' : 'text-slate-500') }}">
                        @if($task->is_overdue)
                            🚨 Telah melewati batas waktu ({{ $task->due_date->diffForHumans() }})
                        @elseif($task->is_due_soon)
                            ⚠️ Deadline Segera! ({{ $task->due_date->diffForHumans() }})
                        @else
                            ⏳ {{ $task->due_date->diffForHumans() }}
                        @endif
                    </p>
                    @endif
                </div>
            </div>

            <!-- Assigner Card -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Diberikan Oleh</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-slate-800 text-amber-400 font-bold flex items-center justify-center text-sm">
                        ⚡
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-900">{{ $task->assigner ? $task->assigner->name : 'Administrator' }}</p>
                        <p class="text-xs text-slate-500">Mulai: {{ $task->start_date ? $task->start_date->format('d/m/Y') : '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submission History & Feedback (If already submitted, approved, or rejected) -->
        @if($task->submission_notes || $task->admin_notes)
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
            <h3 class="text-sm font-bold text-slate-900">Riwayat Penyerahan & Verifikasi</h3>

            @if($task->submission_notes)
            <div class="p-4 rounded-xl bg-purple-50/50 border border-purple-200/60 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-purple-900">Catatan Pengerjaan Karyawan:</span>
                    <span class="text-slate-400">{{ $task->completed_at ? $task->completed_at->format('d/m/Y H:i') : '' }}</span>
                </div>
                <p class="text-sm text-slate-800 whitespace-pre-line">{{ $task->submission_notes }}</p>

                @if($task->submission_attachment)
                <div class="pt-2">
                    <a href="{{ asset('storage/'.$task->submission_attachment) }}" target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-purple-200 text-xs font-semibold text-purple-700 hover:bg-purple-50 shadow-2xs">
                        📎 Unduh Lampiran Bukti
                    </a>
                </div>
                @endif
            </div>
            @endif

            @if($task->admin_notes)
            <div class="p-4 rounded-xl {{ $task->status === 'approved' ? 'bg-emerald-50/60 border-emerald-200 text-emerald-950' : 'bg-rose-50/60 border-rose-200 text-rose-950' }} border space-y-1">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold {{ $task->status === 'approved' ? 'text-emerald-800' : 'text-rose-800' }}">
                        {{ $task->status === 'approved' ? '✅ Catatan Persetujuan Admin:' : '❌ Catatan Revisi Admin:' }}
                    </span>
                    <span class="text-slate-400">{{ $task->reviewed_at ? $task->reviewed_at->format('d/m/Y H:i') : '' }}</span>
                </div>
                <p class="text-sm whitespace-pre-line">{{ $task->admin_notes }}</p>
            </div>
            @endif
        </div>
        @endif

        <!-- WORKFLOW ACTIONS -->

        <!-- 1. ADMIN ACTIONS: APPROVE OR REJECT (IF USER SUBMITTED) -->
        @if(auth()->user()->isAdmin())
        <div class="bg-white rounded-2xl border-2 border-blue-600/30 shadow-md p-6">
            <div class="flex items-center gap-2 mb-4">
                <span class="p-1 rounded-lg bg-blue-100 text-blue-700 font-bold">⚡</span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Panel Keputusan Administrator</h3>
                    <p class="text-xs text-slate-500">Anda dapat menerima tugas ini atau menolaknya untuk diperbaiki oleh karyawan.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('tasks.review', $task) }}" class="space-y-4">
                @csrf

                <div>
                    <label for="admin_notes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Catatan Verifikasi Admin (Opsional / Alasan Revisi)
                    </label>
                    <textarea id="admin_notes" name="admin_notes" rows="3"
                        placeholder="Berikan apresiasi jika disetujui, atau tuliskan poin-poin yang perlu diperbaiki jika ditolak..."
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('admin_notes', $task->admin_notes) }}</textarea>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <button type="submit" name="action" value="approve"
                        class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-sm transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Setujui Tugas (Approve & Selesai)
                    </button>

                    <button type="submit" name="action" value="reject"
                        class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm shadow-sm transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Tolak Tugas (Minta Revisi)
                    </button>
                </div>
            </form>
        </div>
        @endif

        <!-- 2. USER ACTIONS: SUBMIT WORK (IF ASSIGNED TO CURRENT USER) -->
        @if(!auth()->user()->isAdmin() && $task->user_id === auth()->id())
            @if($task->status === 'pending')
            <div class="bg-blue-50 rounded-2xl border border-blue-200 p-6 flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-bold text-blue-900">Mulai Mengerjakan Tugas Ini?</h4>
                    <p class="text-xs text-blue-700 mt-0.5">Tandai bahwa Anda sedang aktif memproses tugas ini.</p>
                </div>
                <form method="POST" action="{{ route('tasks.start', $task) }}">
                    @csrf
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition-all">
                        Mulai Kerjakan
                    </button>
                </form>
            </div>
            @endif

            @if(in_array($task->status, ['pending', 'in_progress', 'rejected']))
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
                <h3 class="text-base font-bold text-slate-900 mb-1">Kirim Hasil Pengerjaan Tugas</h3>
                <p class="text-xs text-slate-500 mb-4">Jika tugas telah selesai dikerjakan, berikan keterangan dan lampiran untuk ditinjau oleh Admin.</p>

                <form method="POST" action="{{ route('tasks.submit', $task) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <label for="submission_notes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Catatan Pengerjaan / Penjelasan <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="submission_notes" name="submission_notes" rows="3" required
                            placeholder="Jelaskan tindakan yang sudah dilakukan, nomor referensi dokumen, hasil pengujian, dll..."
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('submission_notes') }}</textarea>
                    </div>

                    <div>
                        <label for="attachment" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Upload Bukti Dokumen / Scan / Foto (Opsional)
                        </label>
                        <input type="file" id="attachment" name="attachment"
                            class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-[11px] text-slate-400 mt-1">Format: PDF, PNG, JPG, ZIP, DOCX, XLSX (Maks. 10MB)</p>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-sm shadow-md shadow-blue-500/20 transition-all">
                            Serahkan Tugas ke Admin untuk Diverifikasi &rarr;
                        </button>
                    </div>
                </form>
            </div>
            @endif
        @endif
    </div>
</x-layouts.app>

