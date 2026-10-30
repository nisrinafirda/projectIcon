<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class SyncKontrakIdPelangganCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:sync-kontrak-id-pelanggan
                            {--dry-run : Jalankan simulasi tanpa menyimpan perubahan ke database}
                            {--exception-csv=storage/app/imports/Exception.csv : Path ke file Exception.csv untuk sinkronisasi awal}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Isi id_pelanggan task Kontrak Exp dengan mencocokkan document_number (idPelangganProduk) ke data kategori lain di tabel tasks yang sudah punya id_pelanggan terisi.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->warn('[DRY RUN] Mode simulasi aktif. Tidak ada perubahan yang disimpan ke database.');
        }

        // 1. Pastikan kategori Exception memiliki id_pelanggan jika file Exception.csv tersedia
        $exceptionCsv = (string) $this->option('exception-csv');
        $this->syncExceptionIdPelangganIfAvailable($exceptionCsv, $dryRun);

        // 2. Kumpulkan lookup map dari seluruh task kategori lain yang sudah punya id_pelanggan
        $otherTasks = Task::query()
            ->where('category', '!=', Task::CATEGORY_KONTRAK_EXP)
            ->whereNotNull('id_pelanggan')
            ->where('id_pelanggan', '!=', '')
            ->get(['id', 'category', 'document_number', 'id_pelanggan']);

        $this->info("Menemukan {$otherTasks->count()} task kategori lain yang sudah memiliki id_pelanggan.");

        $lookupMap = [];
        foreach ($otherTasks as $other) {
            $doc = trim((string) $other->document_number);
            if ($doc === '') {
                continue;
            }

            // Exact document_number
            $lookupMap[$doc] = $other->id_pelanggan;

            // Prefix sebelum ' / ' (e.g. '141202002634 / 2026-03' -> '141202002634')
            if (str_contains($doc, '/')) {
                $parts = explode('/', $doc);
                $prefix = trim($parts[0] ?? '');
                if ($prefix !== '') {
                    $lookupMap[$prefix] = $other->id_pelanggan;
                }
            }
        }

        // 3. Cocokkan ke task Kontrak Exp
        $kontrakTasks = Task::query()
            ->where('category', Task::CATEGORY_KONTRAK_EXP)
            ->get();

        $totalKontrak = $kontrakTasks->count();
        $this->info("Memproses {$totalKontrak} task Kontrak Exp...");

        $filledCount = 0;
        $unmatched = [];

        foreach ($kontrakTasks as $task) {
            $doc = trim((string) $task->document_number);
            $matchedIdPel = null;

            if ($doc !== '') {
                if (isset($lookupMap[$doc])) {
                    $matchedIdPel = $lookupMap[$doc];
                } elseif (str_contains($doc, '/')) {
                    foreach (explode('/', $doc) as $part) {
                        $cleanPart = trim($part);
                        if ($cleanPart !== '' && isset($lookupMap[$cleanPart])) {
                            $matchedIdPel = $lookupMap[$cleanPart];
                            break;
                        }
                    }
                }
            }

            if ($matchedIdPel !== null) {
                if (! $dryRun) {
                    // Update HANYA id_pelanggan, jangan sentuh status, komentar, konfirmasi, dll.
                    $task->update(['id_pelanggan' => $matchedIdPel]);
                }
                $filledCount++;
            } else {
                $unmatched[] = [
                    'id' => $task->id,
                    'document_number' => $task->document_number ?: '-',
                    'customer_name' => $task->customer_name ?: '-',
                    'service_type' => $task->service_type ?: ($task->title ?: '-'),
                ];
            }
        }

        $unmatchedCount = count($unmatched);

        // 4. Tampilkan Ringkasan Hasil
        $this->newLine();
        $this->info('=== HASIL SINKRONISASI ID PELANGGAN KONTRAK EXP ===');
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Total Task Kontrak Exp', $totalKontrak],
                ['Baris Terisi', $filledCount],
                ['Tidak Ketemu', $unmatchedCount],
            ]
        );

        // Tampilkan 5 contoh yang tidak ketemu
        if ($unmatchedCount > 0) {
            $sample5 = array_slice($unmatched, 0, 5);
            $this->newLine();
            $this->warn('5 Contoh Task Kontrak Exp yang Tidak Ketemu di Kategori Lain:');
            $this->table(
                ['ID Task', 'ID PA / Document Number', 'Nama Pelanggan', 'Jenis Layanan'],
                array_map(fn ($item) => [
                    $item['id'],
                    $item['document_number'],
                    $item['customer_name'],
                    $item['service_type'],
                ], $sample5)
            );
        }

        return self::SUCCESS;
    }

    /**
     * Sinkronkan id_pelanggan kategori Exception dari file CSV jika tersedia dan belum terisi.
     */
    private function syncExceptionIdPelangganIfAvailable(string $path, bool $dryRun): void
    {
        if (! file_exists($path) || ! is_readable($path)) {
            return;
        }

        $emptyExceptionCount = Task::query()
            ->where('category', Task::CATEGORY_EXCEPTION)
            ->whereNull('id_pelanggan')
            ->count();

        if ($emptyExceptionCount === 0) {
            return;
        }

        $handle = fopen($path, 'r');
        if (! $handle) {
            return;
        }

        $header = fgetcsv($handle);
        if (! is_array($header)) {
            fclose($handle);

            return;
        }

        $idxIdPel = array_search('idPelanggan', $header, true);
        $idxSid = array_search('SID / THBL', $header, true);

        if ($idxIdPel === false || $idxSid === false) {
            fclose($handle);

            return;
        }

        $map = [];
        while (($row = fgetcsv($handle)) !== false) {
            $pel = trim((string) ($row[$idxIdPel] ?? ''));
            $sid = trim((string) ($row[$idxSid] ?? ''));
            if ($pel !== '' && $sid !== '') {
                $map[$sid] = $pel;
            }
        }
        fclose($handle);

        if (! empty($map) && ! $dryRun) {
            $updated = 0;
            foreach ($map as $sid => $pel) {
                $affected = Task::query()
                    ->where('category', Task::CATEGORY_EXCEPTION)
                    ->where('document_number', $sid)
                    ->whereNull('id_pelanggan')
                    ->update(['id_pelanggan' => $pel]);
                $updated += $affected;
            }
            if ($updated > 0) {
                $this->info("Menyinkronkan {$updated} task Exception dengan id_pelanggan dari {$path}.");
            }
        }
    }
}
