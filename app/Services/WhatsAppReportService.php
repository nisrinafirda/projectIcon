<?php

namespace App\Services;

use App\Models\ReportSetting;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WhatsAppReportService
{
    /**
     * Generate the complete WhatsApp monitoring report.
     */
    public function generateReport(): string
    {
        $sections = [
            $this->generateHeader(),
            $this->generateLegend(),
            $this->generateExceptionSection(),
            $this->generateSoOpenSection(),
            $this->generateBaaSection(),
            $this->generateContractExpiredSection(),
            $this->generateAccrualPublikSection(),
            $this->generateAccrualPlnSection(),
        ];

        return implode("\n\n", array_values(array_filter($sections, fn ($s) => ! empty($s))));
    }

    /**
     * Generate the header of the report (Publik & PLN breakdown).
     */
    public function generateHeader(): string
    {
        $now = Carbon::now('Asia/Jakarta');
        $year = $now->format('Y');
        $dateFormatted = $now->locale('id')->translatedFormat('d F Y');
        $timeFormatted = $now->format('H.i');

        // Status BAA Open = total nilai tugas BAA yang belum selesai
        // Status SO Open = total Harga Baru tugas SO Open yang belum selesai
        $baaTasks = Task::where('category', Task::CATEGORY_BAA)->get();
        $soTasks = Task::where('category', Task::CATEGORY_SSO_OPEN)->get();

        $baaPublikUnfinished = (float) $baaTasks->filter(function ($t) {
            return strtolower(trim((string) $t->kategori_segmen)) === 'publik'
                && ! $this->isTaskCompleted($t);
        })->sum(fn ($t) => $this->getTaskValue($t));

        $soPublikUnfinished = (float) $soTasks->filter(function ($t) {
            return strtolower(trim((string) $t->kategori_segmen)) === 'publik'
                && ! $this->isTaskCompleted($t);
        })->sum(fn ($t) => $this->getTaskValue($t));

        $baaPlnUnfinished = (float) $baaTasks->filter(function ($t) {
            return strtolower(trim((string) $t->kategori_segmen)) === 'pln'
                && ! $this->isTaskCompleted($t);
        })->sum(fn ($t) => $this->getTaskValue($t));

        $soPlnUnfinished = (float) $soTasks->filter(function ($t) {
            return strtolower(trim((string) $t->kategori_segmen)) === 'pln'
                && ! $this->isTaskCompleted($t);
        })->sum(fn ($t) => $this->getTaskValue($t));

        $oppPublik = (float) ReportSetting::getValue('opportunity_publik', 0);
        $revPublik = (float) ReportSetting::getValue('revenue_as_is_publik', 0);
        $oppPln = (float) ReportSetting::getValue('opportunity_pln', 0);
        $revPln = (float) ReportSetting::getValue('revenue_as_is_pln', 0);

        $fmt = fn (float $val) => 'Rp '.number_format($val, 0, ',', '.');

        return "📊 *UPDATE PROJECT MONITORING PPB {$year}*\n"
            ."🗓 *Tanggal:* {$dateFormatted} | 🕙 *Jam:* {$timeFormatted} WIB\n"
            ."━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
            ."*Publik*\n"
            ."-Status BAA Open {$fmt($baaPublikUnfinished)}\n"
            ."-Status SO Open {$fmt($soPublikUnfinished)}\n"
            ."-Status Opportunity {$fmt($oppPublik)}\n"
            ."-Revenue AS IS {$fmt($revPublik)}\n\n"
            ."*PLN*\n"
            ."-Status BAA Open {$fmt($baaPlnUnfinished)}\n"
            ."-Status SO Open {$fmt($soPlnUnfinished)}\n"
            ."-Status Opportunity {$fmt($oppPln)}\n"
            ."-Revenue AS IS {$fmt($revPln)}";
    }

    /**
     * Generate the Legend block.
     */
    public function generateLegend(): string
    {
        return "━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
            ."Note :\n"
            ."❌ = Belum selesai\n"
            ."✅ = Sudah selesai\n"
            ."☑️ = Terkonfirmasi\n"
            .'━━━━━━━━━━━━━━━━━━━━━━━━━━';
    }

    /**
     * Check if a task is considered completed (status selesai ATAU sudah dicentang user ATAU sudah dikonfirmasi admin).
     */
    public function isTaskCompleted(Task $task): bool
    {
        return in_array($task->status, [Task::STATUS_APPROVED, Task::STATUS_SUBMITTED], true)
            || ! empty($task->done_marked_at)
            || ! empty($task->confirmed_at);
    }

    /**
     * Check if a task is confirmed by admin (confirmed_at, confirmed_by, atau status approved).
     */
    public function isTaskConfirmed(Task $task): bool
    {
        return ! empty($task->confirmed_at)
            || ! empty($task->confirmed_by)
            || $task->status === Task::STATUS_APPROVED;
    }

    /**
     * Check if a completed task qualifies to be shown in the SELESAI / DONE (HARI INI) section.
     *
     * Aturan:
     * - Sudah dikonfirmasi admin (☑️): hanya jika tanggal konfirmasinya = hari ini (Asia/Jakarta).
     * - Sudah dicentang sales tapi belum dikonfirmasi (✅ saja): tetap tampil sampai dikonfirmasi, tidak peduli tanggalnya.
     * - Yang sudah ☑️ pada hari-hari sebelumnya tidak ditampilkan lagi.
     * - Jika confirmed_at kosong pada data lama terkonfirmasi, fallback ke updated_at.
     */
    public function isTaskEligibleForDoneToday(Task $task): bool
    {
        if (! $this->isTaskCompleted($task)) {
            return false;
        }

        if ($this->isTaskConfirmed($task)) {
            $confirmedDate = $task->confirmed_at ?? $task->updated_at;
            if (! $confirmedDate) {
                return false;
            }

            $todayStr = Carbon::now('Asia/Jakarta')->toDateString();
            $taskDateStr = Carbon::parse($confirmedDate)->timezone('Asia/Jakarta')->toDateString();

            return $taskDateStr === $todayStr;
        }

        // Sudah dicentang sales tapi belum dikonfirmasi: tetap tampil
        return true;
    }

    /**
     * Get monetary value of a task with fallback to parsing description.
     */
    public function getTaskValue(Task $task): float
    {
        if (! empty($task->harga_baru) && (float) $task->harga_baru > 0) {
            return (float) $task->harga_baru;
        }

        if (! empty($task->rp_exception) && (float) $task->rp_exception > 0) {
            return (float) $task->rp_exception;
        }

        // Fallback: parse nominal dari deskripsi jika ada format "Harga Baru: Rp ..."
        if (! empty($task->description) && preg_match('/Harga Baru:\s*Rp\s*([\d\.,]+)/i', (string) $task->description, $matches)) {
            $clean = str_replace(['.', ' '], '', $matches[1]);
            $clean = str_replace(',', '.', $clean);

            return is_numeric($clean) ? (float) $clean : 0.0;
        }

        return 0.0;
    }

    /**
     * Determine status marker for a collection of customer tasks.
     *
     * @param  Collection<int, Task>  $tasks
     */
    public function getCustomerStatusMarker(Collection $tasks): string
    {
        $hasUnfinished = $tasks->contains(fn ($t) => ! $this->isTaskCompleted($t));
        if ($hasUnfinished) {
            return '❌';
        }

        $allConfirmed = $tasks->every(fn ($t) => $this->isTaskConfirmed($t));

        return $allConfirmed ? '✅☑️' : '✅';
    }

    /**
     * Check if a customer name is considered a "desa".
     */
    public function isDesaCustomer(string $customerName): bool
    {
        $upper = strtoupper(trim($customerName));

        if (str_starts_with($upper, 'BENDAHARA PENGELUARAN DINAS')) {
            return false;
        }

        return str_starts_with($upper, 'DESA ')
            || str_starts_with($upper, 'BENDAHARA DESA')
            || str_starts_with($upper, 'BEND DESA')
            || str_starts_with($upper, 'BEND. DESA');
    }

    /**
     * Determine termin period for a desa based on kendala text.
     */
    public function getDesaTerminPeriod(?string $kendala): string
    {
        $lower = strtolower((string) $kendala);

        if (str_contains($lower, 'okt') && str_contains($lower, 'nov') && str_contains($lower, 'des')) {
            return 'TERMIN OKT s.d DES';
        }

        if (str_contains($lower, 'juli') && (str_contains($lower, 'sept') || str_contains($lower, 'sep'))) {
            return 'TERMIN JUL s.d SEP';
        }

        if (str_contains($lower, 'termin 3 bulan') || str_contains($lower, 'termin')) {
            return 'TERMIN OKT s.d DES';
        }

        return 'TERMIN OKT s.d DES';
    }

    /**
     * Extract PIC first name(s) in uppercase, joined by '/' if multiple.
     *
     * @param  Collection<int, Task>  $tasks
     */
    public function extractPicName(Collection $tasks): string
    {
        $names = $tasks->map(function ($t) {
            $name = optional($t->user)->name ?: $t->sales_name;
            if (! $name) {
                return null;
            }
            $parts = preg_split('/\s+/', trim((string) $name));

            return strtoupper($parts[0] ?? '');
        })->filter()->unique()->values();

        return $names->isNotEmpty() ? $names->implode('/') : '-';
    }

    /**
     * Generate Section 1: EXCEPTION (STATUS: EXCEPTION OPEN).
     */
    public function generateExceptionSection(): string
    {
        $tasks = Task::where('category', Task::CATEGORY_EXCEPTION)
            ->with(['user'])
            ->get();

        $grouped = $tasks->groupBy(fn ($t) => trim((string) $t->customer_name));

        $onProcessCustomers = [];
        $desaGroups = [];
        $nonDesaCustomers = [];
        $doneAllCustomers = [];
        $doneTodayCustomers = [];

        foreach ($grouped as $customerName => $custTasks) {
            $unfinishedTasks = $custTasks->filter(fn ($t) => ! $this->isTaskCompleted($t));
            $allFinishedTasks = $custTasks->filter(fn ($t) => $this->isTaskCompleted($t));
            $todayFinishedTasks = $allFinishedTasks->filter(fn ($t) => $this->isTaskEligibleForDoneToday($t));

            if ($unfinishedTasks->isNotEmpty()) {
                $totalRp = (float) $unfinishedTasks->sum(fn ($t) => (float) ($t->rp_exception ?? 0));
                $kendala = $unfinishedTasks->map(fn ($t) => $t->comments ?: $t->late_reason)->filter()->last() ?: '-';
                $pic = $this->extractPicName($unfinishedTasks);

                $custItem = [
                    'name' => $customerName ?: 'Tanpa Nama Pelanggan',
                    'count' => $unfinishedTasks->count(),
                    'total_rp' => $totalRp,
                    'pic' => $pic,
                    'kendala' => $kendala,
                    'marker' => '❌',
                    'tasks' => $unfinishedTasks,
                ];

                $onProcessCustomers[] = $custItem;

                if ($this->isDesaCustomer($customerName ?: '')) {
                    $periode = $this->getDesaTerminPeriod($kendala);
                    $desaGroups[$periode][] = $custItem;
                } else {
                    $nonDesaCustomers[] = $custItem;
                }
            }

            if ($allFinishedTasks->isNotEmpty()) {
                $totalRp = (float) $allFinishedTasks->sum(fn ($t) => (float) ($t->rp_exception ?? 0));

                $doneAllCustomers[] = [
                    'name' => $customerName ?: 'Tanpa Nama Pelanggan',
                    'count' => $allFinishedTasks->count(),
                    'total_rp' => $totalRp,
                ];
            }

            if ($todayFinishedTasks->isNotEmpty()) {
                $totalRp = (float) $todayFinishedTasks->sum(fn ($t) => (float) ($t->rp_exception ?? 0));
                $progress = $todayFinishedTasks->map(fn ($t) => $t->comments ?: $t->submission_notes ?: $t->late_reason)->filter()->last() ?: '-';

                $doneTodayCustomers[] = [
                    'name' => $customerName ?: 'Tanpa Nama Pelanggan',
                    'count' => $todayFinishedTasks->count(),
                    'total_rp' => $totalRp,
                    'pic' => $this->extractPicName($todayFinishedTasks),
                    'progress' => $progress,
                    'marker' => $this->getCustomerStatusMarker($todayFinishedTasks),
                ];
            }
        }

        $fmtRp = fn (float $val) => 'Rp '.number_format($val, 0, ',', '.');

        // Build display items for ON PROCESS
        $displayItems = [];

        // 1. Non-desa customers
        foreach ($nonDesaCustomers as $cust) {
            $displayItems[] = [
                'type' => 'customer',
                'total_rp' => $cust['total_rp'],
                'data' => $cust,
            ];
        }

        // 2. Desa groups
        foreach ($desaGroups as $periode => $desaList) {
            $totalGroupRp = (float) array_sum(array_column($desaList, 'total_rp'));
            $desaCount = count($desaList);

            // Group kendala from representative comments
            $allTasks = collect($desaList)->flatMap(fn ($c) => $c['tasks']);
            $kendalas = $allTasks->map(fn ($t) => $t->comments ?: $t->late_reason)->filter()->values();
            $groupKendala = $kendalas->countBy()->sortDesc()->keys()->first() ?: '-';

            // Group PICs
            $picGroups = [];
            foreach ($desaList as $c) {
                $p = $c['pic'];
                if (! isset($picGroups[$p])) {
                    $picGroups[$p] = [
                        'upper' => strtoupper($p),
                        'title' => ucfirst(strtolower($p)),
                        'count' => 0,
                        'subtotal' => 0.0,
                    ];
                }
                $picGroups[$p]['count']++;
                $picGroups[$p]['subtotal'] += $c['total_rp'];
            }

            usort($picGroups, fn ($a, $b) => $b['count'] <=> $a['count'] ?: ($b['subtotal'] <=> $a['subtotal'] ?: strcasecmp($a['title'], $b['title'])));

            $picSummary = implode(', ', array_map(fn ($p) => "{$p['upper']} ({$p['count']})", $picGroups));

            $breakdownLines = [];
            if ($desaCount > 1) {
                foreach ($picGroups as $p) {
                    $breakdownLines[] = "{$p['title']}: {$p['count']} desa | {$fmtRp($p['subtotal'])}";
                }
            } else {
                $single = $desaList[0];
                $picTitle = ucfirst(strtolower($single['pic']));
                $breakdownLines[] = "{$picTitle}: {$single['name']} | {$fmtRp($single['total_rp'])}";
            }

            $displayItems[] = [
                'type' => 'desa_group',
                'total_rp' => $totalGroupRp,
                'periode' => $periode,
                'customer_count' => $desaCount,
                'pic_summary' => $picSummary,
                'kendala' => $groupKendala,
                'breakdown_lines' => $breakdownLines,
            ];
        }

        // Urut item on-process dari total_rp terbesar
        usort($displayItems, fn ($a, $b) => $b['total_rp'] <=> $a['total_rp']);
        usort($doneTodayCustomers, fn ($a, $b) => $b['total_rp'] <=> $a['total_rp']);

        // Rekapitulasi: per individual pelanggan (Rule 6)
        $totalRpBelum = (float) array_sum(array_column($onProcessCustomers, 'total_rp'));
        $custBelum = count($onProcessCustomers);
        $sidBelum = (int) array_sum(array_column($onProcessCustomers, 'count'));

        $totalRpSelesai = (float) array_sum(array_column($doneAllCustomers, 'total_rp'));
        $custSelesai = count($doneAllCustomers);
        $sidSelesai = (int) array_sum(array_column($doneAllCustomers, 'count'));

        $totalRpToday = (float) array_sum(array_column($doneTodayCustomers, 'total_rp'));
        $custToday = count($doneTodayCustomers);
        $sidToday = (int) array_sum(array_column($doneTodayCustomers, 'count'));

        $totalRpAll = (float) $tasks->sum(fn ($t) => (float) ($t->rp_exception ?? 0));
        $custAll = $grouped->count();
        $sidAll = $tasks->count();

        $lines = [
            '📌 *1. EXCEPTION (STATUS: EXCEPTION OPEN)*',
            '*📊 REKAPITULASI*',
            "• *TOTAL KESELURUHAN* : {$fmtRp($totalRpAll)} ({$custAll} Pelanggan | {$sidAll} SID)",
            '----------------------------------------------------------',
            "• *SELESAI* : {$fmtRp($totalRpSelesai)} ({$custSelesai} Pelanggan | {$sidSelesai} SID)",
            "• *BELUM SELESAI* : {$fmtRp($totalRpBelum)} ({$custBelum} Pelanggan | {$sidBelum} SID)",
            '',
            '[❌] *BELUM SELESAI / ON PROCESS:*',
            "*Total: {$fmtRp($totalRpBelum)} ({$custBelum} Pelanggan/PIC | {$sidBelum} SID)*",
        ];

        if (empty($displayItems)) {
            $lines[] = '- Nihil';
        } else {
            $num = 1;
            foreach ($displayItems as $item) {
                if ($item['type'] === 'desa_group') {
                    $lines[] = "{$num}. *DESA / BENDAHARA DESA - {$item['periode']} ({$item['customer_count']} Pelanggan)* | PIC: {$item['pic_summary']} ❌";
                    $lines[] = "   ↳ Nilai: {$fmtRp($item['total_rp'])} | Kendala: {$item['kendala']}";
                    foreach ($item['breakdown_lines'] as $bLine) {
                        $lines[] = "   • {$bLine}";
                    }
                } else {
                    $cust = $item['data'];
                    $itemLabel = $cust['count'] > 1 ? " ({$cust['count']} item)" : '';
                    $lines[] = "{$num}. *{$cust['name']}{$itemLabel}* | PIC: {$cust['pic']} ❌";
                    $lines[] = "   ↳ Nilai: {$fmtRp($cust['total_rp'])} | Kendala: {$cust['kendala']}";
                }
                $num++;
            }
        }

        $lines[] = '';
        $lines[] = '[✅] *SELESAI / DONE (HARI INI):*';

        if (empty($doneTodayCustomers)) {
            $lines[] = 'Belum ada penyelesaian hari ini';
        } else {
            $lines[] = "*Total: {$fmtRp($totalRpToday)} ({$custToday} Pelanggan/PIC | {$sidToday} SID)*";
            $num = 1;
            foreach ($doneTodayCustomers as $cust) {
                $itemLabel = $cust['count'] > 1 ? " ({$cust['count']} item)" : '';
                $lines[] = "{$num}. *{$cust['name']}{$itemLabel}* | PIC: {$cust['pic']} {$cust['marker']}";
                $lines[] = "   ↳ Nilai: {$fmtRp($cust['total_rp'])} | Progress: {$cust['progress']}";
                $num++;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Generate Section 2: SO OPEN.
     */
    public function generateSoOpenSection(): string
    {
        $tasks = Task::where('category', Task::CATEGORY_SSO_OPEN)
            ->with(['user'])
            ->get();

        $grouped = $tasks->groupBy(fn ($t) => trim((string) $t->customer_name));

        $onProcessCustomers = [];
        $doneAllCustomers = [];
        $doneTodayCustomers = [];

        foreach ($grouped as $customerName => $custTasks) {
            $unfinishedTasks = $custTasks->filter(fn ($t) => ! $this->isTaskCompleted($t));
            $allFinishedTasks = $custTasks->filter(fn ($t) => $this->isTaskCompleted($t));
            $todayFinishedTasks = $allFinishedTasks->filter(fn ($t) => $this->isTaskEligibleForDoneToday($t));

            if ($unfinishedTasks->isNotEmpty()) {
                $kendala = $unfinishedTasks->map(fn ($t) => $t->comments ?: $t->late_reason)->filter()->last() ?: '-';

                $onProcessCustomers[] = [
                    'name' => $customerName ?: 'Tanpa Nama Pelanggan',
                    'count' => $unfinishedTasks->count(),
                    'pic' => $this->extractPicName($unfinishedTasks),
                    'kendala' => $kendala,
                    'marker' => '❌',
                ];
            }

            if ($allFinishedTasks->isNotEmpty()) {
                $doneAllCustomers[] = [
                    'name' => $customerName ?: 'Tanpa Nama Pelanggan',
                    'count' => $allFinishedTasks->count(),
                ];
            }

            if ($todayFinishedTasks->isNotEmpty()) {
                $progress = $todayFinishedTasks->map(fn ($t) => $t->comments ?: $t->submission_notes ?: $t->late_reason)->filter()->last() ?: '-';

                $doneTodayCustomers[] = [
                    'name' => $customerName ?: 'Tanpa Nama Pelanggan',
                    'count' => $todayFinishedTasks->count(),
                    'pic' => $this->extractPicName($todayFinishedTasks),
                    'progress' => $progress,
                    'marker' => $this->getCustomerStatusMarker($todayFinishedTasks),
                ];
            }
        }

        usort($onProcessCustomers, fn ($a, $b) => $b['count'] <=> $a['count'] ?: strcasecmp($a['name'], $b['name']));
        usort($doneTodayCustomers, fn ($a, $b) => $b['count'] <=> $a['count'] ?: strcasecmp($a['name'], $b['name']));

        // Rekapitulasi: total keseluruhan selesai (semua yang selesai, bukan cuma hari ini)
        $custSelesai = count($doneAllCustomers);
        $sidSelesai = (int) array_sum(array_column($doneAllCustomers, 'count'));

        // Selesai hari ini
        $custToday = count($doneTodayCustomers);
        $sidToday = (int) array_sum(array_column($doneTodayCustomers, 'count'));

        $custBelum = count($onProcessCustomers);
        $sidBelum = (int) array_sum(array_column($onProcessCustomers, 'count'));

        $custTotal = $grouped->count();
        $sidTotal = $tasks->count();

        $lines = [
            '📌 *2. SO OPEN*',
            '*📊 REKAPITULASI*',
            "• *TOTAL KESELURUHAN* : {$custTotal} Pelanggan | {$sidTotal} SID",
            '----------------------------------------------------------',
            "• *SELESAI* : {$custSelesai} Pelanggan | {$sidSelesai} SID",
            "• *BELUM SELESAI* : {$custBelum} Pelanggan | {$sidBelum} SID",
            '',
            '[❌] *BELUM SELESAI / ON PROCESS:*',
            "*Total: {$custBelum} Pelanggan/PIC | {$sidBelum} SID*",
        ];

        if (empty($onProcessCustomers)) {
            $lines[] = '- Nihil';
        } else {
            $num = 1;
            foreach ($onProcessCustomers as $cust) {
                $itemLabel = $cust['count'] > 1 ? " ({$cust['count']} item)" : '';
                $lines[] = "{$num}. *{$cust['name']}{$itemLabel}* | PIC: {$cust['pic']} ❌";
                $lines[] = "   ↳ Kendala: {$cust['kendala']}";
                $num++;
            }
        }

        $lines[] = '';
        $lines[] = '[✅] *SELESAI / DONE (HARI INI):*';

        if (empty($doneTodayCustomers)) {
            $lines[] = 'Belum ada penyelesaian hari ini';
        } else {
            $lines[] = "*Total: {$custToday} Pelanggan/PIC | {$sidToday} SID*";
            $num = 1;
            foreach ($doneTodayCustomers as $cust) {
                $itemLabel = $cust['count'] > 1 ? " ({$cust['count']} item)" : '';
                $lines[] = "{$num}. *{$cust['name']}{$itemLabel}* | PIC: {$cust['pic']} {$cust['marker']}";
                $lines[] = "   ↳ Progress: {$cust['progress']}";
                $num++;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Generate Section 3: BAA OPEN.
     */
    public function generateBaaSection(): string
    {
        $tasks = Task::where('category', Task::CATEGORY_BAA)
            ->with(['user'])
            ->get();

        $grouped = $tasks->groupBy(fn ($t) => trim((string) $t->customer_name));

        $onProcessCustomers = [];
        $doneAllCustomers = [];
        $doneTodayCustomers = [];

        foreach ($grouped as $customerName => $custTasks) {
            $unfinishedTasks = $custTasks->filter(fn ($t) => ! $this->isTaskCompleted($t));
            $allFinishedTasks = $custTasks->filter(fn ($t) => $this->isTaskCompleted($t));
            $todayFinishedTasks = $allFinishedTasks->filter(fn ($t) => $this->isTaskEligibleForDoneToday($t));

            if ($unfinishedTasks->isNotEmpty()) {
                $salesNames = $unfinishedTasks->map(fn ($t) => $t->sales_name ?: optional($t->user)->name)->filter()->unique()->values();
                $salesStr = $salesNames->isNotEmpty() ? $salesNames->implode(', ') : '-';

                $maxAging = $unfinishedTasks->map(function ($t) {
                    $refDate = $t->start_date ?? $t->created_at;

                    return $refDate ? (int) $refDate->diffInDays(now()) : 0;
                })->max() ?? 0;

                $totalNilai = (float) $unfinishedTasks->sum(fn ($t) => $this->getTaskValue($t));
                $kendala = $unfinishedTasks->map(fn ($t) => $t->comments ?: $t->late_reason)->filter()->last() ?: '-';

                $onProcessCustomers[] = [
                    'name' => $customerName ?: 'Tanpa Nama Pelanggan',
                    'count' => $unfinishedTasks->count(),
                    'sales' => $salesStr,
                    'aging' => $maxAging,
                    'nilai' => $totalNilai,
                    'kendala' => $kendala,
                    'marker' => '❌',
                ];
            }

            if ($allFinishedTasks->isNotEmpty()) {
                $doneAllCustomers[] = [
                    'name' => $customerName ?: 'Tanpa Nama Pelanggan',
                    'count' => $allFinishedTasks->count(),
                ];
            }

            if ($todayFinishedTasks->isNotEmpty()) {
                $salesNames = $todayFinishedTasks->map(fn ($t) => $t->sales_name ?: optional($t->user)->name)->filter()->unique()->values();
                $salesStr = $salesNames->isNotEmpty() ? $salesNames->implode(', ') : '-';

                $maxAging = $todayFinishedTasks->map(function ($t) {
                    $refDate = $t->start_date ?? $t->created_at;

                    return $refDate ? (int) $refDate->diffInDays(now()) : 0;
                })->max() ?? 0;

                $totalNilai = (float) $todayFinishedTasks->sum(fn ($t) => $this->getTaskValue($t));
                $progress = $todayFinishedTasks->map(fn ($t) => $t->comments ?: $t->submission_notes ?: $t->late_reason)->filter()->last() ?: '-';

                $doneTodayCustomers[] = [
                    'name' => $customerName ?: 'Tanpa Nama Pelanggan',
                    'count' => $todayFinishedTasks->count(),
                    'sales' => $salesStr,
                    'aging' => $maxAging,
                    'nilai' => $totalNilai,
                    'progress' => $progress,
                    'marker' => $this->getCustomerStatusMarker($todayFinishedTasks),
                ];
            }
        }

        $unfinishedCount = (int) array_sum(array_column($onProcessCustomers, 'count'));
        $finishedCount = (int) array_sum(array_column($doneAllCustomers, 'count'));
        $totalItem = $finishedCount + $unfinishedCount;
        $custToday = count($doneTodayCustomers);
        $sidToday = (int) array_sum(array_column($doneTodayCustomers, 'count'));

        $fmtRp = fn (float $val) => 'Rp '.number_format($val, 0, ',', '.');

        $lines = [
            '📌 *3. BAA OPEN*',
            '*📊 REKAPITULASI*',
            "• *TOTAL KESELURUHAN* : {$totalItem} Item",
            '----------------------------------------------------------',
            "• *SELESAI* : {$finishedCount} Item",
            "• *BELUM SELESAI* : {$unfinishedCount} Item",
            '',
            '[❌] *BELUM SELESAI / ON PROCESS:*',
        ];

        if (empty($onProcessCustomers)) {
            $lines[] = '- Nihil';
        } else {
            $num = 1;
            foreach ($onProcessCustomers as $cust) {
                $lines[] = "{$num}. *{$cust['name']}* | {$cust['count']} ID PA ❌";
                $lines[] = "   • Sales: {$cust['sales']} | Aging: {$cust['aging']} Hari | Nilai: {$fmtRp($cust['nilai'])}";
                $lines[] = "   • Kendala: {$cust['kendala']}";
                $num++;
            }
        }

        $lines[] = '';
        $lines[] = '[✅] *SELESAI / DONE (HARI INI):*';

        if (empty($doneTodayCustomers)) {
            $lines[] = 'Belum ada penyelesaian hari ini';
        } else {
            $num = 1;
            foreach ($doneTodayCustomers as $cust) {
                $lines[] = "{$num}. *{$cust['name']}* | {$cust['count']} ID PA {$cust['marker']}";
                $lines[] = "   • Sales: {$cust['sales']} | Aging: {$cust['aging']} Hari | Nilai: {$fmtRp($cust['nilai'])}";
                $lines[] = "   • Progress: {$cust['progress']}";
                $num++;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Generate Section 4: CONTRACT EXPIRED.
     */
    public function generateContractExpiredSection(): string
    {
        $tasks = Task::where('category', Task::CATEGORY_KONTRAK_EXP)
            ->with(['user'])
            ->get();

        $grouped = $tasks->groupBy(fn ($t) => trim((string) $t->customer_name));

        $onProcessCustomers = [];
        $doneAllCustomers = [];
        $doneTodayCustomers = [];

        foreach ($grouped as $customerName => $custTasks) {
            $unfinishedTasks = $custTasks->filter(fn ($t) => ! $this->isTaskCompleted($t));
            $allFinishedTasks = $custTasks->filter(fn ($t) => $this->isTaskCompleted($t));
            $todayFinishedTasks = $allFinishedTasks->filter(fn ($t) => $this->isTaskEligibleForDoneToday($t));

            if ($unfinishedTasks->isNotEmpty()) {
                $nearestDue = $unfinishedTasks->min('due_date');
                $dates = $unfinishedTasks->map(fn ($t) => $t->due_date ? $t->due_date->format('d/m/Y') : null)->filter()->unique()->values();
                $expStr = $dates->isNotEmpty() ? $dates->implode(' & ') : '-';

                $salesNames = $unfinishedTasks->map(fn ($t) => $t->sales_name ?: optional($t->user)->name)->filter()->unique()->values();
                $salesStr = $salesNames->isNotEmpty() ? $salesNames->implode(', ') : '-';
                $kendala = $unfinishedTasks->map(fn ($t) => $t->comments ?: $t->late_reason)->filter()->last() ?: '-';

                $onProcessCustomers[] = [
                    'name' => $customerName ?: 'Tanpa Nama Pelanggan',
                    'count' => $unfinishedTasks->count(),
                    'nearest_due' => $nearestDue,
                    'exp_date' => $expStr,
                    'sales' => $salesStr,
                    'kendala' => $kendala,
                    'marker' => '❌',
                ];
            }

            if ($allFinishedTasks->isNotEmpty()) {
                $doneAllCustomers[] = [
                    'name' => $customerName ?: 'Tanpa Nama Pelanggan',
                    'count' => $allFinishedTasks->count(),
                ];
            }

            if ($todayFinishedTasks->isNotEmpty()) {
                $nearestDue = $todayFinishedTasks->min('due_date');
                $dates = $todayFinishedTasks->map(fn ($t) => $t->due_date ? $t->due_date->format('d/m/Y') : null)->filter()->unique()->values();
                $expStr = $dates->isNotEmpty() ? $dates->implode(' & ') : '-';

                $salesNames = $todayFinishedTasks->map(fn ($t) => $t->sales_name ?: optional($t->user)->name)->filter()->unique()->values();
                $salesStr = $salesNames->isNotEmpty() ? $salesNames->implode(', ') : '-';
                $progress = $todayFinishedTasks->map(fn ($t) => $t->comments ?: $t->submission_notes ?: $t->late_reason)->filter()->last() ?: '-';

                $doneTodayCustomers[] = [
                    'name' => $customerName ?: 'Tanpa Nama Pelanggan',
                    'count' => $todayFinishedTasks->count(),
                    'nearest_due' => $nearestDue,
                    'exp_date' => $expStr,
                    'sales' => $salesStr,
                    'progress' => $progress,
                    'marker' => $this->getCustomerStatusMarker($todayFinishedTasks),
                ];
            }
        }

        // Urut berdasarkan tanggal expired terdekat
        $sortExp = function ($a, $b) {
            if ($a['nearest_due'] && $b['nearest_due']) {
                return $a['nearest_due']->getTimestamp() <=> $b['nearest_due']->getTimestamp();
            }

            return $a['nearest_due'] ? -1 : 1;
        };

        usort($onProcessCustomers, $sortExp);
        usort($doneTodayCustomers, $sortExp);

        $unfinishedCount = (int) array_sum(array_column($onProcessCustomers, 'count'));
        $finishedCount = (int) array_sum(array_column($doneAllCustomers, 'count'));
        $totalTasks = $tasks->count();
        $custToday = count($doneTodayCustomers);
        $sidToday = (int) array_sum(array_column($doneTodayCustomers, 'count'));

        $lines = [
            '📌 *4. CONTRACT EXPIRED*',
            '*📊 REKAPITULASI*',
            "• *TOTAL KESELURUHAN* : {$totalTasks} Pekerjaan",
            '----------------------------------------------------------',
            "• *SELESAI* : {$finishedCount} Pekerjaan",
            "• *BELUM SELESAI* : {$unfinishedCount} Pekerjaan",
            '',
            '[❌] *BELUM SELESAI / ON PROCESS:*',
        ];

        if (empty($onProcessCustomers)) {
            $lines[] = '- Nihil';
        } else {
            $num = 1;
            foreach ($onProcessCustomers as $cust) {
                $itemLabel = $cust['count'] > 1 ? " ({$cust['count']} SID)" : '';
                $lines[] = "{$num}. *{$cust['name']}{$itemLabel}* | Exp: {$cust['exp_date']} ❌";
                $lines[] = "   • Sales: {$cust['sales']} | Kendala: {$cust['kendala']}";
                $num++;
            }
        }

        $lines[] = '';
        $lines[] = '[✅] *SELESAI / DONE (HARI INI):*';

        if (empty($doneTodayCustomers)) {
            $lines[] = 'Belum ada penyelesaian hari ini';
        } else {
            $num = 1;
            foreach ($doneTodayCustomers as $cust) {
                $itemLabel = $cust['count'] > 1 ? " ({$cust['count']} SID)" : '';
                $lines[] = "{$num}. *{$cust['name']}{$itemLabel}* | Exp: {$cust['exp_date']} {$cust['marker']}";
                $lines[] = "   • Sales: {$cust['sales']} | Progress: {$cust['progress']}";
                $num++;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Placeholder: Section Accrual Publik (modular).
     */
    public function generateAccrualPublikSection(): ?string
    {
        return null;
    }

    /**
     * Placeholder: Section Accrual PLN (modular).
     */
    public function generateAccrualPlnSection(): ?string
    {
        return null;
    }
}
