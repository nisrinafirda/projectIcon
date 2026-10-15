<x-layouts.app>
    <x-slot:title>Laporan Update WhatsApp — Monitoring PPB</x-slot:title>
    <x-slot:pageHeading>Laporan Update WhatsApp</x-slot:pageHeading>

    <div class="space-y-5 max-w-5xl mx-auto">
        <!-- Top Back Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-2">
                <a href="{{ route('tasks.index') }}" 
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[var(--surface-2)] hover:bg-[var(--surface)] text-[var(--text)] border border-[var(--border)] text-xs font-bold transition-all shadow-2xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span>Ke Semua Tugas</span>
                </a>
                <a href="{{ route('dashboard') }}" 
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[var(--surface-2)] hover:bg-[var(--surface)] text-[var(--text)] border border-[var(--border)] text-xs font-bold transition-all shadow-2xs">
                    <span>Dashboard</span>
                </a>
            </div>

            <!-- Copy Action Button -->
            <button type="button" 
                    onclick="copyWhatsAppReport()" 
                    id="btnCopyTop"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                <span id="btnCopyTopText">Salin Laporan WhatsApp</span>
            </button>
        </div>

        <!-- Main Card -->
        <div class="theme-card p-6 border border-[var(--border)] shadow-sm rounded-2xl space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-[var(--border)] pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold text-lg shrink-0">
                        📱
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-[var(--text)]">
                            Laporan Update Project Monitoring PPB
                        </h2>
                        <p class="text-xs text-[var(--text-muted)]">
                            Format teks plain tebal WhatsApp (emoji, tanda bintang, pemisah baris). Siap langsung disalin dan dikirim.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span id="copyStatusBadge" class="hidden text-xs font-bold px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700 animate-pulse">
                        ✓ Laporan Berhasil Tersalin!
                    </span>
                </div>
            </div>

            <!-- Parameter Header (Opportunity & Revenue AS IS) -->
            <div class="p-4 bg-[var(--surface-2)] border border-[var(--border)] rounded-xl space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-[var(--text)] flex items-center gap-1.5">
                        <span>⚙️</span> Parameter Header (Opportunity & Revenue AS IS)
                    </span>
                    <span id="pageSettingsStatus" class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold hidden">
                        ✓ Tersimpan otomatis
                    </span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Opportunity Publik (Rp)</label>
                        <input type="number" id="page_opp_publik" onchange="savePageReportSettings()" value="{{ $settings['opportunity_publik'] ?? 0 }}" min="0" step="1000" placeholder="0" class="w-full px-2.5 py-1.5 rounded-lg bg-[var(--surface)] text-[var(--text)] border border-[var(--border)] text-xs focus:ring-1 focus:ring-emerald-500 font-mono">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Revenue AS IS Publik (Rp)</label>
                        <input type="number" id="page_rev_publik" onchange="savePageReportSettings()" value="{{ $settings['revenue_as_is_publik'] ?? 0 }}" min="0" step="1000" placeholder="0" class="w-full px-2.5 py-1.5 rounded-lg bg-[var(--surface)] text-[var(--text)] border border-[var(--border)] text-xs focus:ring-1 focus:ring-emerald-500 font-mono">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Opportunity PLN (Rp)</label>
                        <input type="number" id="page_opp_pln" onchange="savePageReportSettings()" value="{{ $settings['opportunity_pln'] ?? 0 }}" min="0" step="1000" placeholder="0" class="w-full px-2.5 py-1.5 rounded-lg bg-[var(--surface)] text-[var(--text)] border border-[var(--border)] text-xs focus:ring-1 focus:ring-emerald-500 font-mono">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Revenue AS IS PLN (Rp)</label>
                        <input type="number" id="page_rev_pln" onchange="savePageReportSettings()" value="{{ $settings['revenue_as_is_pln'] ?? 0 }}" min="0" step="1000" placeholder="0" class="w-full px-2.5 py-1.5 rounded-lg bg-[var(--surface)] text-[var(--text)] border border-[var(--border)] text-xs focus:ring-1 focus:ring-emerald-500 font-mono">
                    </div>
                </div>
            </div>

            <!-- Monospace Textarea Content -->
            <div class="relative">
                <textarea id="whatsappReportArea" 
                          rows="22" 
                          readonly 
                          class="w-full p-4 rounded-xl font-mono text-xs leading-relaxed bg-[var(--surface-2)] text-[var(--text)] border border-[var(--border)] focus:outline-none focus:ring-2 focus:ring-emerald-500 shadow-inner select-all resize-y">{{ $reportText }}</textarea>
            </div>

            <!-- Footer Toolbar -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
                <div class="text-xs text-[var(--text-muted)] flex items-center gap-2">
                    <span>💡 Tips: Klik tombol salin, lalu tekan <strong>Ctrl + V</strong> di chat WhatsApp.</span>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" 
                            onclick="copyWhatsAppReport()" 
                            id="btnCopyBottom"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                        <span id="btnCopyBottomText">Salin Laporan</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Toast & Copy Script -->
    <script>
        async function savePageReportSettings() {
            const oppPublik = document.getElementById('page_opp_publik')?.value || 0;
            const revPublik = document.getElementById('page_rev_publik')?.value || 0;
            const oppPln = document.getElementById('page_opp_pln')?.value || 0;
            const revPln = document.getElementById('page_rev_pln')?.value || 0;

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
                        const textarea = document.getElementById('whatsappReportArea');
                        if (textarea) textarea.value = data.report;
                    }
                    const statusEl = document.getElementById('pageSettingsStatus');
                    if (statusEl) {
                        statusEl.classList.remove('hidden');
                        setTimeout(() => statusEl.classList.add('hidden'), 2500);
                    }
                }
            } catch (e) {
                console.error('Save page report settings error:', e);
            }
        }

        async function copyWhatsAppReport() {
            const textarea = document.getElementById('whatsappReportArea');
            if (!textarea) return;

            // Fetch fresh report from server to ensure latest timestamp and data
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
                // If fetch fails, proceed with current textarea value
            }

            const text = textarea.value;

            const notifySuccess = () => {
                const badge = document.getElementById('copyStatusBadge');
                const btnTopText = document.getElementById('btnCopyTopText');
                const btnBottomText = document.getElementById('btnCopyBottomText');

                if (badge) {
                    badge.classList.remove('hidden');
                    badge.innerText = '✓ Tersalin ke Clipboard!';
                }
                if (btnTopText) btnTopText.innerText = '✓ Tersalin!';
                if (btnBottomText) btnBottomText.innerText = '✓ Tersalin!';

                setTimeout(() => {
                    if (badge) badge.classList.add('hidden');
                    if (btnTopText) btnTopText.innerText = 'Salin Laporan WhatsApp';
                    if (btnBottomText) btnBottomText.innerText = 'Salin Laporan';
                }, 3000);
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(notifySuccess).catch(() => {
                    fallbackCopy(textarea, notifySuccess);
                });
            } else {
                fallbackCopy(textarea, notifySuccess);
            }
        }

        function fallbackCopy(textarea, callback) {
            textarea.focus();
            textarea.select();
            try {
                document.execCommand('copy');
                callback();
            } catch (err) {
                alert('Gagal menyalin teks secara otomatis. Silakan pilih semua dan salin manual (Ctrl + C).');
            }
        }
    </script>
</x-layouts.app>
