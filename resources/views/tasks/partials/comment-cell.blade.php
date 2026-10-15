@php
    $isOwner = (!auth()->user()->isAdmin() && $task->user_id === auth()->id());
    $hasComment = !empty(trim((string)$task->comments));
@endphp

<div id="comment-cell-{{ $task->id }}" class="transition-all duration-200 p-1" data-has-comment="{{ $hasComment ? '1' : '0' }}">
    @if(!$isOwner)
        {{-- Read-only display for admin or tasks assigned to other employees --}}
        @if($hasComment)
            <span class="text-[11px] text-[var(--text)] truncate block" title="{{ $task->comments }}">
                {{ $task->comments }}
            </span>
        @else
            <span class="text-[11px] text-[var(--text-muted)] italic">
                -
            </span>
        @endif
    @else
        {{-- Interactive Inline Comment for Assigned Employee --}}

        {{-- 1. View State --}}
        <div id="comment-view-{{ $task->id }}"
             onclick="openCommentEdit({{ $task->id }})"
             class="group flex items-center justify-between gap-1.5 cursor-pointer py-1 px-2 rounded-lg hover:bg-[var(--surface-2)] transition-colors border border-transparent hover:border-[var(--border)]"
             title="Klik untuk mengubah komentar">
            <div class="flex-1 min-w-0">
                <span id="comment-text-{{ $task->id }}"
                      class="text-[11px] block truncate font-medium {{ $hasComment ? 'text-[var(--text)]' : 'text-amber-500 italic flex items-center gap-1' }}"
                      title="{{ $hasComment ? $task->comments : 'Belum ada komentar (Klik untuk mengisi)' }}">
                    @if($hasComment)
                        {{ $task->comments }}
                    @else
                        <span>⚠️</span> Belum ada komentar
                    @endif
                </span>
            </div>
            <button type="button"
                    class="shrink-0 p-1 rounded hover:bg-[var(--surface)] text-[var(--text-muted)] group-hover:text-blue-500 transition-colors"
                    title="Edit Komentar">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
            </button>
        </div>

        {{-- 2. Edit State (Inline Textarea) --}}
        <div id="comment-edit-{{ $task->id }}" class="hidden space-y-1.5 py-0.5">
            <div class="relative">
                <textarea id="comment-input-{{ $task->id }}"
                          rows="2"
                          class="w-full text-xs p-2 rounded-lg bg-[var(--surface-2)] border border-[var(--border)] focus:outline-none focus:ring-2 focus:ring-blue-500 text-[var(--text)] resize-y placeholder-[var(--text-muted)] leading-relaxed"
                          placeholder="Tulis alasan keterlambatan / kendala..."
                          onkeydown="if(event.key === 'Escape') cancelCommentEdit({{ $task->id }}); if((event.metaKey || event.ctrlKey) && event.key === 'Enter') saveCommentAjax({{ $task->id }});">{{ $task->comments }}</textarea>
                <p id="comment-error-{{ $task->id }}" class="hidden text-[10px] text-rose-500 font-semibold mt-0.5"></p>
            </div>
            <div class="flex items-center gap-1.5 justify-end">
                <button type="button"
                        onclick="cancelCommentEdit({{ $task->id }})"
                        class="px-2.5 py-1 text-[10px] font-semibold rounded-md bg-[var(--surface-2)] hover:bg-[var(--surface)] text-[var(--text-muted)] hover:text-[var(--text)] border border-[var(--border)] transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="button"
                        id="comment-save-btn-{{ $task->id }}"
                        onclick="saveCommentAjax({{ $task->id }})"
                        class="px-2.5 py-1 text-[10px] font-bold rounded-md bg-blue-600 hover:bg-blue-700 text-white shadow-xs transition-colors cursor-pointer flex items-center gap-1">
                    <span>Simpan</span>
                </button>
            </div>
        </div>
    @endif
</div>

