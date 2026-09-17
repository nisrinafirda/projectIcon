<?php

namespace App\Services;

use App\Models\Task;
use Carbon\Carbon;

/**
 * Maps columns from SBU "Project Monitoring PPB" Excel sheets
 * to ICON task fields via header-based auto-detection.
 *
 * @phpstan-type ColumnMap array<string, int|null>
 * @phpstan-type TaskData array<string, mixed>
 */
class SbuSheetMapper
{
    /**
     * Known SBU header keywords mapped to semantic field names.
     * Order matters: first match wins for ambiguous headers.
     *
     * @var array<string, list<string>>
     */
    private const HEADER_PATTERNS = [
        'document_number' => ['id pa', 'idpa', 'id pelanggan', 'sid'],
        'row_number' => ['no'],
        'customer_name' => ['nama pelanggan', 'namapelanggan'],
        'service_type' => ['layanan produk', 'layananproduk', 'layanan'],
        'harga_lama' => ['harga lama', 'hargalama'],
        'harga_baru' => ['harga baru', 'hargabaru', 'harga bar'],
        'selisih' => ['selisih'],
        'kategori_segmen' => ['kategori customer', 'kategori'],
        'kp' => ['kp'],
        'sub_bidang' => ['sub bidang', 'subbidang', 'subbidangrevenue', 'bidangrevenue'],
        'sales' => ['sales'],
        'status' => ['status'],
        'kendala' => ['kendala'],
        'konfirmasi' => ['konfirmasi'],
        'alasan' => ['alasan'],
        'keterangan' => ['keterangan'],
        'support' => ['support'],
        'nomor_kontrak' => ['nomor kontrak'],
        'target_days' => ['target bai', 'target'],
        'aging_days' => ['aging'],
        'tanggal_aging' => ['tanggal aging'],
        'tanggal_upload' => ['tanggal upload', 'tanggalupload'],
        'tgl_target_aktivasi' => ['tgl target aktivasi', 'target aktivasi'],
        'diterbitkan_tanggal' => ['diterbitkan tanggal', 'diterbitkan'],
        'tanggal_kontrak_berakhir' => ['tanggal kontrak berakhir', 'kontrak berakhir'],
        'tanggal_kontrak_lama' => ['tanggal kontrak lama', 'kontrak lama'],
        'sum_rf_layanan' => ['sum of rf layanan', 'rf layanan'],
        'sum_rf_install' => ['sum of rf install', 'rf install'],
    ];

    /**
     * SBU format indicator headers — if any of these are found, it's SBU format.
     *
     * @var list<string>
     */
    private const SBU_INDICATORS = [
        'id pa', 'idpa', 'id pelanggan', 'sid',
        'nama pelanggan', 'namapelanggan',
        'harga lama', 'hargalama',
        'tanggal aging', 'target bai',
        'tanggal upload', 'tanggalupload',
        'tgl target aktivasi',
    ];

    /**
     * Detect whether a header row belongs to SBU/PPB format or ICON template.
     *
     * @param  list<mixed>  $headerRow
     */
    public function detectFormat(array $headerRow): string
    {
        $normalized = array_map(fn ($h) => $this->normalizeHeader((string) ($h ?? '')), $headerRow);

        foreach ($normalized as $header) {
            if ($header === '') {
                continue;
            }

            foreach (self::SBU_INDICATORS as $indicator) {
                if (str_contains($header, $indicator)) {
                    return 'sbu';
                }
            }
        }

        return 'template';
    }

    /**
     * Build a column map from the header row: semantic name => column index.
     *
     * @param  list<mixed>  $headerRow
     * @return ColumnMap
     */
    public function buildColumnMap(array $headerRow): array
    {
        /** @var ColumnMap $map */
        $map = array_fill_keys(array_keys(self::HEADER_PATTERNS), null);

        $normalized = array_map(fn ($h) => $this->normalizeHeader((string) ($h ?? '')), $headerRow);
        $assigned = [];

        foreach (self::HEADER_PATTERNS as $field => $patterns) {
            foreach ($normalized as $colIndex => $header) {
                if ($header === '' || isset($assigned[$colIndex])) {
                    continue;
                }

                foreach ($patterns as $pattern) {
                    if (str_contains($header, $pattern)) {
                        // Avoid "aging" matching "tanggal aging" when tanggal_aging is a separate field
                        if ($field === 'aging_days' && str_contains($header, 'tanggal')) {
                            continue;
                        }

                        $map[$field] = $colIndex;
                        $assigned[$colIndex] = $field;
                        break 2;
                    }
                }
            }
        }

        // If 'document_number' is null but 'row_number' is set and has an adjacent ID-like column, skip
        // Exception sheet: col A = NO (row number), col B = ID Pelanggan
        // This case is already handled since 'id pelanggan' matches 'document_number'

        return $map;
    }

    /**
     * Map a single SBU data row to a task-ready associative array.
     *
     * @param  list<mixed>  $row
     * @param  ColumnMap  $columnMap
     * @return TaskData|null Returns null if row is empty/skippable
     */
    public function mapRow(array $row, array $columnMap, string $category): ?array
    {
        $get = fn (string $field): string => trim((string) ($row[$columnMap[$field] ?? -1] ?? ''));

        $docNumber = $get('document_number');
        $customerName = $get('customer_name');
        $serviceType = $get('service_type');
        $sales = $get('sales');

        // Skip completely empty rows
        if ($docNumber === '' && $customerName === '' && $serviceType === '') {
            return null;
        }

        // If no document_number, generate from customer + index
        if ($docNumber === '') {
            return null;
        }

        // Build title
        $categoryLabel = Task::categories()[$category] ?? strtoupper($category);
        $title = $categoryLabel;
        if ($customerName !== '') {
            $title .= ' - '.$customerName;
        }
        if ($serviceType !== '') {
            $title .= ' ('.$serviceType.')';
        }
        // Truncate title if too long
        if (mb_strlen($title) > 200) {
            $title = mb_substr($title, 0, 197).'...';
        }

        // Build description from price data, kendala, alasan, konfirmasi, keterangan, support
        $description = $this->buildDescription($row, $columnMap);

        // Normalize KP
        $kp = $this->normalizeKp($get('kp'));

        // Normalize kategori segmen
        $kategoriSegmen = $this->normalizeSegmen($get('kategori_segmen'));

        // Normalize status
        $status = $this->normalizeStatus($get('status'));

        // Parse dates
        $dates = $this->parseDates($row, $columnMap);

        return [
            'document_number' => $docNumber,
            'category' => $category,
            'title' => $title,
            'description' => $description ?: null,
            'customer_name' => $customerName ?: null,
            'service_type' => $serviceType ?: null,
            'kp' => $kp,
            'kategori_segmen' => $kategoriSegmen,
            'priority' => 'medium',
            'status' => $status,
            'start_date' => $dates['start_date'],
            'due_date' => $dates['due_date'],
            'sales_identifier' => $sales, // for user matching in controller
        ];
    }

    /**
     * Normalize KP field value to ICON constant.
     */
    public function normalizeKp(string $value): ?string
    {
        $lower = strtolower(trim($value));

        if ($lower === '') {
            return null;
        }

        $kpMap = [
            'surabaya' => Task::KP_SURABAYA,
            'malang' => Task::KP_MALANG,
            'madiun' => Task::KP_MADIUN,
            'jember' => Task::KP_JEMBER,
        ];

        foreach ($kpMap as $keyword => $constant) {
            if (str_contains($lower, $keyword)) {
                return $constant;
            }
        }

        return null;
    }

    /**
     * Normalize kategori segmen (PUBLIK/PLN).
     */
    public function normalizeSegmen(string $value): string
    {
        $lower = strtolower(trim($value));

        if (str_contains($lower, 'pln')) {
            return Task::SEGMEN_PLN;
        }

        return Task::SEGMEN_PUBLIK;
    }

    /**
     * Normalize SBU status to ICON task status.
     */
    public function normalizeStatus(string $value): string
    {
        $lower = strtolower(trim($value));

        if ($lower === '') {
            return Task::STATUS_PENDING;
        }

        return match (true) {
            str_contains($lower, 'done') => Task::STATUS_APPROVED,
            str_contains($lower, 'on process'), str_contains($lower, 'on proses') => Task::STATUS_IN_PROGRESS,
            str_contains($lower, 'perpanjang') => Task::STATUS_IN_PROGRESS,
            str_contains($lower, 'deaktivasi') => Task::STATUS_APPROVED,
            str_contains($lower, 'pelanggan') => Task::STATUS_IN_PROGRESS,
            str_contains($lower, 'proses') => Task::STATUS_IN_PROGRESS,
            default => Task::STATUS_PENDING,
        };
    }

    /**
     * Build a combined description from price data, kendala, alasan, etc.
     *
     * @param  list<mixed>  $row
     * @param  ColumnMap  $columnMap
     */
    private function buildDescription(array $row, array $columnMap): string
    {
        $get = fn (string $field): string => trim((string) ($row[$columnMap[$field] ?? -1] ?? ''));

        $parts = [];

        // Price info
        $hargaLama = $get('harga_lama');
        $hargaBaru = $get('harga_baru');
        $selisih = $get('selisih');

        if ($hargaLama !== '' || $hargaBaru !== '' || $selisih !== '') {
            $priceInfo = [];
            if ($hargaLama !== '' && $hargaLama !== '0') {
                $priceInfo[] = 'Harga Lama: Rp '.number_format((float) str_replace([',', '.'], ['', '.'], $hargaLama), 0, ',', '.');
            }
            if ($hargaBaru !== '' && $hargaBaru !== '0') {
                $priceInfo[] = 'Harga Baru: Rp '.number_format((float) str_replace([',', '.'], ['', '.'], $hargaBaru), 0, ',', '.');
            }
            if ($selisih !== '' && $selisih !== '0') {
                $priceInfo[] = 'Selisih: Rp '.number_format((float) str_replace([',', '.'], ['', '.'], $selisih), 0, ',', '.');
            }
            if (! empty($priceInfo)) {
                $parts[] = implode(' | ', $priceInfo);
            }
        }

        // RF data (Exception sheet)
        $rfLayanan = $get('sum_rf_layanan');
        $rfInstall = $get('sum_rf_install');
        if ($rfLayanan !== '' && $rfLayanan !== '0') {
            $parts[] = 'RF Layanan: Rp '.number_format((float) str_replace([',', '.'], ['', '.'], $rfLayanan), 0, ',', '.');
        }
        if ($rfInstall !== '' && $rfInstall !== '0') {
            $parts[] = 'RF Install: Rp '.number_format((float) str_replace([',', '.'], ['', '.'], $rfInstall), 0, ',', '.');
        }

        // Nomor Kontrak
        $nomorKontrak = $get('nomor_kontrak');
        if ($nomorKontrak !== '') {
            $parts[] = 'No. Kontrak: '.$nomorKontrak;
        }

        // Konfirmasi
        $konfirmasi = $get('konfirmasi');
        if ($konfirmasi !== '' && $konfirmasi !== '-') {
            $parts[] = 'Konfirmasi: '.$konfirmasi;
        }

        // Kendala
        $kendala = $get('kendala');
        if ($kendala !== '' && $kendala !== '-') {
            $parts[] = 'Kendala: '.$kendala;
        }

        // Alasan
        $alasan = $get('alasan');
        if ($alasan !== '' && $alasan !== '-') {
            $parts[] = 'Alasan: '.$alasan;
        }

        // Keterangan
        $keterangan = $get('keterangan');
        if ($keterangan !== '' && $keterangan !== '-') {
            $parts[] = 'Keterangan: '.$keterangan;
        }

        // Support
        $support = $get('support');
        if ($support !== '' && $support !== '-') {
            $parts[] = 'Support: '.$support;
        }

        return implode("\n", $parts);
    }

    /**
     * Parse start_date and due_date from various SBU date columns.
     *
     * @param  list<mixed>  $row
     * @param  ColumnMap  $columnMap
     * @return array{start_date: ?string, due_date: ?string}
     */
    private function parseDates(array $row, array $columnMap): array
    {
        $get = fn (string $field): string => trim((string) ($row[$columnMap[$field] ?? -1] ?? ''));

        $startDate = null;
        $dueDate = null;

        // Try to find start_date from various columns (priority order)
        $startCandidates = ['tanggal_aging', 'tanggal_upload', 'diterbitkan_tanggal', 'tgl_target_aktivasi'];
        foreach ($startCandidates as $field) {
            $val = $get($field);
            if ($val !== '' && $val !== '-' && $val !== '--/--/--' && $val !== '/ /') {
                $parsed = $this->tryParseDate($val);
                if ($parsed) {
                    $startDate = $parsed;
                    break;
                }
            }
        }

        // Try to find due_date from various columns (priority order)
        $dueCandidates = ['tgl_target_aktivasi', 'tanggal_kontrak_berakhir'];
        foreach ($dueCandidates as $field) {
            // Don't reuse the same column already used for start_date
            if ($field === 'tgl_target_aktivasi' && $startDate !== null && $columnMap['tanggal_aging'] === null && $columnMap['tanggal_upload'] === null && $columnMap['diterbitkan_tanggal'] === null) {
                continue; // This was already used as start_date
            }

            $val = $get($field);
            if ($val !== '' && $val !== '-' && $val !== '--/--/--' && $val !== '/ /') {
                $parsed = $this->tryParseDate($val);
                if ($parsed) {
                    $dueDate = $parsed;
                    break;
                }
            }
        }

        // If we have a target_days value and a start_date, compute due_date
        if ($dueDate === null && $startDate !== null) {
            $targetDays = $get('target_days');
            if ($targetDays !== '' && is_numeric($targetDays)) {
                try {
                    $dueDate = Carbon::parse($startDate)->addDays((int) ceil((float) $targetDays))->toDateString();
                } catch (\Exception) {
                    // ignore
                }
            }
        }

        // Fallback: if still no due_date, use tanggal_upload as reference
        if ($dueDate === null) {
            $uploadVal = $get('tanggal_upload');
            if ($uploadVal !== '' && $uploadVal !== '-' && $uploadVal !== '--/--/--') {
                $parsed = $this->tryParseDate($uploadVal);
                if ($parsed && $startDate !== $parsed) {
                    $dueDate = $parsed;
                }
            }
        }

        // Last resort: default due_date to start + 7 days or today + 7 days
        if ($dueDate === null) {
            if ($startDate !== null) {
                try {
                    $dueDate = Carbon::parse($startDate)->addDays(7)->toDateString();
                } catch (\Exception) {
                    $dueDate = Carbon::today()->addDays(7)->toDateString();
                }
            } else {
                $dueDate = Carbon::today()->addDays(7)->toDateString();
            }
        }

        return [
            'start_date' => $startDate,
            'due_date' => $dueDate,
        ];
    }

    /**
     * Try to parse a date string from various SBU formats.
     */
    private function tryParseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || $value === '-' || $value === '--/--/--' || $value === '/ /') {
            return null;
        }

        // Remove time portion if present (e.g., "29/06/2026 10.39")
        $value = preg_replace('/\s+\d{1,2}[.:]\d{2}(:\d{2})?$/', '', $value);

        try {
            // Try Carbon::parse which handles many formats
            $date = Carbon::parse($value);

            // Sanity check: year should be reasonable (2020-2030)
            if ($date->year < 2020 || $date->year > 2035) {
                return null;
            }

            return $date->toDateString();
        } catch (\Exception) {
            // Try manual parsing for dd/mm/yyyy format
            if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{2,4})$#', $value, $matches)) {
                $day = (int) $matches[1];
                $month = (int) $matches[2];
                $year = (int) $matches[3];

                if ($year < 100) {
                    $year += 2000;
                }

                try {
                    return Carbon::createFromDate($year, $month, $day)->toDateString();
                } catch (\Exception) {
                    return null;
                }
            }

            return null;
        }
    }

    /**
     * Normalize a header string for comparison.
     */
    private function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));
        // Remove special characters but keep spaces
        $header = preg_replace('/[^a-z0-9\s]/', '', $header);
        // Collapse multiple spaces
        $header = preg_replace('/\s+/', ' ', $header);

        return trim($header);
    }
}
