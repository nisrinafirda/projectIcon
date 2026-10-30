<?php

namespace App\Services;

use App\Models\Task;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

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
     * Recognized comment / kendala column header patterns per category.
     * Normalized patterns matched against normalized CSV headers.
     * Defined in ONE place as required.
     *
     * @var array<string, list<string>>
     */
    public const COMMENT_COLUMNS_BY_CATEGORY = [
        Task::CATEGORY_SSO_OPEN => [
            'progress pekerjaan',
            'progress/kendala',
            'progress/kendala/support',
        ],
        Task::CATEGORY_BAA => [
            'kendala',
            'kendala/support',
            'progress/kendala',
            'progress/kendala/support',
        ],
        Task::CATEGORY_EXCEPTION => [
            'progress/kendala/support',
            'progress/kendala',
            'kendala/support',
            'kendala',
        ],
        Task::CATEGORY_KONTRAK_EXP => [
            'progress/kendala/support',
            'progress/kendala',
            'kendala/support',
            'kendala',
        ],
    ];

    /**
     * Explicitly forbidden columns that must NEVER be used as task comments.
     *
     * @var list<string>
     */
    public const FORBIDDEN_COMMENT_HEADERS = [
        'status',
        'cek',
        'sales',
        'adev',
        'keterangan',
        'kendala pdl',
        'suport dari ppb',
        'support dari ppb',
        'harga',
        'tanggal',
        'komitmen',
        'bulan',
        'week',
    ];

    /**
     * Get list of categories that support automatic comment import.
     *
     * @return list<string>
     */
    public static function allowedCommentCategories(): array
    {
        return array_keys(self::COMMENT_COLUMNS_BY_CATEGORY);
    }

    /**
     * Known SBU header keywords mapped to semantic field names.
     * Order matters: first match wins for ambiguous headers.
     *
     * @var array<string, list<string>>
     */
    private const HEADER_PATTERNS = [
        'id_pelanggan' => ['id pelanggan', 'idpelanggan', 'id customer', 'idcustomer', 'id cust', 'idcust', 'no pelanggan', 'nopelanggan', 'customer id', 'cust id'],
        'document_number' => ['sid / thbl', 'sidthbl', 'id pa', 'idpa', 'sid'],
        'tanggal_komitmen' => ['tanggal komitmen close', 'komitmen close'],
        'row_number' => ['no'],
        'customer_name' => ['nama pelanggan', 'namapelanggan'],
        'service_type' => ['layanan produk', 'layananproduk', 'layanan'],
        'harga_lama' => ['harga lama', 'hargalama'],
        'harga_baru' => ['harga baru', 'hargabaru', 'harga bar'],
        'selisih' => ['selisih'],
        'kategori_segmen' => ['kategori customer', 'kategori'],
        'kp' => ['kp'],
        'sub_bidang' => ['sub bidang', 'subbidang', 'subbidangrevenue'],
        'sales' => ['sales'],
        'so_comment' => ['progress pekerjaan', 'progress / kendala', 'progress/kendala', 'progress kendala'],
        'status' => ['status pekerjaan', 'status'],
        'kendala' => ['kendala pdl', 'kendala'],
        'confirm_to' => ['confirm to'],
        'konfirmasi' => ['confirm to ppb', 'konfirmasi'],
        'alasan' => ['alasan'],
        'keterangan' => ['keterangan'],
        'support' => ['suport dari ppb', 'support dari ppb', 'support'],
        'nomor_kontrak' => ['nomor kontrak'],
        'target_days' => ['target bai', 'target'],
        'aging_days' => ['aging'],
        'tanggal_aging' => ['tanggal aging'],
        'tanggal_upload' => ['tanggal upload', 'tanggalupload'],
        'tgl_target_aktivasi' => ['tgl target aktivasi', 'target aktivasi', 'tanggal eksekusi'],
        'diterbitkan_tanggal' => ['diterbitkan tanggal', 'diterbitkan', 'tanggal pa terbit', 'pa terbit'],
        'tanggal_kontrak_berakhir' => ['tanggal kontrak berakhir', 'kontrak berakhir'],
        'tanggal_kontrak_lama' => ['tanggal kontrak lama', 'kontrak lama'],
        'sum_rf_layanan' => ['sum of rf layanan', 'rf layanan'],
        'sum_rf_install' => ['sum of rf install', 'rf install'],
        'rp_exception' => ['rpexception', 'rp exception'],
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
     * Detect the specific task category from SBU sheet headers along with matching markers.
     *
     * @param  list<mixed>  $headerRow
     * @return array{category: ?string, markers: list<string>}
     */
    public function detectCategoryWithMarkers(array $headerRow): array
    {
        $normalizedMap = [];
        foreach ($headerRow as $rawHeader) {
            $rawStr = trim((string) ($rawHeader ?? ''));
            if ($rawStr !== '') {
                $normalizedMap[$rawStr] = $this->normalizeHeader($rawStr);
            }
        }

        $findMatchingHeaders = function (string ...$keywords) use ($normalizedMap): array {
            $matched = [];
            foreach ($normalizedMap as $orig => $norm) {
                foreach ($keywords as $kw) {
                    if (str_contains($norm, $kw)) {
                        $matched[] = $orig;
                        break;
                    }
                }
            }

            return $matched;
        };

        // 1. Exception indicators
        $excMatches = $findMatchingHeaders('rpexception', 'rp exception', 'alasanexception', 'alasan exception', 'petugasexception', 'tanggalexception');
        if (! empty($excMatches)) {
            return ['category' => Task::CATEGORY_EXCEPTION, 'markers' => $excMatches];
        }

        // 2. SSO Open indicators (PDL & PPB)
        $ssoMatches = $findMatchingHeaders('so open', 'sso open', 'sso', 'kendala pdl', 'confirm to ppb', 'suport dari ppb', 'setting sla', 'setting kontrak');
        if (! empty($ssoMatches)) {
            return ['category' => Task::CATEGORY_SSO_OPEN, 'markers' => $ssoMatches];
        }

        // 3. Contract Expired indicators
        $kontrakMatches = $findMatchingHeaders('nomor kontrak baru', 'nomor kontrak lama', 'kontrak berakhir', 'kontrak lama', 'thbl tagihan', 'tipe revenue', 'nomor kontrak');
        if (! empty($kontrakMatches)) {
            return ['category' => Task::CATEGORY_KONTRAK_EXP, 'markers' => $kontrakMatches];
        }

        // 4. BAA vs BAI distinction
        $baaMatches = $findMatchingHeaders('upload baa', 'aging baa', 'baa');
        $uploadBaiMatches = $findMatchingHeaders('upload bai', 'tanggal upload bai');
        $targetBaiMatches = $findMatchingHeaders('target bai');

        // Explicit BAA headers (e.g. Aging BAA, Tanggal Upload BAA)
        if (! empty($baaMatches)) {
            return ['category' => Task::CATEGORY_BAA, 'markers' => array_values(array_unique(array_merge($baaMatches, $uploadBaiMatches)))];
        }

        // BAI header without BAA markers (Target BAI)
        if (! empty($targetBaiMatches)) {
            return ['category' => Task::CATEGORY_BAI, 'markers' => $targetBaiMatches];
        }

        // Upload BAI without Target BAI is typically BAA tracking
        if (! empty($uploadBaiMatches)) {
            return ['category' => Task::CATEGORY_BAA, 'markers' => $uploadBaiMatches];
        }

        return ['category' => null, 'markers' => []];
    }

    /**
     * Detect the specific task category from SBU sheet headers.
     *
     * @param  list<mixed>  $headerRow
     */
    public function detectCategory(array $headerRow): ?string
    {
        return $this->detectCategoryWithMarkers($headerRow)['category'];
    }

    /**
     * Build a column map from the header row: semantic name => column index.
     *
     * @param  list<mixed>  $headerRow
     * @return ColumnMap
     */
    public function buildColumnMap(array $headerRow, ?string $category = null): array
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
                        // Avoid "id_pelanggan" matching "id pelanggan produk" or "layanan"
                        if ($field === 'id_pelanggan' && (str_contains($header, 'produk') || str_contains($header, 'layanan'))) {
                            continue;
                        }

                        // Avoid "aging" matching "tanggal aging" when tanggal_aging is a separate field
                        if ($field === 'aging_days' && str_contains($header, 'tanggal')) {
                            continue;
                        }

                        // Avoid "confirm_to" matching "confirm to ppb"
                        if ($field === 'confirm_to' && str_contains($header, 'ppb')) {
                            continue;
                        }

                        $map[$field] = $colIndex;
                        $assigned[$colIndex] = $field;
                        break 2;
                    }
                }
            }
        }

        // Fallback for document_number if no ID PA / SID column exists (e.g. sheets using 'ID Pelanggan' as doc number)
        if ($map['document_number'] === null) {
            if ($map['id_pelanggan'] !== null) {
                $map['document_number'] = $map['id_pelanggan'];
            } else {
                foreach ($normalized as $colIndex => $header) {
                    if ($header === '') {
                        continue;
                    }
                    foreach (['id pelanggan', 'idpelanggan', 'id customer', 'idcustomer'] as $pattern) {
                        if (str_contains($header, $pattern)) {
                            $map['document_number'] = $colIndex;
                            break 2;
                        }
                    }
                }
            }
        }

        // Dedicated category-aware comment column mapping
        $commentCol = $this->resolveCommentColumnIndex($headerRow, $category);
        $map['import_comment'] = $commentCol;
        $map['so_comment'] = $commentCol;

        return $map;
    }

    /**
     * Tolerant normalization of comment column headers:
     * - Trims non-breaking spaces (U+00A0), zero-width characters, BOM
     * - Converts to lowercase
     * - Replaces newlines/carriage returns and tabs with spaces
     * - Normalizes spaces around slashes (" / " -> "/")
     * - Normalizes spelling variations ("progres" -> "progress", "suport" -> "support")
     * - Removes punctuation other than '/'
     * - Collapses multiple spaces
     */
    public function normalizeCommentHeader(string $rawHeader): string
    {
        $clean = preg_replace('/[\x{00A0}\x{200B}-\x{200D}\x{FEFF}]/u', ' ', $rawHeader) ?? $rawHeader;
        $clean = mb_strtolower(trim($clean));
        $clean = preg_replace('/[\r\n\t]+/', ' ', $clean) ?? $clean;
        $clean = preg_replace('/\s*\/\s*/', '/', $clean) ?? $clean;
        $clean = preg_replace('/\bprogres\b/u', 'progress', $clean) ?? $clean;
        $clean = preg_replace('/\bsuport\b/u', 'support', $clean) ?? $clean;
        $clean = preg_replace('/[^\p{L}\p{N}\s\/]/u', '', $clean) ?? $clean;
        $clean = preg_replace('/\s+/', ' ', $clean) ?? $clean;

        return trim($clean);
    }

    /**
     * Resolve comment/kendala column index based on tolerant category-specific rules.
     *
     * @param  list<mixed>  $headerRow
     */
    public function resolveCommentColumnIndex(array $headerRow, ?string $category = null): ?int
    {
        if ($category === Task::CATEGORY_BAI) {
            return null; // BAI is strictly skipped
        }

        $candidates = $category !== null
            ? (self::COMMENT_COLUMNS_BY_CATEGORY[$category] ?? [])
            : array_unique(array_merge(...array_values(self::COMMENT_COLUMNS_BY_CATEGORY)));

        foreach ($headerRow as $idx => $rawHeader) {
            $norm = $this->normalizeCommentHeader((string) $rawHeader);
            if ($norm === '') {
                continue;
            }

            // Reject explicitly forbidden headers (status, CEK, SALES, ADEV, KETERANGAN, Kendala PDL, Suport dari PPB, harga, tanggal, etc.)
            foreach (self::FORBIDDEN_COMMENT_HEADERS as $forbidden) {
                if ($norm === $forbidden || str_starts_with($norm, $forbidden.' ') || str_starts_with($norm, $forbidden.'/')) {
                    continue 2;
                }
            }

            // Reject standalone 'support' (e.g. in BAA header "Support")
            if ($norm === 'support') {
                continue;
            }

            // 1. Direct match with candidates
            if (in_array($norm, $candidates, true)) {
                return (int) $idx;
            }

            // 2. Tolerant match if trailing "/support" or "support" suffix is present
            $strippedSupport = preg_replace('/\/?\s*support$/', '', $norm);
            $strippedSupport = trim(rtrim((string) $strippedSupport, '/'));
            if ($strippedSupport !== '' && in_array($strippedSupport, $candidates, true)) {
                return (int) $idx;
            }
        }

        return null;
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

        $rawDoc = $row[$columnMap['document_number'] ?? -1] ?? '';
        $docNumber = $this->formatDocumentNumber($rawDoc);
        $customerName = $get('customer_name');
        $serviceType = $get('service_type');
        $sales = $get('sales');

        $rawIdPelanggan = $row[$columnMap['id_pelanggan'] ?? -1] ?? '';
        $idPelanggan = $this->formatDocumentNumber($rawIdPelanggan);
        if ($idPelanggan === '' || $idPelanggan === '-') {
            $idPelanggan = null;
        }

        // Skip completely empty rows
        if ($docNumber === '' && $customerName === '' && $serviceType === '') {
            return null;
        }

        // If no document_number or ID PA is empty/invalid, skip row
        if ($docNumber === '' || ! preg_match('/[a-zA-Z0-9]/', $docNumber)) {
            return null;
        }

        // Confirm to (PDL or Sales) for SO Open
        $confirmTo = null;
        if ($category === Task::CATEGORY_SSO_OPEN) {
            $rawConfirmTo = $get('confirm_to');
            if ($rawConfirmTo !== '' && $rawConfirmTo !== '-') {
                $confirmTo = $rawConfirmTo;
            }
        }

        // Detect SO subtype (PDL vs PPB) for SO Open
        $soType = null;
        if ($category === Task::CATEGORY_SSO_OPEN) {
            $docUpper = strtoupper($docNumber);
            $konfirmasiVal = strtolower($get('konfirmasi'));
            if (str_starts_with($docUpper, 'B') || str_contains($konfirmasiVal, 'ppb') || str_contains($konfirmasiVal, 'pull out')) {
                $soType = 'PPB';
            } else {
                $soType = 'PDL';
            }
        }

        // Build title
        $categoryLabel = Task::categories()[$category] ?? strtoupper($category);
        $title = $categoryLabel;
        if ($soType) {
            $title .= ' ['.$soType.']';
        }
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
        $description = $this->buildDescription($row, $columnMap, $soType);

        // Normalize KP
        $kp = $this->normalizeKp($get('kp')) ?? $this->normalizeKp($get('sub_bidang'));

        // Normalize kategori segmen
        $kategoriSegmen = $this->normalizeSegmen($get('kategori_segmen'), $customerName);

        // Normalize status: gunakan mapping per kategori
        $rawStatus = $get('status');
        $status = $this->resolveStatusForCategory($category, $rawStatus);

        // Parse rp_exception
        $rpException = $this->parseRpException($get('rp_exception'));

        // Parse harga_baru
        $hargaBaru = $this->parseAccountingNumber($get('harga_baru'));

        // Parse dates
        $dates = $this->parseDates($row, $columnMap);

        // Comment extraction for sso_open, baa, exception, kontrak_exp (BAI is skipped)
        $comment = null;
        if (in_array($category, self::allowedCommentCategories(), true)) {
            $commentCol = $columnMap['import_comment'] ?? $columnMap['so_comment'] ?? null;
            if ($commentCol !== null) {
                $rawComment = $row[$commentCol] ?? null;
                if ($rawComment !== null) {
                    $cleanComment = preg_replace('/[\x{00A0}\x{200B}-\x{200D}\x{FEFF}]/u', ' ', (string) $rawComment) ?? (string) $rawComment;
                    $trimmedComment = trim($cleanComment);
                    if ($trimmedComment !== '' && ! preg_match('/^[\s\-\x{2013}\x{2014}]+$/u', $trimmedComment)) {
                        $comment = Task::extractKendalaFromDescription($trimmedComment) ?: $trimmedComment;
                    }
                }
            }

            // Fallback to Kendala portion only if no dedicated comment column was found in the sheet
            if ($comment === null && $commentCol === null && ! empty($description) && $category !== Task::CATEGORY_SSO_OPEN) {
                $comment = Task::extractKendalaFromDescription($description);
            }
        }

        return [
            'document_number' => $docNumber,
            'id_pelanggan' => $idPelanggan,
            'category' => $category,
            'title' => $title,
            'description' => $description ?: null,
            'customer_name' => $customerName ?: null,
            'service_type' => $serviceType ?: null,
            'kp' => $kp,
            'kategori_segmen' => $kategoriSegmen,
            'priority' => 'medium',
            'status' => $status,
            'raw_status' => $rawStatus !== '' ? $rawStatus : null,
            'rp_exception' => $rpException,
            'harga_baru' => $hargaBaru,
            'start_date' => $dates['start_date'],
            'due_date' => $dates['due_date'],
            'sales_identifier' => $sales, // for user matching in controller
            'confirm_to' => $confirmTo,
            'comments' => $comment,
        ];
    }

    /**
     * Parse accounting currency number into numeric float.
     */
    public function parseAccountingNumber(mixed $value): float
    {
        if ($value === null) {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $clean = trim((string) $value);
        if ($clean === '' || $clean === '-' || $clean === '0' || $clean === '0.00' || $clean === '0,00') {
            return 0.0;
        }

        $clean = trim($clean, "\"' \t\n\r\0\x0B");
        $clean = preg_replace('/^(rp|idr)\.?\s*/i', '', $clean);

        if (str_contains($clean, '.') && str_contains($clean, ',')) {
            $lastDot = strrpos($clean, '.');
            $lastComma = strrpos($clean, ',');
            if ($lastDot > $lastComma) {
                $clean = str_replace([',', ' '], '', $clean);
            } else {
                $clean = str_replace(['.', ' '], '', $clean);
                $clean = str_replace(',', '.', $clean);
            }
        } elseif (str_contains($clean, ',')) {
            if (substr_count($clean, ',') > 1 || preg_match('/,\d{3}$/', $clean)) {
                $clean = str_replace([',', ' '], '', $clean);
            } else {
                $clean = str_replace(' ', '', $clean);
                $clean = str_replace(',', '.', $clean);
            }
        } else {
            $clean = str_replace(' ', '', $clean);
        }

        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    /**
     * Parse rp_exception accounting value into numeric float.
     */
    public function parseRpException(mixed $value): float
    {
        return $this->parseAccountingNumber($value);
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
    public function normalizeSegmen(string $value, ?string $customerName = null): string
    {
        $lower = strtolower(trim($value));

        if (str_contains($lower, 'pln')) {
            return Task::SEGMEN_PLN;
        }

        if ($customerName !== null && str_contains(strtolower($customerName), 'pln')) {
            return Task::SEGMEN_PLN;
        }

        return Task::SEGMEN_PUBLIK;
    }

    /**
     * Resolve task status based on category and raw status string.
     */
    public function resolveStatusForCategory(?string $category, ?string $rawStatus): string
    {
        $raw = trim((string) ($rawStatus ?? ''));
        $lower = strtolower(preg_replace('/\s+/', ' ', $raw));

        if ($category === Task::CATEGORY_BAA) {
            if ($lower === '') {
                return Task::STATUS_IN_PROGRESS;
            }

            if (str_contains($lower, 'done') || str_contains($lower, 'selesai')) {
                return Task::STATUS_APPROVED;
            }

            if (
                str_contains($lower, 'on process')
                || str_contains($lower, 'on progress')
                || str_contains($lower, 'on proses')
                || str_contains($lower, 'sedang dikerjakan')
            ) {
                return Task::STATUS_IN_PROGRESS;
            }

            return Task::STATUS_IN_PROGRESS;
        }

        if ($category === Task::CATEGORY_EXCEPTION) {
            if ($lower === '') {
                return Task::STATUS_IN_PROGRESS;
            }

            // Status CLOSE / CLOSED -> approved
            if (str_contains($lower, 'close') || str_contains($lower, 'closed')) {
                return Task::STATUS_APPROVED;
            }

            // Status EXCEPTION OPEN (abaikan huruf besar/kecil dan spasi) / open -> in_progress
            if (str_contains($lower, 'exception open') || str_contains($lower, 'open')) {
                return Task::STATUS_IN_PROGRESS;
            }

            return Task::STATUS_IN_PROGRESS;
        }

        if ($category === Task::CATEGORY_SSO_OPEN) {
            if ($lower === '') {
                return Task::STATUS_IN_PROGRESS;
            }

            if (str_contains($lower, 'done') || str_contains($lower, 'selesai')) {
                return Task::STATUS_APPROVED;
            }

            if (
                str_contains($lower, 'on process')
                || str_contains($lower, 'on progress')
                || str_contains($lower, 'on proses')
                || str_contains($lower, 'sedang dikerjakan')
            ) {
                return Task::STATUS_IN_PROGRESS;
            }

            return Task::STATUS_IN_PROGRESS;
        }

        if ($category === Task::CATEGORY_KONTRAK_EXP) {
            $cleaned = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', (string) $rawStatus) ?? (string) $rawStatus;
            $cleaned = preg_replace('/[\x{00A0}\x{202F}]/u', ' ', $cleaned) ?? $cleaned;
            $normalized = mb_strtolower(trim($cleaned), 'UTF-8');
            $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

            if ($normalized === '') {
                return Task::STATUS_IN_PROGRESS;
            }

            if (str_contains($normalized, 'done') || str_contains($normalized, 'selesai')) {
                return Task::STATUS_APPROVED;
            }

            if (
                str_contains($normalized, 'on process')
                || str_contains($normalized, 'on progress')
                || str_contains($normalized, 'on proses')
                || str_contains($normalized, 'sedang dikerjakan')
            ) {
                return Task::STATUS_IN_PROGRESS;
            }

            // Kosong atau tidak dikenal -> Task::STATUS_IN_PROGRESS untuk sementara
            return Task::STATUS_IN_PROGRESS;
        }

        // Kategori lain (BAI): tetap pending seperti sebelum perubahan BAA
        return Task::STATUS_PENDING;
    }

    /**
     * Normalize SBU status to ICON task status (legacy helper).
     */
    public function normalizeStatus(string $value, ?string $category = Task::CATEGORY_BAA): string
    {
        return $this->resolveStatusForCategory($category, $value);
    }

    /**
     * Build a combined description from price data, kendala, alasan, etc.
     *
     * @param  list<mixed>  $row
     * @param  ColumnMap  $columnMap
     */
    private function buildDescription(array $row, array $columnMap, ?string $soType = null): string
    {
        $get = fn (string $field): string => trim((string) ($row[$columnMap[$field] ?? -1] ?? ''));

        $parts = [];

        if ($soType) {
            $parts[] = 'Tipe SO: '.$soType;
        }

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
        $dueCandidates = ['tanggal_komitmen', 'tgl_target_aktivasi', 'tanggal_kontrak_berakhir'];
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

        // If target_days is present, check if it is a specific date or a day offset
        if ($dueDate === null) {
            $targetVal = $get('target_days');
            if ($targetVal !== '' && $targetVal !== '-' && $targetVal !== '--/--/--') {
                $parsedTarget = $this->tryParseDate($targetVal);
                if ($parsedTarget) {
                    $dueDate = $parsedTarget;
                } elseif (is_numeric($targetVal) && (float) $targetVal < 1000 && $startDate !== null) {
                    try {
                        $dueDate = Carbon::parse($startDate)->addDays((int) ceil((float) $targetVal))->toDateString();
                    } catch (\Exception) {
                        // ignore
                    }
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
     * Try to parse a date string or Excel serial number from various SBU formats.
     */
    public function tryParseDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Handle numeric Excel date serials (e.g. 46290 = 2026-09-25)
        if (is_numeric($value)) {
            $num = (float) $value;
            // Valid Excel serial dates between year 2000 (~36526) and 2050 (~54789)
            if ($num >= 35000 && $num <= 55000) {
                try {
                    $dt = Date::excelToDateTimeObject($num);

                    return Carbon::instance($dt)->toDateString();
                } catch (\Exception) {
                    // continue
                }
            }
        }

        $cleanStr = trim((string) $value);
        if ($cleanStr === '' || $cleanStr === '-' || $cleanStr === '--/--/--' || $cleanStr === '/ /') {
            return null;
        }

        // If numeric string representing Excel serial
        if (is_numeric($cleanStr)) {
            $num = (float) $cleanStr;
            if ($num >= 35000 && $num <= 55000) {
                try {
                    $dt = Date::excelToDateTimeObject($num);

                    return Carbon::instance($dt)->toDateString();
                } catch (\Exception) {
                    // continue
                }
            }
        }

        // Remove time portion if present (e.g., "29/06/2026 10.39")
        $cleanStr = preg_replace('/\s+\d{1,2}[.:]\d{2}(:\d{2})?$/', '', $cleanStr);

        try {
            // Try Carbon::parse which handles many formats (including m/d/Y or Y-m-d)
            $date = Carbon::parse($cleanStr);

            // Sanity check: year should be reasonable (2020-2035)
            if ($date->year < 2020 || $date->year > 2035) {
                return null;
            }

            return $date->toDateString();
        } catch (\Exception) {
            // Try manual parsing for dd/mm/yyyy or mm/dd/yyyy format
            if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{2,4})$#', $cleanStr, $matches)) {
                $p1 = (int) $matches[1];
                $p2 = (int) $matches[2];
                $year = (int) $matches[3];

                if ($year < 100) {
                    $year += 2000;
                }

                // If first number > 12, it's definitely day/month/year
                if ($p1 > 12) {
                    $day = $p1;
                    $month = $p2;
                } else {
                    // SBU format is typically MM/DD/YYYY ("Tanggal Upload BAI (MM/DD/YYYY)")
                    $month = $p1;
                    $day = $p2;
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
     * Replaces newlines and multiple spaces with a single space,
     * trims, and converts to lowercase alphanumeric.
     */
    public function normalizeHeader(string $header): string
    {
        // 1. Replace newlines and carriage returns with a single space
        $header = str_replace(["\r\n", "\r", "\n"], ' ', $header);
        // 2. Collapse multiple spaces
        $header = preg_replace('/\s+/', ' ', $header);
        // 3. Lowercase and trim
        $header = strtolower(trim($header));
        // 4. Remove special characters except alphanumeric and whitespace
        $header = preg_replace('/[^a-z0-9\s]/', '', $header);
        // 5. Final collapse of multiple spaces
        $header = preg_replace('/\s+/', ' ', $header);

        return trim($header);
    }

    /**
     * Format document number to avoid scientific notation or floating point artifacts.
     */
    public function formatDocumentNumber(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return sprintf('%.0f', $value);
        }

        $str = trim((string) $value);
        if ($str === '') {
            return '';
        }

        // If scientific notation e.g. 1.42402E+11 or 1.42402e+11
        if (preg_match('/^[+-]?\d+(\.\d+)?[eE][+-]?\d+$/', $str)) {
            return sprintf('%.0f', (float) $str);
        }

        return $str;
    }

    /**
     * Automatically find the header row index (scanning first 30 rows).
     *
     * @param  list<list<mixed>>  $rows
     */
    public function findHeaderRowIndex(array $rows, ?string $targetCategory = null): ?int
    {
        $maxScan = min(count($rows), 30);
        for ($i = 0; $i < $maxScan; $i++) {
            $row = $rows[$i];
            if (! is_array($row) || empty($row)) {
                continue;
            }

            $normalizedCells = [];
            foreach ($row as $cell) {
                if ($cell !== null) {
                    $norm = $this->normalizeHeader((string) $cell);
                    if ($norm !== '') {
                        $normalizedCells[] = $norm;
                    }
                }
            }

            if (empty($normalizedCells)) {
                continue;
            }

            $hasIdPa = false;
            $hasCustomer = false;
            foreach ($normalizedCells as $cell) {
                if (str_contains($cell, 'id pa') || str_contains($cell, 'idpa') || str_contains($cell, 'id pelanggan') || str_contains($cell, 'sid')) {
                    $hasIdPa = true;
                }
                if (str_contains($cell, 'nama pelanggan') || str_contains($cell, 'namapelanggan')) {
                    $hasCustomer = true;
                }
            }

            if ($targetCategory === Task::CATEGORY_BAA) {
                if ($hasIdPa && $hasCustomer) {
                    return $i;
                }
                if ($hasIdPa) {
                    foreach ($normalizedCells as $cell) {
                        if (str_contains($cell, 'layanan') || str_contains($cell, 'sales') || str_contains($cell, 'aging') || str_contains($cell, 'upload')) {
                            return $i;
                        }
                    }
                }
            } else {
                if ($hasIdPa && $hasCustomer) {
                    return $i;
                }
                if ($hasIdPa) {
                    return $i;
                }
                // Check for ICON template header
                $hasCategory = false;
                $hasDoc = false;
                foreach ($normalizedCells as $cell) {
                    if (str_contains($cell, 'kategori')) {
                        $hasCategory = true;
                    }
                    if (str_contains($cell, 'nomor dokumen') || str_contains($cell, 'judul')) {
                        $hasDoc = true;
                    }
                }
                if ($hasCategory && $hasDoc) {
                    return $i;
                }
            }
        }

        return null;
    }

    /**
     * Trim trailing empty cells from row and check if row has any content.
     *
     * @param  list<mixed>  $row
     */
    public function isRowEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
