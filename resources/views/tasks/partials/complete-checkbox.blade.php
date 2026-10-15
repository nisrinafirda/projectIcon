@if(!auth()->user()->isAdmin())
    @php
        $isAssignedToMe = ($task->user_id === auth()->id());
        $mode = $mode ?? 'table';
        $isOverdue = $task->is_overdue;
        $hasComment = !empty(trim((string)$task->comments));
    @endphp

    @if(!$isAssignedToMe)
        @if($mode === 'table')
            <span class="text-[var(--text-muted)] text-xs" title="Tugas milik karyawan lain">-</span>
        @endif
    @else
        @if($mode === 'table')
            {{-- Table Inline Mode --}}
            <div id="complete-box-{{ $task->id }}" 
                 class="inline-flex flex-col items-center gap-1"
                 data-task-id="{{ $task->id }}"
                 data-is-overdue="{{ $isOverdue ? '1' : '0' }}">
                @if($task->status === \App\Models\Task::STATUS_APPROVED)
                    <label class="inline-flex items-center cursor-not-allowed opacity-90" title="Tugas telah dikonfirmasi selesai oleh Admin">
                        <input type="checkbox" checked disabled class="w-4 h-4 rounded text-emerald-600 border-[var(--border)] cursor-not-allowed bg-[var(--surface-2)]">
                    </label>
                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-500/15 text-emerald-600 border border-emerald-500/20 whitespace-nowrap">
                        ✓ Terkonfirmasi
                    </span>
                @elseif($task->status === \App\Models\Task::STATUS_SUBMITTED)
                    <button type="button" 
                            id="chk-btn-{{ $task->id }}"
                            onclick="toggleCompleteAjax({{ $task->id }}, true)" 
                            class="inline-flex items-center p-1 rounded hover:bg-purple-500/10 transition-colors cursor-pointer" 
                            title="Klik untuk membatalkan tanda selesai">
                        <input type="checkbox" checked class="w-4 h-4 rounded text-purple-600 border-[var(--border)] pointer-events-none cursor-pointer">
                    </button>
                    <span id="status-badge-{{ $task->id }}" class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold bg-purple-500/15 text-purple-600 border border-purple-500/20 whitespace-nowrap" title="Menunggu konfirmasi Admin (Klik checkbox untuk membatalkan)">
                        ⏳ Menunggu Admin
                    </span>
                @else
                    <button type="button" 
                            id="chk-btn-{{ $task->id }}"
                            onclick="toggleCompleteAjax({{ $task->id }}, false)" 
                            class="inline-flex items-center p-1 rounded hover:bg-blue-500/10 transition-colors cursor-pointer" 
                            title="Tandai tugas selesai">
                        <input type="checkbox" id="chk-input-{{ $task->id }}" class="w-4 h-4 rounded text-blue-600 border-[var(--border)] pointer-events-none cursor-pointer">
                    </button>
                    @if($task->status === \App\Models\Task::STATUS_REJECTED)
                    <span id="status-badge-{{ $task->id }}" class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-rose-500/15 text-rose-500 border border-rose-500/20 whitespace-nowrap">
                        Perlu Revisi
                    </span>
                    @else
                    <span id="status-badge-{{ $task->id }}" class="text-[9px] text-[var(--text-muted)]">Belum Selesai</span>
                    @endif
                @endif
            </div>
        @else
            {{-- Detail Page Card Mode --}}
            <div id="complete-detail-box-{{ $task->id }}" 
                 class="theme-card p-6 border-2 {{ $task->status === \App\Models\Task::STATUS_APPROVED ? 'border-emerald-500/40' : ($task->status === \App\Models\Task::STATUS_SUBMITTED ? 'border-purple-500/40' : 'border-blue-500/40') }}"
                 data-is-overdue="{{ $isOverdue ? '1' : '0' }}">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-lg">
                                @if($task->status === \App\Models\Task::STATUS_APPROVED) ✅
                                @elseif($task->status === \App\Models\Task::STATUS_SUBMITTED) ⏳
                                @else 📋
                                @endif
                            </span>
                            <h3 class="text-base font-bold text-[var(--text)]">Tandai Pengerjaan Tugas</h3>
                        </div>
                        <p class="text-xs text-[var(--text-muted)]">
                            @if($task->status === \App\Models\Task::STATUS_APPROVED)
                                Tugas telah selesai dan telah dikonfirmasi oleh Administrator pada {{ $task->confirmed_at ? $task->confirmed_at->format('d/m/Y H:i') : ($task->reviewed_at ? $task->reviewed_at->format('d/m/Y H:i') : '-') }}.
                            @elseif($task->status === \App\Models\Task::STATUS_SUBMITTED)
                                Tugas telah Anda tandai selesai dan saat ini sedang menunggu konfirmasi/verifikasi dari Administrator.
                            @else
                                Centang tugas ini jika Anda telah menyelesaikan pekerjaannya untuk diteruskan ke tahap konfirmasi Admin.
                            @endif
                        </p>
                    </div>

                    <div>
                        @if($task->status === \App\Models\Task::STATUS_APPROVED)
                            <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-500/15 text-emerald-600 border border-emerald-500/20 font-bold text-xs">
                                <input type="checkbox" checked disabled class="w-4 h-4 rounded text-emerald-600 border-[var(--border)] cursor-not-allowed">
                                <span>✓ Terkonfirmasi Admin</span>
                            </div>
                        @elseif($task->status === \App\Models\Task::STATUS_SUBMITTED)
                            <button type="button" 
                                    onclick="toggleCompleteAjax({{ $task->id }}, true, true)" 
                                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-purple-500/15 hover:bg-purple-500/25 text-purple-600 border border-purple-500/20 font-bold text-xs transition-colors cursor-pointer" 
                                    title="Klik untuk membatalkan">
                                <input type="checkbox" checked class="w-4 h-4 rounded text-purple-600 border-[var(--border)] pointer-events-none">
                                <span>⏳ Menunggu Konfirmasi (Batalkan?)</span>
                            </button>
                        @else
                            <button type="button" 
                                    onclick="toggleCompleteAjax({{ $task->id }}, false, true)" 
                                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl theme-btn-primary font-bold text-xs shadow-md transition-all cursor-pointer">
                                <input type="checkbox" class="w-4 h-4 rounded text-blue-600 border-white pointer-events-none">
                                <span>Tandai Tugas Selesai &rarr;</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- Shared AJAX Script for Checkbox Toggle, Inline Comment Editing, and Toast Alerts --}}
    <script>
        if (typeof window.toggleCompleteAjax !== 'function') {
            // Toast notification popup
            window.showInlineToast = function(msg, type = 'info') {
                let container = document.getElementById('toast-notification-container');
                if (!container) {
                    container = document.createElement('div');
                    container.id = 'toast-notification-container';
                    container.className = 'fixed bottom-5 right-5 z-[9999] flex flex-col gap-2 pointer-events-none max-w-sm';
                    document.body.appendChild(container);
                }

                const toast = document.createElement('div');
                const bgColors = {
                    success: 'bg-emerald-600 text-white',
                    warning: 'bg-amber-600 text-white shadow-amber-900/30',
                    error: 'bg-rose-600 text-white shadow-rose-900/30',
                    info: 'bg-blue-600 text-white'
                };
                const icons = {
                    success: '✓',
                    warning: '⚠️',
                    error: '✕',
                    info: 'ℹ️'
                };

                toast.className = `${bgColors[type] || bgColors.info} px-4 py-2.5 rounded-xl text-xs font-semibold shadow-lg flex items-center gap-2 transform transition-all duration-300 translate-y-3 opacity-0 pointer-events-auto`;
                toast.innerHTML = `<span class="text-sm font-bold shrink-0">${icons[type] || '•'}</span><span>${msg}</span>`;

                container.appendChild(toast);

                requestAnimationFrame(() => {
                    toast.classList.remove('translate-y-3', 'opacity-0');
                });

                setTimeout(() => {
                    toast.classList.add('opacity-0', 'translate-y-2');
                    setTimeout(() => toast.remove(), 300);
                }, 3500);
            };

            // Open inline comment editing
            window.openCommentEdit = function(taskId, highlight = false) {
                const view = document.getElementById('comment-view-' + taskId);
                const edit = document.getElementById('comment-edit-' + taskId);
                const input = document.getElementById('comment-input-' + taskId);
                const cell = document.getElementById('comment-cell-' + taskId);

                if (view) view.classList.add('hidden');
                if (edit) edit.classList.remove('hidden');

                if (cell && highlight) {
                    cell.classList.add('ring-2', 'ring-amber-400', 'bg-amber-500/10', 'rounded-xl');
                    cell.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
                }

                if (input) {
                    input.focus();
                    input.setSelectionRange(input.value.length, input.value.length);
                }
            };

            // Cancel inline comment editing
            window.cancelCommentEdit = function(taskId) {
                const view = document.getElementById('comment-view-' + taskId);
                const edit = document.getElementById('comment-edit-' + taskId);
                const cell = document.getElementById('comment-cell-' + taskId);
                const err = document.getElementById('comment-error-' + taskId);

                if (edit) edit.classList.add('hidden');
                if (view) view.classList.remove('hidden');
                if (err) err.classList.add('hidden');
                if (cell) {
                    cell.classList.remove('ring-2', 'ring-amber-400', 'bg-amber-500/10', 'rounded-xl');
                }
            };

            // Save inline comment via AJAX
            window.saveCommentAjax = async function(taskId) {
                const input = document.getElementById('comment-input-' + taskId);
                const btn = document.getElementById('comment-save-btn-' + taskId);
                const textSpan = document.getElementById('comment-text-' + taskId);
                const cell = document.getElementById('comment-cell-' + taskId);
                const err = document.getElementById('comment-error-' + taskId);
                const val = (input ? input.value : '').trim();

                if (!val) {
                    if (err) {
                        err.textContent = 'Komentar tidak boleh kosong.';
                        err.classList.remove('hidden');
                    }
                    if (input) input.focus();
                    return;
                }

                if (err) err.classList.add('hidden');
                const originalBtnText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span>...</span>';

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const response = await fetch(`/tasks/${taskId}/comment`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ comments: val })
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        if (err) {
                            err.textContent = data.message || 'Gagal menyimpan komentar.';
                            err.classList.remove('hidden');
                        }
                        return;
                    }

                    // Update UI text
                    if (textSpan) {
                        textSpan.textContent = data.comments;
                        textSpan.title = data.comments;
                        textSpan.className = 'text-[11px] block truncate font-medium text-[var(--text)]';
                    }

                    // Update cell comment flag
                    if (cell) {
                        cell.dataset.hasComment = '1';
                        cell.classList.remove('ring-2', 'ring-amber-400', 'bg-amber-500/10', 'rounded-xl');
                    }

                    if (input) input.defaultValue = data.comments;

                    window.cancelCommentEdit(taskId);
                    window.showInlineToast('Komentar berhasil disimpan.', 'success');
                } catch (e) {
                    console.error(e);
                    if (err) {
                        err.textContent = 'Terjadi kesalahan saat menyimpan.';
                        err.classList.remove('hidden');
                    }
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = originalBtnText;
                }
            };

            // Toggle task completion checkbox via AJAX
            window.toggleCompleteAjax = async function(taskId, isUncheck = false, isDetail = false) {
                const chkInput = document.getElementById('chk-input-' + taskId);
                const box = document.getElementById('complete-box-' + taskId);
                const detailBox = document.getElementById('complete-detail-box-' + taskId);
                const commentCell = document.getElementById('comment-cell-' + taskId);
                const commentInput = document.getElementById('comment-input-' + taskId);

                const isOverdue = (box?.dataset.isOverdue === '1' || detailBox?.dataset.isOverdue === '1');

                // Client-side rule check for overdue tasks
                if (!isUncheck && isOverdue) {
                    const currentComment = (commentInput ? commentInput.value : '').trim();
                    const hasCellComment = (commentCell?.dataset.hasComment === '1');

                    if (!currentComment && !hasCellComment) {
                        if (chkInput) chkInput.checked = false;
                        window.showInlineToast('Tugas ini terlambat, isi komentar alasan keterlambatan dulu', 'warning');
                        window.openCommentEdit(taskId, true);
                        return;
                    }
                }

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const response = await fetch(`/tasks/${taskId}/toggle-complete`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(isUncheck ? { uncheck: 1 } : {})
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        if (chkInput) chkInput.checked = false;
                        if (data.requires_comment || data.requires_reason) {
                            window.showInlineToast(data.message || 'Tugas ini terlambat, isi komentar alasan keterlambatan dulu', 'warning');
                            window.openCommentEdit(taskId, true);
                        } else {
                            window.showInlineToast(data.message || 'Gagal mengubah status tugas.', 'error');
                        }
                        return;
                    }

                    if (isDetail) {
                        // On detail page, reload to update status card and assigner logs cleanly
                        window.location.reload();
                        return;
                    }

                    // On table page, update checkbox UI dynamically
                    if (box) {
                        if (data.is_submitted) {
                            // Switched to submitted (Menunggu Konfirmasi Admin)
                            box.innerHTML = `
                                <button type="button" 
                                        id="chk-btn-${taskId}"
                                        onclick="toggleCompleteAjax(${taskId}, true)" 
                                        class="inline-flex items-center p-1 rounded hover:bg-purple-500/10 transition-colors cursor-pointer" 
                                        title="Klik untuk membatalkan tanda selesai">
                                    <input type="checkbox" checked class="w-4 h-4 rounded text-purple-600 border-[var(--border)] pointer-events-none cursor-pointer">
                                </button>
                                <span id="status-badge-${taskId}" class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold bg-purple-500/15 text-purple-600 border border-purple-500/20 whitespace-nowrap" title="Menunggu konfirmasi Admin (Klik checkbox untuk membatalkan)">
                                    ⏳ Menunggu Admin
                                </span>
                            `;
                            window.showInlineToast('Tugas ditandai selesai. Menunggu konfirmasi Admin.', 'success');
                        } else {
                            // Reverted to in progress
                            box.innerHTML = `
                                <button type="button" 
                                        id="chk-btn-${taskId}"
                                        onclick="toggleCompleteAjax(${taskId}, false)" 
                                        class="inline-flex items-center p-1 rounded hover:bg-blue-500/10 transition-colors cursor-pointer" 
                                        title="Tandai tugas selesai">
                                    <input type="checkbox" id="chk-input-${taskId}" class="w-4 h-4 rounded text-blue-600 border-[var(--border)] pointer-events-none cursor-pointer">
                                </button>
                                <span id="status-badge-${taskId}" class="text-[9px] text-[var(--text-muted)]">Belum Selesai</span>
                            `;
                            window.showInlineToast('Tanda selesai dibatalkan. Status kembali Sedang Dikerjakan.', 'info');
                        }
                    }
                } catch (e) {
                    console.error(e);
                    if (chkInput) chkInput.checked = isUncheck;
                    window.showInlineToast('Terjadi kesalahan koneksi.', 'error');
                }
            };
        }
    </script>
@endif
