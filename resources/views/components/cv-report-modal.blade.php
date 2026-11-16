<!-- Contract Verification WhatsApp Report Modal -->
<div id="cvReportModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5" aria-labelledby="cv-modal-title" role="dialog" aria-modal="true">
    <div class="relative w-full max-w-4xl bg-[var(--surface)] border border-[var(--border)] rounded-2xl shadow-2xl overflow-hidden transform transition-all flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="px-5 py-4 border-b border-[var(--border)] flex items-center justify-between gap-3 bg-[var(--surface-2)] shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold text-lg shrink-0">
                    📋
                </div>
                <div>
                    <h3 class="text-sm font-bold text-[var(--text)] tracking-tight" id="cv-modal-title">
                        Laporan Verifikasi Kontrak (Format WhatsApp)
                    </h3>
                    <p class="text-[11px] text-[var(--text-muted)]">
                        Laporan per KP — salin per bagian atau salin semua.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span id="cvModalCopyBadge" class="hidden text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                    ✓ Tersalin!
                </span>
                <button type="button" onclick="closeCvReportModal()" class="w-8 h-8 rounded-lg text-[var(--text-muted)] hover:text-[var(--text)] hover:bg-[var(--surface)] flex items-center justify-center transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>

        <!-- Body -->
        <div class="p-5 overflow-y-auto flex-1 space-y-4">
            <div id="cvModalSpinner" class="flex flex-col items-center justify-center py-12 text-slate-500 space-y-2">
                <svg class="animate-spin w-8 h-8 text-emerald-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-xs font-semibold">Menyiapkan laporan Verifikasi Kontrak...</span>
            </div>

            <div id="cvModalContent" class="hidden space-y-4"></div>
        </div>

        <!-- Footer -->
        <div class="px-5 py-3 border-t border-[var(--border)] bg-[var(--surface-2)] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 shrink-0">
            <div class="text-xs text-[var(--text-muted)]">
                <span id="cvModalSummary"></span>
            </div>
            <div class="flex items-center gap-2 justify-end">
                <button type="button"
                        onclick="closeCvReportModal()"
                        class="px-3.5 py-2 rounded-xl bg-[var(--surface)] hover:bg-[var(--surface-2)] text-[var(--text)] border border-[var(--border)] text-xs font-semibold transition-colors">
                    Tutup
                </button>
                <button type="button"
                        onclick="copyAllCvReport()"
                        id="btnCopyAllCv"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-bold shadow-xs transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                    <span id="btnCopyAllCvText">Salin Semua</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let _cvReportData = {};

    function openCvReportModal() {
        const modal = document.getElementById('cvReportModal');
        if (!modal) return;

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        const spinner = document.getElementById('cvModalSpinner');
        const content = document.getElementById('cvModalContent');

        if (spinner) spinner.classList.remove('hidden');
        if (content) content.classList.add('hidden');

        fetch('{{ route("report.contract-verification") }}', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.sections) {
                _cvReportData = data.sections;
                renderCvSections(data.sections);
            }
            if (spinner) spinner.classList.add('hidden');
            if (content) content.classList.remove('hidden');
        })
        .catch(err => {
            if (spinner) spinner.innerHTML = '<span class="text-xs text-rose-500 font-semibold">Gagal memuat laporan. Silakan coba kembali.</span>';
        });
    }

    function closeCvReportModal() {
        const modal = document.getElementById('cvReportModal');
        if (!modal) return;
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function renderCvSections(sections) {
        const container = document.getElementById('cvModalContent');
        if (!container) return;
        container.innerHTML = '';

        const kpKeys = Object.keys(sections);
        if (kpKeys.length === 0) {
            container.innerHTML = '<div class="text-center py-8 text-sm text-slate-500">Tidak ada data On Process untuk ditampilkan.</div>';
            document.getElementById('cvModalSummary').textContent = '';
            return;
        }

        let totalOnProcess = 0;
        let totalSelesai = 0;

        kpKeys.forEach(kpKey => {
            const section = sections[kpKey];
            totalOnProcess += section.onProcess;
            totalSelesai += section.selesai;

            const chunks = section.text;
            chunks.forEach((chunk, idx) => {
                const chunkId = 'cv-chunk-' + kpKey + '-' + idx;

                const wrapper = document.createElement('div');
                wrapper.className = 'relative';

                const header = document.createElement('div');
                header.className = 'flex items-center justify-between mb-1.5';

                const label = document.createElement('span');
                label.className = 'text-xs font-bold text-[var(--text)] flex items-center gap-1.5';
                let labelText = '🏢 KP ' + section.label;
                if (chunks.length > 1) {
                    labelText += ' — Bagian ' + (idx + 1) + '/' + chunks.length;
                }
                label.textContent = labelText;

                const copyBtn = document.createElement('button');
                copyBtn.type = 'button';
                copyBtn.className = 'inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 text-[11px] font-semibold border border-emerald-200 dark:border-emerald-700 transition-colors';
                copyBtn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg> Salin';
                copyBtn.onclick = () => copyCvChunk(chunkId, copyBtn);

                header.appendChild(label);
                header.appendChild(copyBtn);

                const textarea = document.createElement('textarea');
                textarea.id = chunkId;
                textarea.rows = 10;
                textarea.readOnly = true;
                textarea.className = 'w-full p-4 rounded-xl font-mono text-xs leading-relaxed bg-[var(--surface-2)] text-[var(--text)] border border-[var(--border)] focus:outline-none focus:ring-2 focus:ring-emerald-500 shadow-inner select-all resize-y';
                textarea.value = chunk;

                wrapper.appendChild(header);
                wrapper.appendChild(textarea);
                container.appendChild(wrapper);
            });
        });

        const summary = document.getElementById('cvModalSummary');
        if (summary) {
            summary.textContent = 'Total: On Process ' + totalOnProcess + ' · Selesai ' + totalSelesai + ' — ' + kpKeys.length + ' KP';
        }
    }

    function copyCvChunk(textareaId, btnEl) {
        const textarea = document.getElementById(textareaId);
        if (!textarea) return;

        const text = textarea.value;
        const onSuccess = () => {
            const origHTML = btnEl.innerHTML;
            btnEl.innerHTML = '<span>✓ Tersalin!</span>';
            setTimeout(() => { btnEl.innerHTML = origHTML; }, 2000);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(onSuccess).catch(() => {
                fallbackCopyCv(textarea, onSuccess);
            });
        } else {
            fallbackCopyCv(textarea, onSuccess);
        }
    }

    function copyAllCvReport() {
        const allTexts = [];
        const container = document.getElementById('cvModalContent');
        if (!container) return;

        container.querySelectorAll('textarea').forEach(ta => {
            if (ta.value.trim()) allTexts.push(ta.value.trim());
        });

        const combined = allTexts.join('\n\n');
        const btnText = document.getElementById('btnCopyAllCvText');
        const badge = document.getElementById('cvModalCopyBadge');

        const onSuccess = () => {
            if (badge) badge.classList.remove('hidden');
            if (btnText) btnText.innerText = '✓ Tersalin!';
            setTimeout(() => {
                if (badge) badge.classList.add('hidden');
                if (btnText) btnText.innerText = 'Salin Semua';
            }, 3000);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(combined).then(onSuccess).catch(() => {
                // fallback: create temp textarea
                const tmp = document.createElement('textarea');
                tmp.value = combined;
                tmp.style.position = 'fixed';
                tmp.style.opacity = '0';
                document.body.appendChild(tmp);
                tmp.focus();
                tmp.select();
                try { document.execCommand('copy'); onSuccess(); } catch(e) { alert('Gagal menyalin. Silakan salin manual.'); }
                document.body.removeChild(tmp);
            });
        } else {
            const tmp = document.createElement('textarea');
            tmp.value = combined;
            tmp.style.position = 'fixed';
            tmp.style.opacity = '0';
            document.body.appendChild(tmp);
            tmp.focus();
            tmp.select();
            try { document.execCommand('copy'); onSuccess(); } catch(e) { alert('Gagal menyalin. Silakan salin manual.'); }
            document.body.removeChild(tmp);
        }
    }

    function fallbackCopyCv(textarea, callback) {
        textarea.focus();
        textarea.select();
        try {
            document.execCommand('copy');
            callback();
        } catch (err) {
            alert('Gagal menyalin teks secara otomatis. Silakan pilih teks dan tekan Ctrl + C.');
        }
    }
</script>
