<x-layouts.app>
    <x-slot:title>Import Data Tugas Massal — ICON</x-slot:title>
    <x-slot:pageHeading>Import Data Tugas Massal</x-slot:pageHeading>

    <div class="max-w-4xl mx-auto space-y-6">
        <!-- QA Answer Banner: Menjawab Pertanyaan User -->
        <div class="p-6 rounded-2xl bg-gradient-to-r from-blue-900 to-indigo-900 text-white shadow-md shadow-blue-950/20">
            <div class="flex items-start gap-3">
                <span class="text-2xl">💡</span>
                <div>
                    <h3 class="text-base font-bold text-white">Import Ribuan Data Sekaligus Langsung Terhubung ke Karyawan Masing-Masing</h3>
                    <p class="text-xs text-blue-100/90 mt-1 leading-relaxed">
                        <strong>Pertanyaan:</strong> <em>"Datanya ada banyak sedangkan per orang ada yang pegang sendiri-sendiri, apakah bisa kita masukkan data awal dan langsung sesuai dengan user yang kita mau?"</em><br>
                        <strong>Jawaban:</strong> <strong>BISA SEKALI!</strong> Anda cukup menyertakan <u>NIP atau Email Karyawan</u> pada kolom template Excel. Sistem secara otomatis mencocokkan setiap baris tugas ke akun karyawan yang bersangkutan.
                    </p>
                </div>
            </div>
        </div>

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
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="text-[10px] font-bold tracking-wider uppercase px-2 py-0.5 rounded bg-blue-100 text-blue-800">Langkah 1</span>
                <h3 class="text-base font-bold text-slate-900 mt-1">Unduh Template Excel Resmi (.xlsx)</h3>
                <p class="text-xs text-slate-500 mt-0.5">Format tabel telah disesuaikan dengan 5 kategori ICON (SSO Open, BAA, BAI, Exception, Kontrak Exp).</p>
            </div>
            <a href="{{ route('tasks.import.template') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition-colors shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Unduh Template Excel
            </a>
        </div>

        <!-- Step 2: Upload & Anti-Duplicate Configuration -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-6 border-b border-slate-100">
                <span class="text-[10px] font-bold tracking-wider uppercase px-2 py-0.5 rounded bg-blue-100 text-blue-800">Langkah 2</span>
                <h3 class="text-base font-bold text-slate-900 mt-1">Unggah File & Pengaturan Anti-Duplikasi</h3>
                <p class="text-xs text-slate-500 mt-0.5">Pilih cara sistem menangani nomor dokumen yang sudah pernah terdaftar di database.</p>
            </div>

            <form method="POST" action="{{ route('tasks.import.process') }}" enctype="multipart/form-data" class="p-6 space-y-6">
                @csrf

                <!-- File Input -->
                <div>
                    <label for="file" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Pilih File Spreadsheet (.xlsx, .xls, .csv) <span class="text-rose-500">*</span>
                    </label>
                    <div class="p-6 border-2 border-dashed border-blue-200 hover:border-blue-500 rounded-2xl bg-blue-50/20 text-center transition-colors">
                        <svg class="w-10 h-10 text-blue-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                        <input type="file" id="file" name="file" required accept=".xlsx,.xls,.csv"
                            class="text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer">
                        <p class="text-[11px] text-slate-400 mt-2">Mendukung format Microsoft Excel (.xlsx) dan Comma Separated Values (.csv)</p>
                    </div>
                    @error('file')
                        <p class="mt-1.5 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Anti-Duplicate Policy -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Kebijakan Penanganan Data Duplikat <span class="text-rose-500">*</span>
                    </label>
                    <p class="text-xs text-slate-500 mb-3">Tentukan tindakan jika ditemukan baris dengan Nomor Dokumen yang sudah ada di sistem:</p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 hover:border-blue-500 bg-slate-50/50 cursor-pointer transition-colors">
                            <input type="radio" name="duplicate_action" value="skip" checked class="mt-0.5 text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="block text-xs font-bold text-slate-900">🛡️ Lewati (Skip)</span>
                                <span class="text-[11px] text-slate-500">Aman: Data yang sudah ada dibiarkan, hanya data baru yang dimasukkan.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 hover:border-blue-500 bg-slate-50/50 cursor-pointer transition-colors">
                            <input type="radio" name="duplicate_action" value="update" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="block text-xs font-bold text-slate-900">🔄 Perbarui (Update)</span>
                                <span class="text-[11px] text-slate-500">Timpa data lama jika nomor dokumen sama dengan data terbaru di Excel.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 hover:border-blue-500 bg-slate-50/50 cursor-pointer transition-colors">
                            <input type="radio" name="duplicate_action" value="fail" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="block text-xs font-bold text-slate-900">⛔ Batalkan Import</span>
                                <span class="text-[11px] text-slate-500">Batalkan seluruh proses jika ditemukan ada nomor dokumen ganda.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Fallback User Assignee -->
                <div>
                    <label for="default_user_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Karyawan Default (Jika Kolom PIC di Excel Kosong)
                    </label>
                    <select id="default_user_id" name="default_user_id"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Tugaskan ke Admin yang Mengimpor --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->nip ?: $emp->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                    <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-md shadow-blue-500/20 transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Mulai Proses Import Data
                    </button>
                </div>
            </form>
        </div>

        <!-- Panduan Kolom Excel -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
            <h3 class="text-sm font-bold text-slate-900 mb-3">Struktur Kolom Template Excel</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-3">Kolom</th>
                            <th class="py-2.5 px-3">Nama Header</th>
                            <th class="py-2.5 px-3">Wajib?</th>
                            <th class="py-2.5 px-3">Contoh Nilai</th>
                            <th class="py-2.5 px-3">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr>
                            <td class="py-2.5 px-3 font-mono font-bold text-blue-600">A</td>
                            <td class="py-2.5 px-3 font-semibold">Kategori</td>
                            <td class="py-2.5 px-3 text-rose-600 font-bold">Ya</td>
                            <td class="py-2.5 px-3 font-mono text-slate-700">baa / bai / sso_open / exception / kontrak_exp</td>
                            <td class="py-2.5 px-3">Pilih salah satu dari 5 kategori ICON</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-3 font-mono font-bold text-blue-600">B</td>
                            <td class="py-2.5 px-3 font-semibold">Nomor Dokumen</td>
                            <td class="py-2.5 px-3 text-rose-600 font-bold">Ya (Unik)</td>
                            <td class="py-2.5 px-3 font-mono text-slate-700">BAA-2026-901</td>
                            <td class="py-2.5 px-3">Identitas unik dokumen (mencegah data dobel)</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-3 font-mono font-bold text-blue-600">C</td>
                            <td class="py-2.5 px-3 font-semibold">Judul Tugas</td>
                            <td class="py-2.5 px-3 text-rose-600 font-bold">Ya</td>
                            <td class="py-2.5 px-3 text-slate-700">BAA Aktivasi Link Fiber Optik</td>
                            <td class="py-2.5 px-3">Nama atau ringkasan tugas</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-3 font-mono font-bold text-blue-600">D</td>
                            <td class="py-2.5 px-3 font-semibold">NIP / Email Karyawan</td>
                            <td class="py-2.5 px-3 text-slate-500 font-semibold">Opsional</td>
                            <td class="py-2.5 px-3 font-mono text-slate-700">ahmad@icon.co.id / NIP101</td>
                            <td class="py-2.5 px-3 font-semibold text-blue-600">Otomatis langsung ditugaskan ke karyawan ini!</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-3 font-mono font-bold text-blue-600">E</td>
                            <td class="py-2.5 px-3 font-semibold">Nama Pelanggan</td>
                            <td class="py-2.5 px-3 text-slate-500">Opsional</td>
                            <td class="py-2.5 px-3 text-slate-700">PT Telco Mandiri / PLN UID</td>
                            <td class="py-2.5 px-3">Nama customer atau site terkait</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-3 font-mono font-bold text-blue-600">F</td>
                            <td class="py-2.5 px-3 font-semibold">Jenis Layanan</td>
                            <td class="py-2.5 px-3 text-slate-500">Opsional</td>
                            <td class="py-2.5 px-3 text-slate-700">Metronet 1Gbps / IP VPN / Dark Fiber</td>
                            <td class="py-2.5 px-3">Produk / connectivity ICON</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-3 font-mono font-bold text-blue-600">G</td>
                            <td class="py-2.5 px-3 font-semibold">Prioritas</td>
                            <td class="py-2.5 px-3 text-slate-500">Opsional</td>
                            <td class="py-2.5 px-3 font-mono text-slate-700">low / medium / high / urgent</td>
                            <td class="py-2.5 px-3">Default: medium</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-3 font-mono font-bold text-blue-600">I</td>
                            <td class="py-2.5 px-3 font-semibold">Deadline</td>
                            <td class="py-2.5 px-3 text-rose-600 font-bold">Ya</td>
                            <td class="py-2.5 px-3 font-mono text-slate-700">2026-09-16</td>
                            <td class="py-2.5 px-3">Format tanggal: YYYY-MM-DD</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>

