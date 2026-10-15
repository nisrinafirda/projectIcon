<x-layouts.app>
    <x-slot:title>Import Data Tugas Massal — ICON</x-slot:title>
    <x-slot:pageHeading>Import Data Tugas Massal</x-slot:pageHeading>

    <div class="max-w-4xl mx-auto space-y-6">
        <!-- QA Answer Banner: Menjawab Pertanyaan User -->
        <div class="p-6 rounded-2xl bg-gradient-to-r from-blue-900 to-indigo-900 text-white shadow-md shadow-blue-950/20">
            <div class="flex items-start gap-3">
                <span class="text-2xl">💡</span>
                <div>
                    <h3 class="text-base font-bold text-white">Import Ribuan Data Sekaligus — Mendukung File Asli Monitoring PPB SBU & Template ICON</h3>
                    <p class="text-xs text-blue-100/90 mt-1 leading-relaxed">
                        <strong>Fitur Pintar:</strong> Anda bisa langsung mengunggah file spreadsheet dari <strong>Project Monitoring PPB 2026 (BAA Open, BAI Open, SO Open, Exception, Contract Expired)</strong> tanpa perlu mengubah susunan kolom! Sistem secara otomatis memetakan kolom (ID PA, Pelanggan, Sales, KP, Harga, Tanggal) dan mendistribusikannya ke karyawan masing-masing.
                    </p>
                </div>
            </div>
        </div>

        @if($selectedCategory)
        <div class="p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-950 text-xs flex items-center justify-between gap-3 shadow-2xs">
            <div class="flex items-center gap-2.5">
                <span class="text-lg">📁</span>
                <div>
                    <span class="font-bold text-blue-900">Mode Import Khusus: {{ $selectedCategoryLabel }}</span>
                    <p class="text-[11px] text-blue-700 mt-0.5">Semua baris tugas yang diimpor akan otomatis masuk ke kategori <strong>{{ $selectedCategoryLabel }}</strong>.</p>
                </div>
            </div>
            <a href="{{ route('tasks.import.view') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800 underline shrink-0">
                Pilih Kategori Lain
            </a>
        </div>
        @endif

        @if(session('import_errors') && count(session('import_errors')) > 0)
        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs">
            <h4 class="font-bold mb-1">Catatan Import Baris:</h4>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach(session('import_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Step 1: Download Template -->
        <div class="theme-card p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="text-[10px] font-bold tracking-wider uppercase px-2 py-0.5 rounded bg-[var(--primary-soft)] text-blue-600">Langkah 1</span>
                <h3 class="text-base font-bold text-[var(--text)] mt-1">Unduh Template Excel Resmi (.xlsx)</h3>
                <p class="text-xs text-[var(--text-muted)] mt-0.5">
                    {{ $selectedCategory ? "Template telah disesuaikan khusus untuk kategori {$selectedCategoryLabel} dan daftar nama karyawan aktif." : 'Format tabel telah disesuaikan dengan 5 kategori ICON (SO Open, BAA, BAI, Exception, Kontrak Exp).' }}
                </p>
            </div>
            <a href="{{ route('tasks.import.template', $selectedCategory ? ['category' => $selectedCategory] : []) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition-colors shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Unduh Template Excel {{ $selectedCategory ? "({$selectedCategoryLabel})" : '' }}
            </a>
        </div>

        <!-- Step 2: Upload & Anti-Duplicate Configuration -->
        <div class="theme-card overflow-hidden">
            <div class="p-6 border-b border-[var(--border)]">
                <span class="text-[10px] font-bold tracking-wider uppercase px-2 py-0.5 rounded bg-[var(--primary-soft)] text-blue-600">Langkah 2</span>
                <h3 class="text-base font-bold text-[var(--text)] mt-1">Unggah File & Pengaturan Anti-Duplikasi</h3>
                <p class="text-xs text-[var(--text-muted)] mt-0.5">Pilih cara sistem menangani nomor dokumen yang sudah pernah terdaftar di database.</p>
            </div>

            <form method="POST" action="{{ route('tasks.import.process', $selectedCategory ? ['category' => $selectedCategory] : []) }}" enctype="multipart/form-data" class="p-6 space-y-6">
                @csrf
                @if($selectedCategory)
                    <input type="hidden" name="source_category" value="{{ $selectedCategory }}">
                @endif

                <!-- Target Category Selector -->
                <div>
                    <label for="target_category" class="block text-xs font-bold text-[var(--text)] uppercase tracking-wider mb-1.5">
                        Target Modul Kategori Tugas @if($selectedCategory)<span class="text-rose-500">*</span>@endif
                    </label>
                    <div class="relative">
                        <select id="target_category" name="target_category" {{ $selectedCategory ? 'required' : '' }}
                            class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-[var(--text)] text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all font-medium">
                            @if(! $selectedCategory)
                                <option value="">-- Deteksi Otomatis dari Kolom Kategori di Excel --</option>
                            @endif
                            @foreach($categories as $catKey => $catLabel)
                                <option value="{{ $catKey }}" {{ old('target_category', $selectedCategory) === $catKey ? 'selected' : '' }}>
                                    📌 Khusus Modul {{ $catLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @error('target_category')
                        <p class="mt-1.5 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                    @enderror
                    <p class="text-[11px] text-[var(--text-muted)] mt-1">
                        @if($selectedCategory)
                            Import dibuka khusus untuk modul <strong>{{ $selectedCategoryLabel }}</strong>. Seluruh baris data di file Excel harus sesuai dengan modul ini.
                        @else
                            Jika dipilih (misal <strong>SO Open</strong>), seluruh baris data di file Excel akan otomatis dimasukkan ke modul tersebut tanpa perlu edit kolom kategori lagi.
                        @endif
                    </p>
                </div>

                <!-- File Input -->
                <div>
                    <label for="file" class="block text-xs font-bold text-[var(--text)] uppercase tracking-wider mb-2">
                        Pilih File Spreadsheet (.xlsx, .xls, .csv) <span class="text-rose-500">*</span>
                    </label>
                    <div class="p-6 border-2 border-dashed border-[var(--border)] hover:border-blue-500 rounded-2xl bg-[var(--surface-2)]/60 text-center transition-colors">
                        <svg class="w-10 h-10 text-blue-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                        <input type="file" id="file" name="file" required accept=".xlsx,.xls,.csv"
                            class="text-xs text-[var(--text-muted)] file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer">
                        <p class="text-[11px] text-[var(--text-muted)] mt-2">Mendukung format Microsoft Excel (.xlsx) dan Comma Separated Values (.csv)</p>
                    </div>
                    @error('file')
                        <p class="mt-1.5 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Anti-Duplicate Policy -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-[var(--text)] uppercase tracking-wider">
                        Kebijakan Penanganan Data Duplikat <span class="text-rose-500">*</span>
                    </label>
                    <p class="text-xs text-[var(--text-muted)] mb-3">Tentukan tindakan jika ditemukan baris dengan Nomor Dokumen yang sudah ada di sistem:</p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <label class="flex items-start gap-3 p-3.5 rounded-xl border border-[var(--border)] hover:border-blue-500 bg-[var(--surface-2)] cursor-pointer transition-colors">
                            <input type="radio" name="duplicate_action" value="skip" checked class="mt-0.5 text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="block text-xs font-bold text-[var(--text)]">🛡️ Lewati (Skip)</span>
                                <span class="text-[11px] text-[var(--text-muted)]">Aman: Data yang sudah ada dibiarkan, hanya data baru yang dimasukkan.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3.5 rounded-xl border border-[var(--border)] hover:border-blue-500 bg-[var(--surface-2)] cursor-pointer transition-colors">
                            <input type="radio" name="duplicate_action" value="update" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="block text-xs font-bold text-[var(--text)]">🔄 Perbarui (Update)</span>
                                <span class="text-[11px] text-[var(--text-muted)]">Timpa data lama jika nomor dokumen sama dengan data terbaru di Excel.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3.5 rounded-xl border border-[var(--border)] hover:border-blue-500 bg-[var(--surface-2)] cursor-pointer transition-colors">
                            <input type="radio" name="duplicate_action" value="fail" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="block text-xs font-bold text-[var(--text)]">⛔ Batalkan Import</span>
                                <span class="text-[11px] text-[var(--text-muted)]">Batalkan seluruh proses jika ditemukan ada nomor dokumen ganda.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Fallback User Assignee -->
                <div>
                    <label for="default_user_id" class="block text-xs font-bold text-[var(--text)] uppercase tracking-wider mb-1.5">
                        Karyawan Default (Jika Kolom Nama/PIC di Excel Kosong atau Tidak Dikenali)
                    </label>
                    <select id="default_user_id" name="default_user_id"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-[var(--text)] text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Tugaskan ke Admin yang Mengimpor --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->nip ?: $emp->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="pt-4 border-t border-[var(--border)] flex items-center justify-end">
                    <button type="submit"
                        class="px-6 py-2.5 rounded-xl theme-btn-primary text-white font-bold text-sm shadow-md transition-all flex items-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Mulai Proses Import Data
                    </button>
                </div>
            </form>
        </div>

        <!-- Panduan Kolom Excel -->
        <div class="theme-card p-6">
            <h3 class="text-sm font-bold text-[var(--text)] mb-1">Struktur Kolom Template Excel</h3>
            <p class="text-xs text-[var(--text-muted)] mb-3">1 file Excel dapat berisi berbagai tugas untuk karyawan yang berbeda-beda sekaligus:</p>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-[var(--text)]">
                    <thead class="bg-[var(--surface-2)] text-[var(--text-muted)] font-semibold border-b border-[var(--border)]">
                        <tr>
                            <th class="py-2.5 px-3">Kolom</th>
                            <th class="py-2.5 px-3">Nama Header</th>
                            <th class="py-2.5 px-3">Wajib?</th>
                            <th class="py-2.5 px-3">Contoh Nilai</th>
                            <th class="py-2.5 px-3">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        <tr>
                            <td class="py-2.5 px-3 font-mono font-bold text-blue-500">A</td>
                            <td class="py-2.5 px-3 font-semibold">Kategori</td>
                            <td class="py-2.5 px-3 text-amber-500 font-bold">Opsional jika Target Modul dipilih</td>
                            <td class="py-2.5 px-3 font-mono text-[var(--text)]">sso_open / baa / bai / exception / kontrak_exp</td>
                            <td class="py-2.5 px-3 text-[var(--text-muted)]">Jika Target Modul di form telah dipilih (misal SO Open), kolom ini otomatis mengikuti pilihan tersebut.</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-3 font-mono font-bold text-blue-500">B</td>
                            <td class="py-2.5 px-3 font-semibold">Nomor Dokumen</td>
                            <td class="py-2.5 px-3 text-rose-500 font-bold">Ya (Unik)</td>
                            <td class="py-2.5 px-3 font-mono text-[var(--text)]">SO-2026-101 / BAA-2026-901</td>
                            <td class="py-2.5 px-3 text-[var(--text-muted)]">Identitas unik dokumen per kategori</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-3 font-mono font-bold text-blue-500">C</td>
                            <td class="py-2.5 px-3 font-semibold">Judul Tugas</td>
                            <td class="py-2.5 px-3 text-rose-500 font-bold">Ya</td>
                            <td class="py-2.5 px-3 text-[var(--text)]">Aktivasi Penambahan Bandwidth IP Transit</td>
                            <td class="py-2.5 px-3 text-[var(--text-muted)]">Nama atau ringkasan tugas pengerjaan</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-3 font-mono font-bold text-emerald-500">D</td>
                            <td class="py-2.5 px-3 font-semibold text-[var(--text)]">Nama / NIP / Email Karyawan PIC</td>
                            <td class="py-2.5 px-3 text-emerald-500 font-bold">Bisa Nama Langsung!</td>
                            <td class="py-2.5 px-3 font-semibold text-emerald-500">Siti Rahma / Budi Santoso / ahmad@icon.co.id / NIP101</td>
                            <td class="py-2.5 px-3 font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 rounded">✨ Otomatis dipisah dan dimasukkan ke akun masing-masing karyawan sesuai nama yang tertulis!</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-3 font-mono font-bold text-blue-500">E</td>
                            <td class="py-2.5 px-3 font-semibold">Nama Pelanggan</td>
                            <td class="py-2.5 px-3 text-[var(--text-muted)]">Opsional</td>
                            <td class="py-2.5 px-3 text-[var(--text)]">PT Telco Mandiri / PLN UID</td>
                            <td class="py-2.5 px-3 text-[var(--text-muted)]">Nama customer atau site terkait</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-3 font-mono font-bold text-blue-500">F</td>
                            <td class="py-2.5 px-3 font-semibold">Jenis Layanan</td>
                            <td class="py-2.5 px-3 text-[var(--text-muted)]">Opsional</td>
                            <td class="py-2.5 px-3 text-[var(--text)]">Metronet 1Gbps / IP VPN / Dark Fiber</td>
                            <td class="py-2.5 px-3 text-[var(--text-muted)]">Produk / connectivity ICON</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-3 font-mono font-bold text-blue-500">G</td>
                            <td class="py-2.5 px-3 font-semibold">Prioritas</td>
                            <td class="py-2.5 px-3 text-[var(--text-muted)]">Opsional</td>
                            <td class="py-2.5 px-3 font-mono text-[var(--text)]">low / medium / high / urgent</td>
                            <td class="py-2.5 px-3 text-[var(--text-muted)]">Default: medium</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-3 font-mono font-bold text-blue-500">I</td>
                            <td class="py-2.5 px-3 font-semibold">Deadline</td>
                            <td class="py-2.5 px-3 text-rose-500 font-bold">Ya</td>
                            <td class="py-2.5 px-3 font-mono text-[var(--text)]">2026-09-16</td>
                            <td class="py-2.5 px-3 text-[var(--text-muted)]">Format tanggal: YYYY-MM-DD</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Panduan Format File Monitoring SBU SharePoint -->
        <div class="theme-card p-6">
            <div class="flex items-center gap-2 mb-2">
                <span class="text-xl">✨</span>
                <h3 class="text-sm font-bold text-[var(--text)]">Mendukung File Asli "Project Monitoring PPB 2026" (SharePoint SBU)</h3>
            </div>
            <p class="text-xs text-[var(--text-muted)] mb-3">
                Tidak perlu memindah atau mengubah format kolom! Sistem otomatis mendeteksi header tabel operasional SBU dan memetakannya langsung:
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                <div class="p-3.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] space-y-1.5 shadow-2xs">
                    <span class="font-bold text-blue-500 block">📊 Pemetaan Otomatis Kolom SBU:</span>
                    <ul class="space-y-1 text-[var(--text)] list-disc list-inside">
                        <li><strong>ID PA / SID / ID Pelanggan</strong> &rarr; Nomor Dokumen (Unik)</li>
                        <li><strong>NAMA PELANGGAN</strong> &rarr; Pelanggan / Site</li>
                        <li><strong>LAYANAN PRODUK</strong> &rarr; Jenis Layanan</li>
                        <li><strong>KP (Surabaya/Malang/dll)</strong> &rarr; Wilayah KP Task</li>
                        <li><strong>Kategori Customer</strong> &rarr; Segmen (Publik / PLN)</li>
                        <li><strong>Sales / PIC</strong> &rarr; Otomatis assign ke User ICON</li>
                    </ul>
                </div>
                <div class="p-3.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] space-y-1.5 shadow-2xs">
                    <span class="font-bold text-blue-500 block">💼 Data Tambahan yang Dirangkum:</span>
                    <ul class="space-y-1 text-[var(--text)] list-disc list-inside">
                        <li><strong>Harga Lama / Harga Baru / Selisih</strong> &rarr; Dirangkum rapi ke Keterangan / Deskripsi</li>
                        <li><strong>Kendala / Alasan / Konfirmasi</strong> &rarr; Otomatis digabung ke Deskripsi</li>
                        <li><strong>Tanggal Aging / Upload</strong> &rarr; Tanggal Mulai</li>
                        <li><strong>Target Hari / Aktivasi</strong> &rarr; Tanggal Deadline</li>
                        <li><strong>Status (Done / On Process)</strong> &rarr; Status Tugas</li>
                    </ul>
                </div>
            </div>
            <p class="text-[11px] text-blue-500 mt-3 italic">
                Tips: Jika file Excel memiliki banyak sheet (1.1 SO Open, 1.2 SO Open, 2 BAI Open, 3 BAA Open, 4 Exception, 5 Contract Expired), pastikan sheet yang ingin diimpor berada di posisi aktif atau tentukan "Target Modul Kategori Tugas" di formulir atas.
            </p>
        </div>
    </div>
</x-layouts.app>

