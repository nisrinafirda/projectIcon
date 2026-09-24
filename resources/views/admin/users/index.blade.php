<x-layouts.app>
    <x-slot:title>Kelola Karyawan — ICON</x-slot:title>
    <x-slot:pageHeading>Kelola Karyawan & Pembagian Beban Kerja</x-slot:pageHeading>

    <div class="space-y-6">
        <!-- Top Action Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Daftar Karyawan & Penugasan</h2>
                <p class="text-xs text-slate-500 mt-0.5">Pantau jumlah tugas yang sedang dipegang oleh masing-masing karyawan.</p>
            </div>

            <button type="button" onclick="openUserModal()"
                class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm shadow-blue-500/20 transition-colors shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                Tambah Karyawan Baru
            </button>
        </div>

        <!-- Employees Workload Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-[11px] border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-4">Karyawan / NIP</th>
                            <th class="py-3.5 px-4">Peran (Role)</th>
                            <th class="py-3.5 px-4">Divisi / Departemen</th>
                            <th class="py-3.5 px-4 text-center">Total Tugas</th>
                            <th class="py-3.5 px-4 text-center">Sedang Berjalan</th>
                            <th class="py-3.5 px-4 text-center">Menunggu Verifikasi</th>
                            <th class="py-3.5 px-4 text-center">Selesai</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($users as $u)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-xs shadow-2xs">
                                        {{ strtoupper(substr($u->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900">{{ $u->name }}</p>
                                        <p class="text-[11px] text-slate-500 font-mono">
                                            <span class="text-blue-600 font-semibold">@{{ $u->username ?: '-' }}</span>
                                            {{ $u->nip ? '• NIP: '.$u->nip : '' }}
                                            {{ $u->email ? '• '.$u->email : '' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                    {{ $u->role === 'admin' ? 'bg-amber-100 text-amber-800' : 'bg-cyan-100 text-cyan-800' }}">
                                    {{ $u->role }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-medium text-slate-700">
                                {{ $u->department ?: '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-slate-900">
                                {{ $u->total_tasks }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full font-bold {{ $u->pending_tasks > 0 ? 'bg-amber-100 text-amber-800' : 'text-slate-400' }}">
                                    {{ $u->pending_tasks }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full font-bold {{ $u->submitted_tasks > 0 ? 'bg-purple-100 text-purple-800' : 'text-slate-400' }}">
                                    {{ $u->submitted_tasks }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full font-bold {{ $u->completed_tasks > 0 ? 'bg-emerald-100 text-emerald-800' : 'text-slate-400' }}">
                                    {{ $u->completed_tasks }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Filter user tasks button -->
                                    <a href="{{ route('tasks.index', ['user_id' => $u->id]) }}" class="px-2.5 py-1.5 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white font-semibold text-xs transition-colors">
                                        Lihat Tugas
                                    </a>

                                    <button type="button" onclick="openEditUserModal({{ json_encode($u) }})" class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors" title="Edit Akun">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>

                                    @if($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Karyawan -->
    <div id="userModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900">Tambah Akun Karyawan Baru</h3>
                <button type="button" onclick="closeUserModal()" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-3.5">
                @csrf

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Lengkap</label>
                        <input type="text" name="name" required placeholder="Nama Karyawan" class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-300 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Username</label>
                        <input type="text" name="username" required placeholder="cth: karyawan.icon" class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-300 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">NIP Pegawai</label>
                        <input type="text" name="nip" placeholder="cth: NIP105" class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-300 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Peran (Role)</label>
                        <select name="role" required class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-300 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 font-semibold">
                            <option value="user" selected>Karyawan (User)</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                </div>


                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Divisi / Departemen</label>
                    <input type="text" name="department" placeholder="cth: Sales, Service Delivery, Operasional Pusat" class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-300 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Kata Sandi Awal</label>
                    <div class="relative">
                        <input type="password" id="create_password" name="password" required minlength="8" value="password" class="w-full pl-3 pr-10 py-2 rounded-xl bg-slate-50 border border-slate-300 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="button" onclick="togglePasswordVisibility('create_password', this)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none" title="Tampilkan/Sembunyikan Kata Sandi">
                            <svg class="w-4 h-4 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg class="w-4 h-4 eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                        </button>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Default diisi: password</p>
                </div>

                <div class="pt-3 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeUserModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm shadow-blue-500/20">
                        Simpan Karyawan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Karyawan -->
    <div id="editUserModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900">Edit Data Akun Pengguna</h3>
                <button type="button" onclick="closeEditUserModal()" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form id="editUserForm" method="POST" action="" class="space-y-3.5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Lengkap</label>
                        <input type="text" id="edit_name" name="name" required class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-300 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Username</label>
                        <input type="text" id="edit_username" name="username" required class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-300 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">NIP Pegawai</label>
                        <input type="text" id="edit_nip" name="nip" class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-300 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Peran (Role)</label>
                        <select id="edit_role" name="role" required class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-300 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 font-semibold">
                            <option value="user">Karyawan (User)</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                </div>


                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Divisi / Departemen</label>
                    <input type="text" id="edit_department" name="department" class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-300 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Kata Sandi Baru <span class="text-slate-400 font-normal lowercase">(kosongkan jika tidak diubah)</span></label>
                    <div class="relative">
                        <input type="password" id="edit_password" name="password" minlength="8" placeholder="••••••••" class="w-full pl-3 pr-10 py-2 rounded-xl bg-slate-50 border border-slate-300 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="button" onclick="togglePasswordVisibility('edit_password', this)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none" title="Tampilkan/Sembunyikan Kata Sandi">
                            <svg class="w-4 h-4 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg class="w-4 h-4 eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Konfirmasi Kata Sandi Baru</label>
                    <div class="relative">
                        <input type="password" id="edit_password_confirmation" name="password_confirmation" minlength="8" placeholder="••••••••" class="w-full pl-3 pr-10 py-2 rounded-xl bg-slate-50 border border-slate-300 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="button" onclick="togglePasswordVisibility('edit_password_confirmation', this)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none" title="Tampilkan/Sembunyikan Kata Sandi">
                            <svg class="w-4 h-4 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg class="w-4 h-4 eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                        </button>
                    </div>
                    <p id="edit_password_error" class="text-[11px] text-rose-600 mt-1 hidden"></p>
                </div>

                <div class="pt-3 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeEditUserModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm shadow-blue-500/20">
                        Perbarui Akun
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';

            const eyeOpen = btn.querySelector('.eye-open');
            const eyeClosed = btn.querySelector('.eye-closed');
            if (eyeOpen && eyeClosed) {
                eyeOpen.classList.toggle('hidden', isPassword);
                eyeClosed.classList.toggle('hidden', !isPassword);
            }
        }

        function resetPasswordToggle(inputId) {
            const input = document.getElementById(inputId);
            if (!input) return;
            input.type = 'password';
            const btn = input.parentElement ? input.parentElement.querySelector('button') : null;
            if (btn) {
                const eyeOpen = btn.querySelector('.eye-open');
                const eyeClosed = btn.querySelector('.eye-closed');
                if (eyeOpen) eyeOpen.classList.remove('hidden');
                if (eyeClosed) eyeClosed.classList.add('hidden');
            }
        }

        function openUserModal() {
            document.getElementById('userModal').classList.remove('hidden');
        }

        function closeUserModal() {
            document.getElementById('userModal').classList.add('hidden');
            resetPasswordToggle('create_password');
        }

        function openEditUserModal(user) {
            const form = document.getElementById('editUserForm');
            form.action = `/admin/users/${user.id}`;
            document.getElementById('edit_name').value = user.name || '';
            document.getElementById('edit_username').value = user.username || '';
            document.getElementById('edit_nip').value = user.nip || '';
            document.getElementById('edit_role').value = user.role || 'user';
            document.getElementById('edit_department').value = user.department || '';
            document.getElementById('edit_password').value = '';
            document.getElementById('edit_password_confirmation').value = '';
            resetPasswordToggle('edit_password');
            resetPasswordToggle('edit_password_confirmation');
            const errorEl = document.getElementById('edit_password_error');
            if (errorEl) {
                errorEl.classList.add('hidden');
                errorEl.textContent = '';
            }
            document.getElementById('editUserModal').classList.remove('hidden');
        }

        function closeEditUserModal() {
            document.getElementById('editUserModal').classList.add('hidden');
            document.getElementById('edit_password').value = '';
            document.getElementById('edit_password_confirmation').value = '';
            resetPasswordToggle('edit_password');
            resetPasswordToggle('edit_password_confirmation');
            const errorEl = document.getElementById('edit_password_error');
            if (errorEl) {
                errorEl.classList.add('hidden');
                errorEl.textContent = '';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const editForm = document.getElementById('editUserForm');
            if (editForm) {
                editForm.addEventListener('submit', function (e) {
                    const pwd = document.getElementById('edit_password').value;
                    const pwdConfirm = document.getElementById('edit_password_confirmation').value;
                    const errorEl = document.getElementById('edit_password_error');

                    if (pwd.length > 0) {
                        if (!pwdConfirm) {
                            e.preventDefault();
                            errorEl.textContent = 'Konfirmasi kata sandi wajib diisi jika kata sandi baru diisi.';
                            errorEl.classList.remove('hidden');
                            return false;
                        }
                        if (pwd !== pwdConfirm) {
                            e.preventDefault();
                            errorEl.textContent = 'Konfirmasi kata sandi tidak cocok dengan kata sandi baru.';
                            errorEl.classList.remove('hidden');
                            return false;
                        }
                    }
                    errorEl.classList.add('hidden');
                    errorEl.textContent = '';
                });
            }
        });
    </script>
</x-layouts.app>

