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
     * Check if a task is confirmed by admin (confirmed_at atau confirmed_by terisi).
     */
    public function isTaskConfirmed(Task $task): bool
    {
        return ! empty($task->confirmed_at) || ! empty($task->confirmed_by);
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
        $doneCustomers = [];

        foreach ($grouped as $customerName => $custTasks) {
            $unfinishedTasks = $custTasks->filter(fn ($t) => ! $this->isTaskCompleted($t));
            $finishedTasks = $custTasks->filter(fn ($t) => $this->isTaskCompleted($t));

            if ($unfinishedTasks->isNotEmpty()) {
                $totalRp = (float) $unfinishedTasks->sum(fn ($t) => (float) ($t->rp_exception ?? 0));
                $kendala = $unfinishedTasks->map(fn ($t) => $t->comments ?: $t->late_reason)->filter()->last() ?: '-';

                $onProcessCustomers[] = [
                    'name' => $customerName ?: 'Tanpa Nama Pelanggan',
                    'count' => $unfinishedTasks->count(),
                    'total_rp' => $totalRp,
                    'pic' => $this->extractPicName($unfinishedTasks),
                    'kendala' => $kendala,
                    'marker' => '❌',
                ];
            }

            if ($finishedTasks->isNotEmpty()) {
                $totalRp = (float) $finishedTasks->sum(fn ($t) => (float) ($t->rp_exception ?? 0));
                $progress = $finishedTasks->map(fn ($t) => $t->comments ?: $t->submission_notes ?: $t->late_reason)->filter()->last() ?: '-';

                $doneCustomers[] = [
                    'name' => $customerName ?: 'Tanpa Nama Pelanggan',
                    'count' => $finishedTasks->count(),
                    'total_rp' => $totalRp,
                    'pic' => $this->extractPicName($finishedTasks),
                    'progress' => $progress,
                    'marker' => $this->getCustomerStatusMarker($finishedTasks),
                ];
            }
        }

        // Urut nominal rp_exception terbesar
        usort($onProcessCustomers, fn ($a, $b) => $b['total_rp'] <=> $a['total_rp']);
        usort($doneCustomers, fn ($a, $b) => $b['total_rp'] <=> $a['total_rp']);

        $totalRpBelum = (float) array_sum(array_column($onProcessCustomers, 'total_rp'));
        $custBelum = count($onProcessCustomers);
        $sidBelum = (int) array_sum(array_column($onProcessCustomers, 'count'));

        $totalRpSelesai = (float) array_sum(array_column($doneCustomers, 'total_rp'));
        $custSelesai = count($doneCustomers);
        $sidSelesai = (int) array_sum(array_column($doneCustomers, 'count'));

        $totalRpAll = (float) $tasks->sum(fn ($t) => (float) ($t->rp_exception ?? 0));
        $custAll = $grouped->count();
        $sidAll = $tasks->count();

        $fmtRp = fn (float $val) => 'Rp '.number_format($val, 0, ',', '.');

        $lines = [
            '📌 *1. EXCEPTION (STATUS: EXCEPTION OPEN)*',
            '*📊 REKAPITULASI*',
            "• *BELUM SELESAI* : {$fmtRp($totalRpBelum)} ({$custBelum} Pelanggan | {$sidBelum} SID)",
            "• *SELESAI* : {$fmtRp($totalRpSelesai)} ({$custSelesai} Pelanggan | {$sidSelesai} SID)",
            '----------------------------------------------------------',
            "*TOTAL KESELURUHAN : {$fmtRp($totalRpAll)} ({$custAll} Pelanggan | {$sidAll} SID)*",
            '',
            '[❌] *BELUM SELESAI / ON PROCESS:*',
            "*Total: {$fmtRp($totalRpBelum)} ({$custBelum} Pelanggan/PIC | {$sidBelum} SID)*",
        ];

        if (empty($onProcessCustomers)) {
            $lines[] = '- Nihil';
        } else {
            $num = 1;
            foreach ($onProcessCustomers as $cust) {
                $itemLabel = $cust['count'] > 1 ? " ({$cust['count']} item)" : '';
                $lines[] = "{$num}. *{$cust['name']}{$itemLabel}* | PIC: {$cust['pic']} ❌";
                $lines[] = "   ↳ Nilai: {$fmtRp($cust['total_rp'])} | Kendala: {$cust['kendala']}";
                $num++;
            }
        }

        $lines[] = '';
        $lines[] = '[✅] *SELESAI / DONE:*';
        $lines[] = "*Total: {$fmtRp($totalRpSelesai)} ({$custSelesai} Pelanggan/PIC | {$sidSelesai} SID)*";

        if (empty($doneCustomers)) {
            $lines[] = '- Nihil';
        } else {
            $num = 1;
            foreach ($doneCustomers as $cust) {
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
        $doneCustomers = [];

        foreach ($grouped as $customerName => $custTasks) {
            $unfinishedTasks = $custTasks->filter(fn ($t) => ! $this->isTaskCompleted($t));
            $finishedTasks = $custTasks->filter(fn ($t) => $this->isTaskCompleted($t));

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

            if ($finishedTasks->isNotEmpty()) {
                $progress = $finishedTasks->map(fn ($t) => $t->comments ?: $t->submission_notes ?: $t->late_reason)->filter()->last() ?: '-';

                $doneCustomers[] = [
                    'name' => $customerName ?: 'Tanpa Nama Pelanggan',
                    'count' => $finishedTasks->count(),
                    'pic' => $this->extractPicName($finishedTasks),
                    'progress' => $progress,
                    'marker' => $this->getCustomerStatusMarker($finishedTasks),
                ];
            }
        }

        usort($onProcessCustomers, fn ($a, $b) => $b['count'] <=> $a['count'] ?: strcasecmp($a['name'], $b['name']));
        usort($doneCustomers, fn ($a, $b) => $b['count'] <=> $a['count'] ?: strcasecmp($a['name'], $b['name']));

        $custSelesai = count($doneCustomers);
        $sidSelesai = (int) array_sum(array_column($doneCustomers, 'count'));

        $custBelum = count($onProcessCustomers);
        $sidBelum = (int) array_sum(array_column($onProcessCustomers, 'count'));

        $custTotal = $grouped->count();
        $sidTotal = $tasks->count();

        $lines = [
            '📌 *2. SO OPEN*',
            '📊 REKAPITULASI GLOBAL PEKERJAAN',
            "SELESAI / DONE : {$custSelesai} Pelanggan | {$sidSelesai} SID",
            "ON PROGRESS / KENDALA : {$custBelum} Pelanggan | {$sidBelum} SID",
            "TOTAL KESELURUHAN : {$custTotal} Pelanggan | {$sidTotal} SID",
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
        $lines[] = '[✅] *SELESAI / DONE:*';
        $lines[] = "*Total: {$custSelesai} Pelanggan/PIC | {$sidSelesai} SID*";

        if (empty($doneCustomers)) {
            $lines[] = '- Nihil';
        } else {
            $num = 1;
            foreach ($doneCustomers as $cust) {
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
        $doneCustomers = [];

        foreach ($grouped as $customerName => $custTasks) {
            $unfinishedTasks = $custTasks->filter(fn ($t) => ! $this->isTaskCompleted($t));
            $finishedTasks = $custTasks->filter(fn ($t) => $this->isTaskCompleted($t));

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

            if ($finishedTasks->isNotEmpty()) {
                $salesNames = $finishedTasks->map(fn ($t) => $t->sales_name ?: optional($t->user)->name)->filter()->unique()->values();
                $salesStr = $salesNames->isNotEmpty() ? $salesNames->implode(', ') : '-';

                $maxAging = $finishedTasks->map(function ($t) {
                    $refDate = $t->start_date ?? $t->created_at;

                    return $refDate ? (int) $refDate->diffInDays(now()) : 0;
                })->max() ?? 0;

                $totalNilai = (float) $finishedTasks->sum(fn ($t) => $this->getTaskValue($t));
                $progress = $finishedTasks->map(fn ($t) => $t->comments ?: $t->submission_notes ?: $t->late_reason)->filter()->last() ?: '-';

                $doneCustomers[] = [
                    'name' => $customerName ?: 'Tanpa Nama Pelanggan',
                    'count' => $finishedTasks->count(),
                    'sales' => $salesStr,
                    'aging' => $maxAging,
                    'nilai' => $totalNilai,
                    'progress' => $progress,
                    'marker' => $this->getCustomerStatusMarker($finishedTasks),
                ];
            }
        }

        $unfinishedCount = (int) array_sum(array_column($onProcessCustomers, 'count'));
        $finishedCount = (int) array_sum(array_column($doneCustomers, 'count'));

        $fmtRp = fn (float $val) => 'Rp '.number_format($val, 0, ',', '.');

        $lines = [
            '📌 *3. BAA OPEN*',
            '- Total: '.number_format($unfinishedCount, 0, ',', '.').' Item (Selesai: '.number_format($finishedCount, 0, ',', '.').' Item)',
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
        $lines[] = '[✅] *SELESAI / DONE:*';

        if (empty($doneCustomers)) {
            $lines[] = '- Nihil';
        } else {
            $num = 1;
            foreach ($doneCustomers as $cust) {
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
        $doneCustomers = [];

        foreach ($grouped as $customerName => $custTasks) {
            $unfinishedTasks = $custTasks->filter(fn ($t) => ! $this->isTaskCompleted($t));
            $finishedTasks = $custTasks->filter(fn ($t) => $this->isTaskCompleted($t));

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

            if ($finishedTasks->isNotEmpty()) {
                $nearestDue = $finishedTasks->min('due_date');
                $dates = $finishedTasks->map(fn ($t) => $t->due_date ? $t->due_date->format('d/m/Y') : null)->filter()->unique()->values();
                $expStr = $dates->isNotEmpty() ? $dates->implode(' & ') : '-';

                $salesNames = $finishedTasks->map(fn ($t) => $t->sales_name ?: optional($t->user)->name)->filter()->unique()->values();
                $salesStr = $salesNames->isNotEmpty() ? $salesNames->implode(', ') : '-';
                $progress = $finishedTasks->map(fn ($t) => $t->comments ?: $t->submission_notes ?: $t->late_reason)->filter()->last() ?: '-';

                $doneCustomers[] = [
                    'name' => $customerName ?: 'Tanpa Nama Pelanggan',
                    'count' => $finishedTasks->count(),
                    'nearest_due' => $nearestDue,
                    'exp_date' => $expStr,
                    'sales' => $salesStr,
                    'progress' => $progress,
                    'marker' => $this->getCustomerStatusMarker($finishedTasks),
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
        usort($doneCustomers, $sortExp);

        $unfinishedCount = (int) array_sum(array_column($onProcessCustomers, 'count'));
        $finishedCount = (int) array_sum(array_column($doneCustomers, 'count'));
        $totalTasks = $tasks->count();

        $lines = [
            '📌 *4. CONTRACT EXPIRED*',
            "• Total: {$totalTasks} Pekerjaan ({$unfinishedCount} On Process | {$finishedCount} Selesai)",
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
        $lines[] = '[✅] *SELESAI / DONE:*';

        if (empty($doneCustomers)) {
            $lines[] = '- Nihil';
        } else {
            $num = 1;
            foreach ($doneCustomers as $cust) {
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
