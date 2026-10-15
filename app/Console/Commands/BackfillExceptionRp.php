<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\User;
use App\Services\SbuSheetMapper;
use Illuminate\Console\Command;

class BackfillExceptionRp extends Command
{
    protected $signature = 'tasks:backfill-exception-rp
                            {file=storage/app/imports/Exception.csv : Path ke Exception.csv}';

    protected $description = 'Isi kolom rp_exception dari Exception.csv tanpa membuat duplikat';

    public function handle(SbuSheetMapper $mapper): int
    {
        $path = $this->argument('file');
        if (! file_exists($path)) {
            $path = base_path($path);
        }

        if (! is_readable($path)) {
            $this->error("File tidak ditemukan atau tidak dapat dibaca: {$path}");

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        if (! $handle) {
            $this->error("Gagal membuka file: {$path}");

            return self::FAILURE;
        }

        $rawHeader = fgetcsv($handle) ?: [];
        $header = array_map(
            fn ($h) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h)),
            $rawHeader
        );
        $idx = array_flip($header);

        if (! isset($idx['SID / THBL'])) {
            $this->error("Kolom 'SID / THBL' tidak ditemukan di CSV.");
            fclose($handle);

            return self::FAILURE;
        }

        $rpKey = null;
        foreach ($header as $index => $colName) {
            $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $colName));
            if ($cleanName === 'rpexception') {
                $rpKey = $index;
                break;
            }
        }

        if ($rpKey === null) {
            $this->error('Header ditemukan: '.implode(', ', $header));
            $this->error("Kolom 'rpException' tidak ditemukan di CSV.");
            fclose($handle);

            return self::FAILURE;
        }

        $defaultUser = User::where('role', 'admin')->first() ?: User::first();
        $defaultUserId = $defaultUser ? $defaultUser->id : 1;

        $stats = [
            'baris_dibaca' => 0,
            'diperbarui' => 0,
            'dibuat_baru' => 0,
            'total_rp' => 0.0,
        ];

        while (($row = fgetcsv($handle)) !== false) {
            $sid = trim($row[$idx['SID / THBL']] ?? '');
            if ($sid === '') {
                continue;
            }

            $stats['baris_dibaca']++;
            $rawRp = trim($row[$rpKey] ?? '0');
            $rpVal = $mapper->parseRpException($rawRp);
            $stats['total_rp'] += $rpVal;

            $task = Task::where('category', Task::CATEGORY_EXCEPTION)
                ->where('document_number', $sid)
                ->first();

            if ($task) {
                $task->update(['rp_exception' => $rpVal]);
                $stats['diperbarui']++;
            } else {
                $customerName = trim($row[$idx['namaPelanggan']] ?? '');
                $serviceName = trim($row[$idx['namaLayananProduk']] ?? '');
                $kp = isset($idx['subBidangRevenue']) ? $mapper->normalizeKp(trim($row[$idx['subBidangRevenue']])) : null;
                $statusStr = isset($idx['STATUS']) ? trim($row[$idx['STATUS']]) : '';
                $status = strtolower($statusStr) === 'close' ? Task::STATUS_APPROVED : Task::STATUS_IN_PROGRESS;

                Task::create([
                    'user_id' => $defaultUserId,
                    'assigned_by' => $defaultUserId,
                    'category' => Task::CATEGORY_EXCEPTION,
                    'document_number' => $sid,
                    'title' => 'EXCEPTION - '.($customerName ?: 'Customer').' ('.($serviceName ?: 'Layanan').')',
                    'customer_name' => $customerName ?: null,
                    'service_type' => $serviceName ?: null,
                    'kp' => $kp,
                    'kategori_segmen' => Task::SEGMEN_PUBLIK,
                    'priority' => 'medium',
                    'status' => $status,
                    'rp_exception' => $rpVal,
                ]);
                $stats['dibuat_baru']++;
            }
        }

        fclose($handle);

        $this->info('Backfill rp_exception selesai.');
        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Baris dibaca', $stats['baris_dibaca']],
                ['Data diperbarui', $stats['diperbarui']],
                ['Data baru dibuat', $stats['dibuat_baru']],
                ['Total Rp Exception', 'Rp '.number_format($stats['total_rp'], 0, ',', '.')],
            ]
        );

        return self::SUCCESS;
    }
}
