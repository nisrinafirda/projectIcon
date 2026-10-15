<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Services\SbuSheetMapper;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillExceptionTasks extends Command
{
    protected $signature = 'tasks:backfill-exception
                            {file : Path ke Exception.csv}
                            {--status : Sinkronkan status juga (CLOSE menjadi Selesai)}
                            {--dry-run : Hanya hitung perubahan, tidak menyimpan}';

    protected $description = 'Isi kp dan due_date tugas Exception dari CSV tanpa import ulang';

    public function handle(SbuSheetMapper $mapper): int
    {
        $path = $this->argument('file');
        if (! is_readable($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        $header = array_map(
            fn ($h) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h)),
            fgetcsv($handle) ?: []
        );
        $idx = array_flip($header);

        $dateCol = 'TANGGAL KOMITMEN CLOSE (MM/DD/YYYY)';
        foreach (['SID / THBL', 'subBidangRevenue', $dateCol, 'STATUS'] as $need) {
            if (! isset($idx[$need])) {
                $this->error("Kolom '{$need}' tidak ada di CSV.");

                return self::FAILURE;
            }
        }

        $dry = (bool) $this->option('dry-run');
        $stats = ['Baris dibaca' => 0, 'Tidak ketemu di database' => 0, 'kp diisi' => 0, 'due_date diubah' => 0, 'status diubah' => 0];

        while (($row = fgetcsv($handle)) !== false) {
            $sid = trim($row[$idx['SID / THBL']] ?? '');
            if ($sid === '') {
                continue;
            }
            $stats['Baris dibaca']++;

            $task = Task::where('category', 'exception')->where('document_number', $sid)->first();
            if (! $task) {
                $stats['Tidak ketemu di database']++;

                continue;
            }

            $changes = [];

            $kp = $mapper->normalizeKp(trim($row[$idx['subBidangRevenue']] ?? ''));
            if ($kp && $task->kp !== $kp) {
                $changes['kp'] = $kp;
                $stats['kp diisi']++;
            }

            $due = $this->parseDate(trim($row[$idx[$dateCol]] ?? ''));
            if ($due && optional($task->due_date)->toDateString() !== $due) {
                $changes['due_date'] = $due;
                $stats['due_date diubah']++;
            }

            if ($this->option('status')
                && strtolower(trim($row[$idx['STATUS']] ?? '')) === 'close'
                && $task->status !== Task::STATUS_APPROVED) {
                $changes['status'] = Task::STATUS_APPROVED;
                $stats['status diubah']++;
            }

            if ($changes && ! $dry) {
                $task->update($changes);
            }
        }
        fclose($handle);

        $this->table(['Keterangan', 'Jumlah'], collect($stats)->map(fn ($v, $k) => [$k, $v])->values()->all());
        $this->info($dry ? 'DRY RUN: tidak ada data yang disimpan.' : 'Selesai.');

        return self::SUCCESS;
    }

    private function parseDate(string $value): ?string
    {
        if ($value === '' || $value === '-') {
            return null;
        }
        foreach (['d-M-y', 'n/j/Y'] as $format) {
            try {
                $d = Carbon::createFromFormat($format, $value);
                if ($d && $d->format($format) === $value) {
                    return $d->toDateString();
                }
            } catch (\Throwable) {
            }
        }

        return null;
    }
}
