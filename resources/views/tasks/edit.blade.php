<x-layouts.app>
    <x-slot:title>Edit Tugas — {{ $task->document_number }}</x-slot:title>
    <x-slot:pageHeading>Edit Data Tugas</x-slot:pageHeading>

    <div class="max-w-4xl mx-auto">
        <div class="theme-card overflow-hidden">
            <div class="p-6 border-b border-[var(--border)] flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-[var(--text)]">Perbarui Informasi Tugas</h2>
                    <p class="text-xs text-[var(--text-muted)] mt-0.5">Dokumen: <span class="font-mono font-bold">{{ $task->document_number }}</span></p>
                </div>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-[var(--primary-soft)] text-blue-600">
                    {{ $task->category_label }}
                </span>
            </div>

            <form method="POST" action="{{ route('tasks.update', $task) }}" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="category" class="block text-xs font-bold text-[var(--text)] uppercase tracking-wider mb-1.5">
                            Kategori Tugas <span class="text-rose-500">*</span>
                        </label>
                        <select id="category" name="category" required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-[var(--text)] text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-semibold">
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}" {{ old('category', $task->category) === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="document_number" class="block text-xs font-bold text-[var(--text)] uppercase tracking-wider mb-1.5">
                            Nomor Dokumen / Tiket <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="document_number" name="document_number" value="{{ old('document_number', $task->document_number) }}" required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-[var(--text)] font-mono text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('document_number')
                            <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="title" class="block text-xs font-bold text-[var(--text)] uppercase tracking-wider mb-1.5">
                            Judul / Perihal Tugas <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="title" name="title" value="{{ old('title', $task->title) }}" required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-[var(--text)] text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="user_id" class="block text-xs font-bold text-[var(--text)] uppercase tracking-wider mb-1.5">
                            Ditugaskan Kepada (Karyawan PIC) <span class="text-rose-500">*</span>
                        </label>
                        <select id="user_id" name="user_id" required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-[var(--text)] text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ old('user_id', $task->user_id) == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->name }} ({{ $emp->nip ?: $emp->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="customer_name" class="block text-xs font-bold text-[var(--text)] uppercase tracking-wider mb-1.5">
                            Nama Pelanggan / Mitra / Site
                        </label>
                        <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name', $task->customer_name) }}"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-[var(--text)] text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="service_type" class="block text-xs font-bold text-[var(--text)] uppercase tracking-wider mb-1.5">
                            Jenis Layanan
                        </label>
                        <input type="text" id="service_type" name="service_type" value="{{ old('service_type', $task->service_type) }}"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-[var(--text)] text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- KP & Kategori Segmen -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="kp" class="block text-xs font-bold text-[var(--text)] uppercase tracking-wider mb-1.5">
                            Kantor Perwakilan (KP)
                        </label>
                        <select id="kp" name="kp"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-[var(--text)] text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Pilih Kantor Perwakilan --</option>
                            @foreach($kpList as $key => $label)
                                <option value="{{ $key }}" {{ old('kp', $task->kp) === $key ? 'selected' : '' }}>
                                    KP {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="kategori_segmen" class="block text-xs font-bold text-[var(--text)] uppercase tracking-wider mb-1.5">
                            Kategori Segmen Pelanggan
                        </label>
                        <select id="kategori_segmen" name="kategori_segmen"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-[var(--text)] text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach($kategoriSegmenList as $key => $label)
                                <option value="{{ $key }}" {{ old('kategori_segmen', $task->kategori_segmen) === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Hidden Priority (preserve existing data) -->
                <input type="hidden" name="priority" value="{{ old('priority', $task->priority) }}">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="start_date" class="block text-xs font-bold text-[var(--text)] uppercase tracking-wider mb-1.5">
                            Tanggal Mulai
                        </label>
                        <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $task->start_date ? $task->start_date->format('Y-m-d') : '') }}"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-[var(--text)] text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="due_date" class="block text-xs font-bold text-[var(--text)] uppercase tracking-wider mb-1.5">
                            Batas Waktu (Deadline) <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" id="due_date" name="due_date" value="{{ old('due_date', $task->due_date ? $task->due_date->format('Y-m-d') : '') }}" required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-[var(--text)] text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-xs font-bold text-[var(--text)] uppercase tracking-wider mb-1.5">
                        Instruksi & Keterangan
                    </label>
                    <textarea id="description" name="description" rows="4"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] text-[var(--text)] text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description', $task->description) }}</textarea>
                </div>

                <div class="pt-4 border-t border-[var(--border)] flex items-center justify-between">
                    <a href="{{ route('tasks.show', $task) }}" class="px-5 py-2.5 rounded-xl bg-[var(--surface-2)] hover:bg-[var(--surface-3)] text-[var(--text)] font-semibold text-xs transition-colors">
                        Batal
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 rounded-xl theme-btn-primary text-white font-bold text-sm shadow-md transition-all">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
