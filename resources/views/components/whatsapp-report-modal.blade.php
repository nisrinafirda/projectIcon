<!-- WhatsApp Report Modal -->
<div id="whatsappReportModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="relative w-full max-w-4xl bg-[var(--surface)] border border-[var(--border)] rounded-2xl shadow-2xl overflow-hidden transform transition-all flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="px-5 py-4 border-b border-[var(--border)] flex items-center justify-between gap-3 bg-[var(--surface-2)] shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold text-lg shrink-0">
                    📱
                </div>
                <div>
                    <h3 class="text-sm font-bold text-[var(--text)] tracking-tight" id="modal-title">
                        Laporan Update Project Monitoring PPB (Format WhatsApp)
                    </h3>
                    <p class="text-[11px] text-[var(--text-muted)]">
                        Format teks khusus WhatsApp (emoji, tanda bintang, pemisah baris).
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span id="waModalCopyBadge" class="hidden text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                    ✓ Tersalin!
                </span>
                <button type="button" onclick="closeWhatsAppReportModal()" class="w-8 h-8 rounded-lg text-[var(--text-muted)] hover:text-[var(--text)] hover:bg-[var(--surface)] flex items-center justify-center transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>

        <!-- Body -->
        <div class="p-5 overflow-y-auto flex-1 space-y-4">
            <div id="waModalSpinner" class="flex flex-col items-center justify-center py-12 text-slate-500 space-y-2">
                <svg class="animate-spin w-8 h-8 text-emerald-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-xs font-semibold">Menyiapkan laporan update WhatsApp...</span>
            </div>

            <div id="waModalContent" class="hidden space-y-4">
                <!-- 4 Input Parameter Angka Header (Publik & PLN) -->
                <div class="p-4 bg-[var(--surface-2)] border border-[var(--border)] rounded-xl space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-[var(--text)] flex items-center gap-1.5">
                            <span>⚙️</span> Parameter Header (Opportunity & Revenue AS IS)
                        </span>
                        <span id="waModalSettingsStatus" class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold hidden">
                            ✓ Tersimpan otomatis
                        </span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Opportunity Publik (Rp)</label>
                            <input type="number" id="modal_opp_publik" onchange="saveModalReportSettings()" min="0" step="1000" placeholder="0" class="w-full px-2.5 py-1.5 rounded-lg bg-[var(--surface)] text-[var(--text)] border border-[var(--border)] text-xs focus:ring-1 focus:ring-emerald-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Revenue AS IS Publik (Rp)</label>
                            <input type="number" id="modal_rev_publik" onchange="saveModalReportSettings()" min="0" step="1000" placeholder="0" class="w-full px-2.5 py-1.5 rounded-lg bg-[var(--surface)] text-[var(--text)] border border-[var(--border)] text-xs focus:ring-1 focus:ring-emerald-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Opportunity PLN (Rp)</label>
                            <input type="number" id="modal_opp_pln" onchange="saveModalReportSettings()" min="0" step="1000" placeholder="0" class="w-full px-2.5 py-1.5 rounded-lg bg-[var(--surface)] text-[var(--text)] border border-[var(--border)] text-xs focus:ring-1 focus:ring-emerald-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Revenue AS IS PLN (Rp)</label>
                            <input type="number" id="modal_rev_pln" onchange="saveModalReportSettings()" min="0" step="1000" placeholder="0" class="w-full px-2.5 py-1.5 rounded-lg bg-[var(--surface)] text-[var(--text)] border border-[var(--border)] text-xs focus:ring-1 focus:ring-emerald-500 font-mono">
                        </div>
                    </div>
                </div>

                <!-- Textarea -->
                <textarea id="waModalTextarea" 
                          rows="16" 
                          readonly 
                          class="w-full p-4 rounded-xl font-mono text-xs leading-relaxed bg-[var(--surface-2)] text-[var(--text)] border border-[var(--border)] focus:outline-none focus:ring-2 focus:ring-emerald-500 shadow-inner select-all resize-y"></textarea>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-5 py-3 border-t border-[var(--border)] bg-[var(--surface-2)] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 shrink-0">
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.report.whatsapp') }}" target="_blank" class="text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 inline-flex items-center gap-1 hover:underline">
                    <span>Buka Halaman Penuh</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                </a>
            </div>

            <div class="flex items-center gap-2 justify-end">
                <button type="button" 
                        onclick="closeWhatsAppReportModal()" 
                        class="px-3.5 py-2 rounded-xl bg-[var(--surface)] hover:bg-[var(--surface-2)] text-[var(--text)] border border-[var(--border)] text-xs font-semibold transition-colors">
                    Tutup
                </button>
                <button type="button" 
                        onclick="copyWaModalReport()" 
                        id="btnCopyWaModal"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-bold shadow-xs transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                    <span id="btnCopyWaModalText">Salin Laporan</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function openWhatsAppReportModal() {
        const modal = document.getElementById('whatsappReportModal');
        if (!modal) return;

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        const spinner = document.getElementById('waModalSpinner');
        const content = document.getElementById('waModalContent');
        const textarea = document.getElementById('waModalTextarea');

        if (spinner) spinner.classList.remove('hidden');
        if (content) content.classList.add('hidden');

        fetch('{{ route('admin.report.whatsapp') }}', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (textarea && data.report) {
                textarea.value = data.report;
            }
            if (data.settings) {
                const oppPub = document.getElementById('modal_opp_publik');
                const revPub = document.getElementById('modal_rev_publik');
                const oppPln = document.getElementById('modal_opp_pln');
                const revPln = document.getElementById('modal_rev_pln');

                if (oppPub) oppPub.value = data.settings.opportunity_publik || 0;
                if (revPub) revPub.value = data.settings.revenue_as_is_publik || 0;
                if (oppPln) oppPln.value = data.settings.opportunity_pln || 0;
                if (revPln) revPln.value = data.settings.revenue_as_is_pln || 0;
            }
            if (spinner) spinner.classList.add('hidden');
            if (content) content.classList.remove('hidden');
        })
        .catch(err => {
            if (spinner) spinner.innerHTML = '<span class="text-xs text-rose-500 font-semibold">Gagal memuat laporan. Silakan coba kembali atau buka halaman penuh.</span>';
        });
    }

    function closeWhatsAppReportModal() {
        const modal = document.getElementById('whatsappReportModal');
        if (!modal) return;
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    async function saveModalReportSettings() {
        const oppPublik = document.getElementById('modal_opp_publik')?.value || 0;
        const revPublik = document.getElementById('modal_rev_publik')?.value || 0;
        const oppPln = document.getElementById('modal_opp_pln')?.value || 0;
        const revPln = document.getElementById('modal_rev_pln')?.value || 0;

        try {
            const response = await fetch("{{ route('admin.report.whatsapp.settings') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    opportunity_publik: oppPublik,
                    revenue_as_is_publik: revPublik,
                    opportunity_pln: oppPln,
                    revenue_as_is_pln: revPln
                })
            });

            if (response.ok) {
                const data = await response.json();
                if (data.report) {
                    const textarea = document.getElementById('waModalTextarea');
                    if (textarea) textarea.value = data.report;
                }
                const statusEl = document.getElementById('waModalSettingsStatus');
                if (statusEl) {
                    statusEl.classList.remove('hidden');
                    setTimeout(() => statusEl.classList.add('hidden'), 2500);
                }
            }
        } catch (e) {
            console.error('Save report settings error:', e);
        }
    }

    async function copyWaModalReport() {
        const textarea = document.getElementById('waModalTextarea');
        if (!textarea) return;

        // Fetch fresh report before copying
        try {
            const response = await fetch("{{ route('admin.report.whatsapp') }}", {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (response.ok) {
                const data = await response.json();
                if (data && data.report) {
                    textarea.value = data.report;
                }
            }
        } catch (err) {
            // fallback to current textarea
        }

        const text = textarea.value;

        const notifySuccess = () => {
            const badge = document.getElementById('waModalCopyBadge');
            const btnText = document.getElementById('btnCopyWaModalText');

            if (badge) badge.classList.remove('hidden');
            if (btnText) btnText.innerText = '✓ Tersalin!';

            setTimeout(() => {
                if (badge) badge.classList.add('hidden');
                if (btnText) btnText.innerText = 'Salin Laporan';
            }, 3000);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(notifySuccess).catch(() => {
                fallbackCopyModal(textarea, notifySuccess);
            });
        } else {
            fallbackCopyModal(textarea, notifySuccess);
        }
    }

    function fallbackCopyModal(textarea, callback) {
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
