<x-layouts.app>
    <x-slot:title>Beri Tugas Baru — ICON</x-slot:title>
    <x-slot:pageHeading>Beri Tugas Karyawan Baru</x-slot:pageHeading>

    <div class="max-w-4xl mx-auto">
        <!-- Anti-Duplicate Feature Explainer Badge -->
        <div class="mb-6 p-4 rounded-2xl bg-blue-50 border border-blue-200 text-blue-900 flex items-start gap-3 shadow-xs">
            <span class="text-xl">🛡️</span>
            <div class="text-xs">
                <strong class="block text-sm font-bold text-blue-900">Fitur Pencegahan Data Ganda (Anti-Duplicate System)</strong>
                Sistem secara otomatis mengecek keunikan nomor dokumen/tiket pada kategori yang dipilih. Jika nomor dokumen sudah pernah didaftarkan sebelumnya, sistem akan mendeteksi secara realtime dan mencegah data ganda masuk ke database.
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-6 border-b border-slate-100">
                <h2 class="text-lg font-bold text-slate-900">Form Penugasan Pekerjaan</h2>
                <p class="text-xs text-slate-500 mt-0.5">Pilih kategori modul dan tentukan karyawan yang bertanggung jawab.</p>
            </div>

            <form method="POST" action="{{ route('tasks.store') }}" class="p-6 space-y-6">
                @csrf

                <!-- 1. Kategori & Nomor Dokumen (with Live Duplicate Checker) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="category" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Kategori Tugas <span class="text-rose-500">*</span>
                        </label>
                        <select id="category" name="category" required onchange="triggerDuplicateCheck()"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all font-semibold">
                            <option value="">-- Pilih Kategori Modul --</option>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}" {{ old('category', request('category')) === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('category')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="document_number" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Nomor Dokumen / Tiket Unik <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="text" id="document_number" name="document_number" value="{{ old('document_number') }}" required
                                oninput="debounceDuplicateCheck()"
                                placeholder="Contoh: BAA-2026-101 / SSO-9821"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                            <div id="duplicate-spinner" class="absolute right-3 top-3 hidden">
                                <span class="w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full inline-block animate-spin"></span>
                            </div>
                        </div>

                        <!-- Live Duplicate Feedback Area -->
                        <div id="duplicate-feedback" class="mt-2 text-xs hidden"></div>

                        @error('document_number')
                            <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- 2. Judul Tugas & Karyawan PIC -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="title" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Judul / Perihal Tugas <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="title" name="title" value="{{ old('title') }}" required
                            placeholder="cth: Penyelesaian Dokumen BAA Aktivasi Link Fiber Optik"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        @error('title')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="user_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Ditugaskan Kepada (Karyawan PIC) <span class="text-rose-500">*</span>
                        </label>
                        <select id="user_id" name="user_id" required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                            <option value="">-- Pilih Karyawan PIC --</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ old('user_id') == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->name }} ({{ $emp->nip ?: $emp->email }}) - {{ $emp->department ?: 'Karyawan' }}
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- 3. Pelanggan / Site & Jenis Layanan -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="customer_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Nama Pelanggan / Mitra / Site
                        </label>
                        <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name') }}"
                            placeholder="cth: PT Telco Mandiri / POP Surabaya Gubeng"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    </div>

                    <div>
                        <label for="service_type" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Jenis Layanan ICON
                        </label>
                        <input type="text" id="service_type" name="service_type" value="{{ old('service_type') }}"
                            placeholder="cth: Metronet 1Gbps / IP VPN / Dark Fiber / OLT"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    </div>
                </div>

                <!-- 3b. Kantor Perwakilan (KP) & Kategori Segmen -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="kp" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Kantor Perwakilan (KP)
                        </label>
                        <select id="kp" name="kp"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                            <option value="">-- Pilih Kantor Perwakilan --</option>
                            @foreach($kpList as $key => $label)
                                <option value="{{ $key }}" {{ old('kp') === $key ? 'selected' : '' }}>
                                    KP {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('kp')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="kategori_segmen" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Kategori Segmen Pelanggan
                        </label>
                        <select id="kategori_segmen" name="kategori_segmen"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                            @foreach($kategoriSegmenList as $key => $label)
                                <option value="{{ $key }}" {{ old('kategori_segmen', 'publik') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('kategori_segmen')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- 4. Prioritas & Tanggal Batas Waktu (Deadline) -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label for="priority" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Prioritas Pekerjaan <span class="text-rose-500">*</span>
                        </label>
                        <select id="priority" name="priority" required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                            <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Rendah (Low)</option>
                            <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>Normal (Medium)</option>
                            <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>Tinggi (High)</option>
                            <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Mendesak (Urgent)</option>
                        </select>
                    </div>

                    <div>
                        <label for="start_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Tanggal Mulai
                        </label>
                        <input type="date" id="start_date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    </div>

                    <div>
                        <label for="due_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Batas Waktu (Deadline) <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" id="due_date" name="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+1 day'))) }}" required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        @error('due_date')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- 5. Deskripsi / Instruksi -->
                <div>
                    <label for="description" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Instruksi & Keterangan Tambahan
                    </label>
                    <textarea id="description" name="description" rows="4"
                        placeholder="Tuliskan rincian instruksi pengerjaan dokumen atau tindakan yang diharapkan dari karyawan..."
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">{{ old('description') }}</textarea>
                </div>

                <!-- Action Buttons -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                    <a href="{{ route('tasks.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition-colors">
                        Batal
                    </a>
                    <button type="submit" id="submit-btn"
                        class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-md shadow-blue-500/20 transition-all">
                        Simpan & Tugaskan Karyawan
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        let checkTimeout = null;

        function debounceDuplicateCheck() {
            clearTimeout(checkTimeout);
            checkTimeout = setTimeout(triggerDuplicateCheck, 400);
        }

        async function triggerDuplicateCheck() {
            const category = document.getElementById('category').value;
            const docNumber = document.getElementById('document_number').value.trim();
            const feedback = document.getElementById('duplicate-feedback');
            const spinner = document.getElementById('duplicate-spinner');
            const submitBtn = document.getElementById('submit-btn');

            if (!category || !docNumber) {
                feedback.classList.add('hidden');
                feedback.innerHTML = '';
                return;
            }

            spinner.classList.remove('hidden');

            try {
                const response = await fetch(`/api/tasks/check-duplicate?category=${encodeURIComponent(category)}&document_number=${encodeURIComponent(docNumber)}`);
                const data = await response.json();

                feedback.classList.remove('hidden');
                if (data.exists) {
                    feedback.className = 'mt-2 p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 font-semibold flex items-center gap-2';
                    feedback.innerHTML = `<span>🚨</span> <span>${data.message}</span>`;
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    feedback.className = 'mt-2 p-2 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 font-medium flex items-center gap-1.5';
                    feedback.innerHTML = `<span>✓</span> <span>${data.message}</span>`;
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            } catch (err) {
                console.error(err);
            } finally {
                spinner.classList.add('hidden');
            }
        }
    </script>
    @endpush
</x-layouts.app>

