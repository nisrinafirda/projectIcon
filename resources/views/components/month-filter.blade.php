@props([
    'selectedMonth' => request('month'),
    'selectedYear' => request('year'),
    'id' => null,
])

@php
    $id = $id ?? 'month_picker_' . substr(md5(uniqid((string) mt_rand(), true)), 0, 8);
    $selectedMonth = ($selectedMonth !== null && $selectedMonth !== '') ? (int) $selectedMonth : null;
    $selectedYear = ($selectedYear !== null && $selectedYear !== '') ? (int) $selectedYear : null;
    
    $monthsList = \App\Models\Task::listMonths();

    $buttonLabel = 'Semua Bulan';
    if ($selectedMonth && isset($monthsList[$selectedMonth])) {
        $buttonLabel = $monthsList[$selectedMonth] . ($selectedYear ? ' ' . $selectedYear : '');
    } elseif ($selectedYear) {
        $buttonLabel = 'Tahun ' . $selectedYear;
    }

    $isFiltered = ($selectedMonth !== null || $selectedYear !== null);
    $initialYear = $selectedYear ?: (int) date('Y');
@endphp

<div class="relative w-full" id="{{ $id }}_container">
    <!-- Hidden inputs inside form -->
    <input type="hidden" name="month" id="{{ $id }}_input_month" value="{{ $selectedMonth }}">
    <input type="hidden" name="year" id="{{ $id }}_input_year" value="{{ $selectedYear }}">

    <!-- Trigger Button -->
    <button 
        type="button" 
        id="{{ $id }}_trigger"
        class="w-full py-2 px-3 rounded-xl bg-[var(--surface-2)] hover:bg-[var(--surface)] border {{ $isFiltered ? 'border-blue-500 text-blue-600 dark:text-blue-400 font-bold shadow-xs' : 'border-[var(--border)] text-[var(--text)]' }} text-xs flex items-center justify-between gap-2 transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500"
        aria-haspopup="true"
        aria-expanded="false"
    >
        <span class="flex items-center gap-2 truncate">
            <svg class="w-4 h-4 {{ $isFiltered ? 'text-blue-500' : 'text-[var(--text-muted)]' }} shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <span id="{{ $id }}_label" class="truncate">{{ $buttonLabel }}</span>
        </span>
        <svg id="{{ $id }}_chevron" class="w-3.5 h-3.5 text-[var(--text-muted)] shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <!-- Calendar Popover Modal -->
    <div 
        id="{{ $id }}_popup" 
        class="hidden absolute z-50 mt-1.5 left-0 sm:left-auto right-auto sm:right-0 w-72 max-w-[calc(100vw-2rem)] rounded-2xl bg-[var(--surface)] border border-[var(--border)] shadow-2xl p-3 text-[var(--text)] select-none text-xs"
        style="min-width: 280px;"
    >
        <!-- VIEW 1: MONTHS VIEW -->
        <div id="{{ $id }}_view_months">
            <!-- Header: Prev, Year (clickable), Next -->
            <div class="flex items-center justify-between pb-2 mb-2 border-b border-[var(--border)]">
                <button type="button" id="{{ $id }}_prev_year" class="p-1.5 rounded-lg hover:bg-[var(--surface-2)] text-[var(--text-muted)] hover:text-[var(--text)] transition-colors cursor-pointer" title="Tahun sebelumnya">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <button type="button" id="{{ $id }}_year_btn" class="px-2.5 py-1 rounded-lg hover:bg-[var(--surface-2)] font-bold text-sm text-[var(--text)] transition-colors flex items-center gap-1 group cursor-pointer" title="Klik untuk pilih rentang tahun">
                    <span id="{{ $id }}_displayed_year">{{ $initialYear }}</span>
                    <svg class="w-3 h-3 text-[var(--text-muted)] group-hover:text-[var(--text)] transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <button type="button" id="{{ $id }}_next_year" class="p-1.5 rounded-lg hover:bg-[var(--surface-2)] text-[var(--text-muted)] hover:text-[var(--text)] transition-colors cursor-pointer" title="Tahun berikutnya">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>

            <!-- Grid 12 Bulan (4x3) -->
            <div class="grid grid-cols-4 gap-1.5 py-1" id="{{ $id }}_months_grid">
                <!-- Dynamically populated -->
            </div>

            <!-- Footer: Seluruh Tahun & Hapus Filter -->
            <div class="flex items-center justify-between pt-2.5 mt-2.5 border-t border-[var(--border)] gap-2">
                <button type="button" id="{{ $id }}_all_year_btn" class="flex-1 py-1.5 px-2 rounded-xl bg-[var(--surface-2)] hover:bg-[var(--border)] text-[var(--text)] font-semibold text-[11px] transition-colors text-center cursor-pointer" title="Filter seluruh bulan di tahun ini">
                    Seluruh Tahun
                </button>
                <button type="button" id="{{ $id }}_reset_btn" class="py-1.5 px-2.5 rounded-xl hover:bg-rose-500/10 text-rose-500 font-semibold text-[11px] transition-colors text-center cursor-pointer" title="Kembali ke semua bulan">
                    Hapus Filter
                </button>
            </div>
        </div>

        <!-- VIEW 2: YEARS VIEW (Saat tahun diklik) -->
        <div id="{{ $id }}_view_years" class="hidden">
            <!-- Header: Prev Range, Range Text, Next Range -->
            <div class="flex items-center justify-between pb-2 mb-2 border-b border-[var(--border)]">
                <button type="button" id="{{ $id }}_prev_range" class="p-1.5 rounded-lg hover:bg-[var(--surface-2)] text-[var(--text-muted)] hover:text-[var(--text)] transition-colors cursor-pointer" title="12 tahun sebelumnya">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <div class="font-bold text-xs text-[var(--text)]" id="{{ $id }}_range_text">
                    2020 - 2031
                </div>
                <button type="button" id="{{ $id }}_next_range" class="p-1.5 rounded-lg hover:bg-[var(--surface-2)] text-[var(--text-muted)] hover:text-[var(--text)] transition-colors cursor-pointer" title="12 tahun berikutnya">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>

            <!-- Grid 12 Tahun (4x3) -->
            <div class="grid grid-cols-4 gap-1.5 py-1" id="{{ $id }}_years_grid">
                <!-- 12 year buttons -->
            </div>

            <div class="pt-2 mt-2 border-t border-[var(--border)] flex justify-end">
                <button type="button" id="{{ $id }}_back_btn" class="py-1 px-2.5 rounded-lg text-[11px] text-[var(--text-muted)] hover:text-[var(--text)] font-semibold transition-colors cursor-pointer">
                    ← Kembali ke Bulan
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const rootId = '{{ $id }}';
    const container = document.getElementById(rootId + '_container');
    if (!container) return;

    const trigger = document.getElementById(rootId + '_trigger');
    const chevron = document.getElementById(rootId + '_chevron');
    const popup = document.getElementById(rootId + '_popup');

    const viewMonths = document.getElementById(rootId + '_view_months');
    const viewYears = document.getElementById(rootId + '_view_years');

    const displayedYearText = document.getElementById(rootId + '_displayed_year');
    const prevYearBtn = document.getElementById(rootId + '_prev_year');
    const nextYearBtn = document.getElementById(rootId + '_next_year');
    const yearBtn = document.getElementById(rootId + '_year_btn');
    const monthsGrid = document.getElementById(rootId + '_months_grid');

    const allYearBtn = document.getElementById(rootId + '_all_year_btn');
    const resetBtn = document.getElementById(rootId + '_reset_btn');

    const prevRangeBtn = document.getElementById(rootId + '_prev_range');
    const nextRangeBtn = document.getElementById(rootId + '_next_range');
    const rangeText = document.getElementById(rootId + '_range_text');
    const yearsGrid = document.getElementById(rootId + '_years_grid');
    const backBtn = document.getElementById(rootId + '_back_btn');

    const inputMonth = document.getElementById(rootId + '_input_month');
    const inputYear = document.getElementById(rootId + '_input_year');

    const months = [
        { num: 1, short: 'Jan' },
        { num: 2, short: 'Feb' },
        { num: 3, short: 'Mar' },
        { num: 4, short: 'Apr' },
        { num: 5, short: 'Mei' },
        { num: 6, short: 'Jun' },
        { num: 7, short: 'Jul' },
        { num: 8, short: 'Agu' },
        { num: 9, short: 'Sep' },
        { num: 10, short: 'Okt' },
        { num: 11, short: 'Nov' },
        { num: 12, short: 'Des' }
    ];

    const today = new Date();
    const todayMonth = today.getMonth() + 1;
    const todayYear = today.getFullYear();

    let selectedMonth = {{ $selectedMonth !== null ? (int)$selectedMonth : 'null' }};
    let selectedYear = {{ $selectedYear !== null ? (int)$selectedYear : 'null' }};

    let displayedYear = selectedYear || todayYear;
    let rangeStart = Math.floor(displayedYear / 12) * 12;

    function renderMonthsGrid() {
        if (displayedYearText) displayedYearText.textContent = displayedYear;
        if (!monthsGrid) return;
        monthsGrid.innerHTML = '';

        months.forEach(m => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = m.short;

            const isSelected = (selectedMonth === m.num && selectedYear === displayedYear);
            const isToday = (m.num === todayMonth && displayedYear === todayYear);

            let classes = 'py-2 px-1 text-center text-xs rounded-xl font-medium transition-all cursor-pointer ';
            if (isSelected) {
                classes += 'bg-blue-600 text-white font-bold shadow-xs hover:bg-blue-700';
            } else if (isToday) {
                classes += 'ring-1 ring-blue-500/80 text-blue-600 dark:text-blue-400 font-bold bg-blue-50/50 dark:bg-blue-950/30 hover:bg-blue-100/60 dark:hover:bg-blue-900/40';
            } else {
                classes += 'bg-[var(--surface-2)] hover:bg-[var(--border)] text-[var(--text)]';
            }

            btn.className = classes;
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                selectMonth(m.num, displayedYear);
            });
            monthsGrid.appendChild(btn);
        });
    }

    function renderYearsGrid() {
        if (rangeText) rangeText.textContent = `${rangeStart} - ${rangeStart + 11}`;
        if (!yearsGrid) return;
        yearsGrid.innerHTML = '';

        for (let y = rangeStart; y < rangeStart + 12; y++) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = y;

            const isCurrentDisplayed = (y === displayedYear);
            const isToday = (y === todayYear);

            let classes = 'py-2 px-1 text-center text-xs rounded-xl font-medium transition-all cursor-pointer ';
            if (isCurrentDisplayed) {
                classes += 'bg-blue-600 text-white font-bold shadow-xs hover:bg-blue-700';
            } else if (isToday) {
                classes += 'ring-1 ring-blue-500/80 text-blue-600 dark:text-blue-400 font-bold bg-blue-50/50 dark:bg-blue-950/30 hover:bg-blue-100/60 dark:hover:bg-blue-900/40';
            } else {
                classes += 'bg-[var(--surface-2)] hover:bg-[var(--border)] text-[var(--text)]';
            }

            btn.className = classes;
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                displayedYear = y;
                showMonthsView();
                renderMonthsGrid();
            });
            yearsGrid.appendChild(btn);
        }
    }

    function showMonthsView() {
        if (viewYears) viewYears.classList.add('hidden');
        if (viewMonths) viewMonths.classList.remove('hidden');
    }

    function showYearsView() {
        rangeStart = Math.floor(displayedYear / 12) * 12;
        renderYearsGrid();
        if (viewMonths) viewMonths.classList.add('hidden');
        if (viewYears) viewYears.classList.remove('hidden');
    }

    function openPopup() {
        showMonthsView();
        renderMonthsGrid();
        popup.classList.remove('hidden');
        trigger.setAttribute('aria-expanded', 'true');
        chevron.classList.add('rotate-180');

        // Adjust positioning if overflowing right
        const rect = popup.getBoundingClientRect();
        if (rect.right > window.innerWidth - 10) {
            popup.style.left = 'auto';
            popup.style.right = '0';
        } else {
            popup.style.left = '0';
            popup.style.right = 'auto';
        }
    }

    function closePopup() {
        popup.classList.add('hidden');
        trigger.setAttribute('aria-expanded', 'false');
        chevron.classList.remove('rotate-180');
    }

    function togglePopup() {
        if (popup.classList.contains('hidden')) {
            openPopup();
        } else {
            closePopup();
        }
    }

    function submitForm() {
        const form = container.closest('form');
        if (form) {
            form.submit();
        }
    }

    function selectMonth(month, year) {
        inputMonth.disabled = false;
        inputMonth.value = month;
        inputYear.disabled = false;
        inputYear.value = year;
        closePopup();
        submitForm();
    }

    // Trigger button toggle
    trigger.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        togglePopup();
    });

    // Navigation in Months view
    prevYearBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        displayedYear--;
        renderMonthsGrid();
    });

    nextYearBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        displayedYear++;
        renderMonthsGrid();
    });

    yearBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        showYearsView();
    });

    // Action buttons in Months view
    allYearBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        // Seluruh tahun: month is omitted, year is set
        inputMonth.value = '';
        inputMonth.disabled = true;
        inputYear.disabled = false;
        inputYear.value = displayedYear;
        closePopup();
        submitForm();
    });

    resetBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        // Hapus filter: both month and year are omitted
        inputMonth.value = '';
        inputMonth.disabled = true;
        inputYear.value = '';
        inputYear.disabled = true;
        closePopup();
        submitForm();
    });

    // Navigation in Years view
    prevRangeBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        rangeStart -= 12;
        renderYearsGrid();
    });

    nextRangeBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        rangeStart += 12;
        renderYearsGrid();
    });

    backBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        showMonthsView();
    });

    // Click outside to close
    document.addEventListener('click', function(e) {
        if (!container.contains(e.target)) {
            closePopup();
        }
    });

    // Press Escape to close
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' || e.key === 'Esc') {
            closePopup();
        }
    });
})();
</script>
