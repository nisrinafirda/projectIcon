<?php

namespace App\Http\Controllers;

use App\Models\ActivityLogDetail;
use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\SbuSheetMapper;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskImportExportController extends Controller
{
    //
    /**
     * Show import form and template guide.
     */
    public function showImport(Request $request): View
    {
        $categories = Task::categories();
        $employees = User::where('role', 'user')->orderBy('name')->get();

        $selectedCategory = $request->query('category');
        if ($selectedCategory && ! array_key_exists($selectedCategory, $categories)) {
            $selectedCategory = null;
        }

        $selectedCategoryLabel = $selectedCategory ? $categories[$selectedCategory] : null;

        return view('tasks.import', compact('categories', 'employees', 'selectedCategory', 'selectedCategoryLabel'));
    }

    /**
     * Download sample Excel template for bulk import.
     */
    public function downloadTemplate(Request $request): StreamedResponse
    {
        $categoryParam = $request->query('category');
        $validCategories = Task::categories();
        $targetCategory = ($categoryParam && array_key_exists($categoryParam, $validCategories)) ? $categoryParam : null;

        $employees = User::where('role', 'user')->orderBy('name')->get();
        $empNames = $employees->pluck('name')->toArray();
        if (empty($empNames)) {
            $empNames = ['Siti Rahma', 'Budi Santoso', 'Ahmad Pratama'];
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheetTitle = $targetCategory ? 'Template '.$validCategories[$targetCategory] : 'Template Import Tugas ICON';
        $sheet->setTitle(substr($sheetTitle, 0, 31));

        // Headers
        $headers = [
            'A1' => 'Kategori ('.implode('/', array_keys($validCategories)).')',
            'B1' => 'Nomor Dokumen (Unik)',
            'C1' => 'Judul Tugas',
            'D1' => 'Nama / NIP / Email Karyawan PIC',
            'E1' => 'Nama Pelanggan / Site',
            'F1' => 'Jenis Layanan',
            'G1' => 'Prioritas (low/medium/high/urgent)',
            'H1' => 'Tanggal Mulai (YYYY-MM-DD)',
            'I1' => 'Deadline (YYYY-MM-DD)',
            'J1' => 'Keterangan / Deskripsi',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Header Styling - ICON Blue Theme
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E40AF'], // Tailwind blue-800
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Sample rows with real employee names to guide user
        if ($targetCategory === 'sso_open') {
            $sampleData = [
                ['sso_open', 'SO-2026-101', 'Aktivasi Penambahan Bandwidth IP Transit', $empNames[0 % count($empNames)], 'Universitas Merdeka Madiun', 'IP VPN 500Mbps', 'high', date('Y-m-d'), date('Y-m-d', strtotime('+3 days')), 'Koordinasi penarikan kabel dan konfigurasi router'],
                ['sso_open', 'SO-2026-102', 'Penyambungan Drop Optik Pelanggan Baru', $empNames[1 % count($empNames)], 'Dinas Kesehatan Prov Jatim', 'Metronet 1Gbps', 'urgent', date('Y-m-d'), date('Y-m-d', strtotime('+2 days')), 'Splicing core 1-4 dan OTDR test'],
                ['sso_open', 'SO-2026-103', 'Migrasi Port Pelanggan VIP', $empNames[2 % count($empNames)], 'PT Bank Jatim Kantor Pusat', 'Dedicated Fiber', 'medium', date('Y-m-d'), date('Y-m-d', strtotime('+5 days')), 'Pergantian perangkat SFP di POP Surabaya'],
            ];
        } elseif ($targetCategory) {
            $catLabel = $validCategories[$targetCategory];
            $sampleData = [
                [$targetCategory, strtoupper($targetCategory).'-2026-101', "Penyelesaian Dokumen {$catLabel} Integrasi", $empNames[0 % count($empNames)], 'PLN UID Jawa Timur', 'Integrasi Sistem', 'high', date('Y-m-d'), date('Y-m-d', strtotime('+3 days')), 'Lengkapi berkas dan tanda tangan mitra'],
                [$targetCategory, strtoupper($targetCategory).'-2026-102', "Uji Terima {$catLabel} Penarikan Fiber Optik", $empNames[1 % count($empNames)], 'Dinas Kominfo Jawa Timur', 'Fiber Optic', 'urgent', date('Y-m-d'), date('Y-m-d', strtotime('+2 days')), 'Pemeriksaan fisik dan lampiran berita acara'],
                [$targetCategory, strtoupper($targetCategory).'-2026-103', "Verifikasi & Administrasi {$catLabel}", $empNames[2 % count($empNames)], 'PT Semen Indonesia', 'Konektivitas IP', 'medium', date('Y-m-d'), date('Y-m-d', strtotime('+5 days')), 'Tinjau catatan hasil pengerjaan'],
            ];
        } else {
            $sampleData = [
                ['baa', 'BAA-2026-901', 'BAA Aktivasi Link Fiber Optik', $empNames[0 % count($empNames)], 'PT Global Data', 'Metronet 1Gbps', 'high', date('Y-m-d'), date('Y-m-d', strtotime('+3 days')), 'Lengkapi BAA dan tanda tangan mitra'],
                ['bai', 'BAI-2026-902', 'BAI Penggantian SFP OLT Surabaya', $empNames[1 % count($empNames)], 'POP Surabaya Gubeng', 'OLT Upgrade', 'urgent', date('Y-m-d'), date('Y-m-d', strtotime('+2 days')), 'Pemeriksaan port fisik dan redudansi'],
                ['sso_open', 'SO-2026-903', 'Aktivasi Penambahan Bandwidth IP Transit', $empNames[2 % count($empNames)], 'Bank Mandiri', 'Dedicated Internet', 'urgent', date('Y-m-d'), date('Y-m-d', strtotime('+2 days')), 'Investigasi loss packet'],
                ['exception', 'EXC-2026-904', 'Laporan Exception Pemadaman Gardu', $empNames[0 % count($empNames)], 'PLN UID', 'SCADA', 'medium', date('Y-m-d'), date('Y-m-d', strtotime('+4 days')), 'Klaim SLA force majeure'],
                ['kontrak_exp', 'KTR-2026-905', 'Renewal Sewa Rack Co-Location', $empNames[1 % count($empNames)], 'PT Nusantara Cloud', 'Data Center', 'high', date('Y-m-d'), date('Y-m-d', strtotime('+7 days')), 'Konfirmasi perpanjangan sewa'],
            ];
        }

        $row = 2;
        foreach ($sampleData as $data) {
            $col = 'A';
            foreach ($data as $val) {
                $sheet->setCellValue($col.$row, $val);
                $col++;
            }
            $row++;
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = $targetCategory ? "template_import_{$targetCategory}.xlsx" : 'template_import_tugas_icon.xlsx';

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Process bulk import of tasks with anti-duplicate logic.
     */
    public function import(Request $request): RedirectResponse
    {
        $categoryLabels = Task::categories();
        $validCategories = array_keys($categoryLabels);

        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv,txt', 'max:20480'],
            'duplicate_action' => ['required', 'in:skip,update,fail'],
            'default_user_id' => ['nullable', 'exists:users,id'],
            'target_category' => ['nullable', 'string', 'in:'.implode(',', $validCategories)],
            'source_category' => ['nullable', 'string', 'in:'.implode(',', $validCategories)],
        ], [
            'file.extensions' => 'File harus berupa file spreadsheet dengan format: .xlsx, .xls, .csv, atau .txt.',
            'file.max' => 'Ukuran file tidak boleh melebihi 20 MB.',
        ]);

        $file = $request->file('file');
        $duplicateAction = $request->input('duplicate_action');
        $defaultUserId = $request->input('default_user_id');
        $targetCategory = $request->input('target_category');
        $sourceCategory = $request->query('category') ?: $request->input('source_category');

        // Requirement 3.4: Jika import dibuka dari modul tertentu, target_category wajib terkirim. Kalau tidak ada, tampilkan error, jangan menebak.
        if ($sourceCategory && ! $targetCategory) {
            $sourceLabel = $categoryLabels[$sourceCategory] ?? $sourceCategory;

            return back()->withInput()->withErrors([
                'target_category' => "Import dibuka khusus dari modul {$sourceLabel}. Target Modul Kategori Tugas wajib terkirim dan tidak boleh kosong.",
            ]);
        }

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['file' => 'Gagal membaca file spreadsheet: '.$e->getMessage()]);
        }

        $sbuMapper = new SbuSheetMapper;
        $sheetNames = $spreadsheet->getSheetNames();
        if (empty($sheetNames)) {
            return back()->withInput()->withErrors(['file' => 'File tidak memiliki sheet yang dapat dibaca.']);
        }

        /** @var Worksheet|null $sheet */
        $sheet = null;
        $headerRowIndex = null;

        if ($targetCategory) {
            $targetCategoryLabel = $categoryLabels[$targetCategory] ?? strtoupper($targetCategory);

            // Requirement 3.1: Telusuri semua sheet (getSheetNames) dan pilih sheet yang namanya cocok dengan target.
            $sheetCategoryMap = [];
            $matchingSheetName = null;

            foreach ($sheetNames as $sName) {
                $detected = $this->detectCategoryFromSheetTitle($sName);
                if ($detected) {
                    $sheetCategoryMap[$sName] = $detected;
                    if ($detected === $targetCategory && $matchingSheetName === null) {
                        $matchingSheetName = $sName;
                    }
                }
            }

            if ($matchingSheetName !== null) {
                $sheet = $spreadsheet->getSheetByName($matchingSheetName);
                if ($sheet) {
                    $rows = $sheet->toArray(null, true, false, false);
                    $headerRowIndex = $sbuMapper->findHeaderRowIndex($rows, $targetCategory);
                }
            } elseif ($targetCategory === Task::CATEGORY_BAA) {
                // Khusus BAA: Jika tidak ditemukan sheet dengan nama eksplisit BAA,
                // cari sheet pertama yang header-nya cocok dengan format BAA (ada kolom "ID PA" dan "NAMA PELANGGAN").
                foreach ($spreadsheet->getAllSheets() as $candSheet) {
                    $candDetected = $this->detectCategoryFromSheetTitle($candSheet->getTitle());
                    if ($candDetected && $candDetected !== Task::CATEGORY_BAA) {
                        continue;
                    }

                    $sRows = $candSheet->toArray(null, true, false, false);
                    $hIndex = $sbuMapper->findHeaderRowIndex($sRows, Task::CATEGORY_BAA);
                    if ($hIndex !== null) {
                        $catInfo = $sbuMapper->detectCategoryWithMarkers($sRows[$hIndex]);
                        if ($catInfo['category'] === null || $catInfo['category'] === Task::CATEGORY_BAA) {
                            $sheet = $candSheet;
                            $headerRowIndex = $hIndex;
                            break;
                        }
                    }
                }

                if (! $sheet && ! empty($sheetCategoryMap)) {
                    $detectedDetails = [];
                    $detectedCategoryNames = [];
                    foreach ($sheetCategoryMap as $sName => $sCat) {
                        $lbl = $categoryLabels[$sCat] ?? $sCat;
                        $detectedDetails[] = "'{$sName}' (terdeteksi sebagai {$lbl})";
                        $detectedCategoryNames[$sCat] = $lbl;
                    }
                    $foundSheetsList = implode(', ', array_map(fn ($n) => "'{$n}'", $sheetNames));
                    $detectedNamesStr = implode(', ', array_unique($detectedCategoryNames));

                    return back()->withInput()->withErrors([
                        'file' => "Import ditolak: tidak ditemukan sheet untuk modul {$targetCategoryLabel}. "
                            ."Sheet yang ditemukan dalam file: {$foundSheetsList} [".implode(', ', $detectedDetails).']. '
                            ."File ini terdeteksi sebagai {$detectedNamesStr}, sedangkan modul yang dipilih {$targetCategoryLabel}.",
                    ]);
                }
            } elseif (! empty($sheetCategoryMap)) {
                // Sheet teridentifikasi ada, tapi tidak ada yang cocok dengan target category
                $detectedDetails = [];
                $detectedCategoryNames = [];
                foreach ($sheetCategoryMap as $sName => $sCat) {
                    $lbl = $categoryLabels[$sCat] ?? $sCat;
                    $detectedDetails[] = "'{$sName}' (terdeteksi sebagai {$lbl})";
                    $detectedCategoryNames[$sCat] = $lbl;
                }
                $foundSheetsList = implode(', ', array_map(fn ($n) => "'{$n}'", $sheetNames));
                $detectedNamesStr = implode(', ', array_unique($detectedCategoryNames));

                return back()->withInput()->withErrors([
                    'file' => "Import ditolak: tidak ditemukan sheet untuk modul {$targetCategoryLabel}. "
                        ."Sheet yang ditemukan dalam file: {$foundSheetsList} [".implode(', ', $detectedDetails).']. '
                        ."File ini terdeteksi sebagai {$detectedNamesStr}, sedangkan modul yang dipilih {$targetCategoryLabel}.",
                ]);
            } else {
                // Kategori tidak dapat dideteksi dari nama sheet; ambil sheet pertama
                $sheet = $spreadsheet->getSheet(0);
                if ($sheet) {
                    $rows = $sheet->toArray(null, true, false, false);
                    $headerRowIndex = $sbuMapper->findHeaderRowIndex($rows, $targetCategory);
                }
            }
        } else {
            $sheet = $spreadsheet->getSheet(0);
            if ($sheet) {
                $rows = $sheet->toArray(null, true, false, false);
                $headerRowIndex = $sbuMapper->findHeaderRowIndex($rows, null);
            }
        }

        if (! $sheet) {
            if ($targetCategory === Task::CATEGORY_BAA) {
                return back()->withInput()->withErrors([
                    'file' => "Import ditolak: Tidak ditemukan sheet yang cocok dengan format BAA (kolom 'ID PA' dan 'NAMA PELANGGAN' tidak ditemukan pada sheet mana pun di file ini).",
                ]);
            }

            return back()->withInput()->withErrors(['file' => 'Tidak ada sheet yang dapat diproses dari file ini.']);
        }

        $rows = $sheet->toArray(null, true, false, false);
        if (count($rows) <= 1) {
            return back()->withInput()->withErrors(['file' => "Sheet '{$sheet->getTitle()}' kosong atau hanya berisi baris judul header."]);
        }

        if ($headerRowIndex === null) {
            $headerRowIndex = $sbuMapper->findHeaderRowIndex($rows, $targetCategory);
        }

        if ($headerRowIndex === null) {
            if ($targetCategory === Task::CATEGORY_BAA) {
                return back()->withInput()->withErrors([
                    'file' => "Import ditolak: Kolom 'ID PA' dan 'NAMA PELANGGAN' tidak ditemukan di sheet '{$sheet->getTitle()}'. Pastikan sheet memiliki baris header yang valid.",
                ]);
            }

            return back()->withInput()->withErrors([
                'file' => "Import ditolak: Baris header kolom (ID PA atau Nomor Dokumen) tidak ditemukan di sheet '{$sheet->getTitle()}'.",
            ]);
        }

        $headerRow = $rows[$headerRowIndex];
        $isSbuFormat = $sbuMapper->detectFormat($headerRow) === 'sbu';

        // Requirement 3.2 & 3.3: Pemeriksaan kedua (berdasarkan penanda isi) SEBELUM loop insert
        if ($isSbuFormat) {
            if (! $targetCategory) {
                return back()->withInput()->withErrors(['file' => 'Format file Project Monitoring (SBU) terdeteksi. Silakan pilih Target Modul Kategori Tugas di form sebelum mengimpor file ini.']);
            }

            $sbuCategoryInfo = $sbuMapper->detectCategoryWithMarkers($headerRow);
            $detectedSbuCat = $sbuCategoryInfo['category'];

            if ($detectedSbuCat && $detectedSbuCat !== $targetCategory) {
                $detectedLabel = $categoryLabels[$detectedSbuCat] ?? $detectedSbuCat;
                $targetLabel = $categoryLabels[$targetCategory] ?? $targetCategory;
                $markerInfo = ! empty($sbuCategoryInfo['markers']) ? " (penanda: '".implode("', '", $sbuCategoryInfo['markers'])."')" : '';

                return back()->withInput()->withErrors([
                    'file' => "Import ditolak: file ini terdeteksi sebagai {$detectedLabel}{$markerInfo}, sedangkan modul yang dipilih {$targetLabel}.",
                ]);
            }

            $columnMap = $sbuMapper->buildColumnMap($headerRow, $targetCategory);
        } else {
            // Format Template ICON
            $columnMap = [];

            if ($targetCategory) {
                $targetLabel = $categoryLabels[$targetCategory] ?? $targetCategory;
                $mismatchedRows = [];
                $mismatchedCategories = [];

                for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
                    $row = $rows[$i];
                    $rowNum = $i + 1;

                    // Lewati baris yang benar-benar kosong
                    $hasAnyData = false;
                    foreach ($row as $val) {
                        if (trim((string) $val) !== '') {
                            $hasAnyData = true;
                            break;
                        }
                    }
                    if (! $hasAnyData) {
                        continue;
                    }

                    $rawCategory = trim((string) ($row[0] ?? ''));
                    $rowCategory = $this->normalizeCategory($rawCategory);
                    $docNumber = trim((string) ($row[1] ?? ''));

                    if ($rowCategory) {
                        if ($rowCategory !== $targetCategory) {
                            $mismatchedRows[] = $rowNum;
                            $mismatchedCategories[$rowCategory] = $categoryLabels[$rowCategory] ?? $rowCategory;
                        }
                    } else {
                        // Kolom Kategori kosong diperlakukan sebagai ikut target, tapi periksa apakah penanda nomor dokumen bertentangan
                        $detectedDocCat = $this->detectCategoryFromDocNumber($docNumber);
                        if ($detectedDocCat && $detectedDocCat !== $targetCategory) {
                            $mismatchedRows[] = $rowNum;
                            $catName = $categoryLabels[$detectedDocCat] ?? $detectedDocCat;
                            $mismatchedCategories[$detectedDocCat] = "{$catName} (nomor dokumen: {$docNumber})";
                        }
                    }
                }

                if (! empty($mismatchedRows)) {
                    $rowList = implode(', ', array_slice($mismatchedRows, 0, 10)).(count($mismatchedRows) > 10 ? ', ...' : '');
                    $catList = implode(', ', array_unique($mismatchedCategories));

                    return back()->withInput()->withErrors([
                        'file' => "Import ditolak: file ini terdeteksi sebagai {$catList}, sedangkan modul yang dipilih {$targetLabel}. "
                            .'Total '.count($mismatchedRows)." baris tidak sesuai (baris: {$rowList}). Tidak ada data yang diimpor.",
                    ]);
                }
            }
        }

        // Cache all users for high-speed multi-attribute matching (Name, Email, NIP, First Name)
        $allUsers = User::all();
        $usersByEmail = [];
        $usersByNip = [];
        $usersByName = [];
        $usersByFirstName = [];

        foreach ($allUsers as $u) {
            if ($u->email) {
                $usersByEmail[strtolower(trim($u->email))] = $u;
            }
            if ($u->nip) {
                $usersByNip[strtolower(trim($u->nip))] = $u;
            }
            $cleanName = strtolower(preg_replace('/\s+/', ' ', trim($u->name)));
            $usersByName[$cleanName] = $u;

            // Index first name if unambiguous
            $parts = explode(' ', $cleanName);
            $firstName = $parts[0] ?? '';
            if ($firstName && strlen($firstName) >= 3) {
                if (! array_key_exists($firstName, $usersByFirstName)) {
                    $usersByFirstName[$firstName] = $u;
                } else {
                    $usersByFirstName[$firstName] = false; // ambiguous first name
                }
            }
        }

        $insertedCount = 0;
        $updatedCount = 0;
        $unchangedCount = 0;
        $skippedCount = 0;
        $importedCommentsCount = 0;
        $detailRecords = [];
        $errors = [];
        $importWarnings = [];
        $distribution = [];

        DB::beginTransaction();
        try {
            // Skip header row(s) and start from data row
            for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $rowNum = $i + 1;

                if ($sbuMapper->isRowEmpty($row)) {
                    continue;
                }

                if ($isSbuFormat) {
                    $mapped = $sbuMapper->mapRow($row, $columnMap, $targetCategory);
                    if ($mapped === null) {
                        continue;
                    }

                    $category = $mapped['category'];
                    $docNumber = $mapped['document_number'];
                    $title = $mapped['title'];
                    $description = $mapped['description'];
                    $customerName = $mapped['customer_name'];
                    $serviceType = $mapped['service_type'];
                    $kp = $mapped['kp'];
                    $kategoriSegmen = $mapped['kategori_segmen'];
                    $priority = $mapped['priority'];
                    $status = $mapped['status'] ?? Task::STATUS_PENDING;
                    $rawStatus = $mapped['raw_status'] ?? null;
                    $parsedStartDate = $mapped['start_date'];
                    $parsedDueDate = $mapped['due_date'];
                    $userIdentifier = $mapped['sales_identifier'];
                    $rpException = $mapped['rp_exception'] ?? 0;
                    $hargaBaru = $mapped['harga_baru'] ?? 0;
                    $comments = $mapped['comments'] ?? null;
                    $confirmTo = $mapped['confirm_to'] ?? null;
                    $idPelanggan = $mapped['id_pelanggan'] ?? null;
                } else {
                    $rawCategory = strtolower(trim($row[0] ?? ''));
                    $docNumber = trim((string) ($row[1] ?? ''));
                    $title = trim($row[2] ?? '');
                    $userIdentifier = trim((string) ($row[3] ?? ''));
                    $customerName = trim($row[4] ?? '');
                    $serviceType = trim($row[5] ?? '');
                    $priority = strtolower(trim($row[6] ?? 'medium'));
                    $startDate = trim($row[7] ?? '');
                    $dueDate = trim($row[8] ?? '');
                    $description = trim($row[9] ?? '');
                    $kp = null;
                    $kategoriSegmen = Task::SEGMEN_PUBLIK;
                    $status = Task::STATUS_PENDING;
                    $rawStatus = null;
                    $rpException = 0;
                    $hargaBaru = 0;
                    $comments = null;
                    $confirmTo = null;
                    $idPelanggan = null;

                    // Skip completely empty rows
                    if (! $docNumber && ! $title && ! $rawCategory && ! $userIdentifier) {
                        continue;
                    }

                    // Category resolution: use selected target_category if specified, otherwise parse from row or detect from doc number
                    if ($targetCategory) {
                        $category = $targetCategory;
                    } else {
                        $category = $this->normalizeCategory($rawCategory) ?? $this->detectCategoryFromDocNumber($docNumber);
                    }

                    if (! $category || ! in_array($category, $validCategories, true)) {
                        $errors[] = "Baris #{$rowNum}: Kategori '{$row[0]}' tidak dikenali. Tentukan Target Modul di form atau isi kolom kategori sesuai template.";

                        continue;
                    }

                    if (! $docNumber) {
                        $errors[] = "Baris #{$rowNum}: Nomor dokumen wajib diisi.";

                        continue;
                    }

                    if (! $title) {
                        $title = 'Tugas '.strtoupper($category).' - '.$docNumber;
                    }

                    // Priority default check
                    if (! in_array($priority, ['low', 'medium', 'high', 'urgent'], true)) {
                        $priority = 'medium';
                    }

                    // Parse dates
                    $parsedStartDate = null;
                    $parsedDueDate = null;
                    try {
                        if ($startDate) {
                            $parsedStartDate = Carbon::parse($startDate)->toDateString();
                        }
                        if ($dueDate) {
                            $parsedDueDate = Carbon::parse($dueDate)->toDateString();
                        } else {
                            $parsedDueDate = Carbon::today()->addDays(2)->toDateString();
                        }
                    } catch (\Exception) {
                        $parsedDueDate = Carbon::today()->addDays(2)->toDateString();
                    }
                }

                // Match user by Email, NIP, Full Name, or First Name
                $targetUser = null;
                if ($userIdentifier) {
                    $cleanIdent = strtolower(trim($userIdentifier));
                    $cleanIdent = preg_replace('/\s*\([^)]*\)/', '', $cleanIdent);
                    $cleanIdent = preg_replace('/[^a-z0-9\s]/', ' ', $cleanIdent);
                    $cleanIdent = preg_replace('/\s+/', ' ', trim($cleanIdent));

                    if (isset($usersByEmail[$cleanIdent])) {
                        $targetUser = $usersByEmail[$cleanIdent];
                    } elseif (isset($usersByNip[$cleanIdent])) {
                        $targetUser = $usersByNip[$cleanIdent];
                    } elseif (isset($usersByName[$cleanIdent])) {
                        $targetUser = $usersByName[$cleanIdent];
                    } else {
                        // Check partial / contains full name
                        foreach ($usersByName as $uname => $u) {
                            if (str_contains($cleanIdent, $uname) || str_contains($uname, $cleanIdent)) {
                                $targetUser = $u;
                                break;
                            }
                        }

                        // Check unique first name
                        if (! $targetUser) {
                            $identFirstWord = explode(' ', $cleanIdent)[0] ?? '';
                            if ($identFirstWord && ! empty($usersByFirstName[$identFirstWord])) {
                                $targetUser = $usersByFirstName[$identFirstWord];
                            }
                        }

                        // Check name similarity using similar_text
                        if (! $targetUser) {
                            $bestSimilarity = 0.0;
                            $bestMatchUser = null;

                            foreach ($usersByName as $cleanUName => $u) {
                                similar_text($cleanIdent, $cleanUName, $percent);
                                if ($percent > $bestSimilarity) {
                                    $bestSimilarity = $percent;
                                    $bestMatchUser = $u;
                                }
                            }

                            if ($bestMatchUser) {
                                if ($bestSimilarity >= 90.0) {
                                    // Rule 1: >= 90% -> Auto match and assign
                                    $targetUser = $bestMatchUser;
                                } elseif ($bestSimilarity >= 70.0) {
                                    // Rule 2: 70% - 89% -> Keep fallback, flag as "Perlu Konfirmasi"
                                    $percentFormatted = number_format($bestSimilarity, 1);
                                    $importWarnings[] = "Baris #{$rowNum} ({$docNumber}): Perlu Konfirmasi — '{$userIdentifier}' mirip ({$percentFormatted}%) dengan '{$bestMatchUser->name}'. Tugas sementara dialihkan ke fallback (Admin/Default). Tolong cek manual di halaman Kelola Karyawan atau edit task ini.";
                                }
                                // Rule 3: < 70% -> Ordinary fallback, no special note
                            }
                        }
                    }
                }

                if ($targetUser) {
                    $targetUserId = $targetUser->id;
                    $targetUserName = $targetUser->name;
                } else {
                    $targetUserId = $defaultUserId ?: Auth::id();
                    $fallbackUser = $allUsers->firstWhere('id', $targetUserId);
                    $targetUserName = $fallbackUser ? $fallbackUser->name : 'Admin';
                }

                // Anti-duplicate check!
                $existing = Task::where('category', $category)
                    ->where('document_number', $docNumber)
                    ->first();

                if ($existing) {
                    if ($duplicateAction === 'fail') {
                        return back()->withErrors([
                            'file' => "Import dibatalkan: Dokumen duplikat terdeteksi pada baris #{$rowNum} ('{$docNumber}' dalam kategori {$category}).",
                        ]);
                    }

                    if ($duplicateAction === 'skip') {
                        if ($category === Task::CATEGORY_SSO_OPEN && $confirmTo !== null && $existing->confirm_to !== $confirmTo) {
                            $existing->update(['confirm_to' => $confirmTo]);
                        }
                        $skippedCount++;

                        continue;
                    }

                    if ($duplicateAction === 'update') {
                        $updatePayload = [
                            'user_id' => $targetUserId,
                            'title' => $title,
                            'description' => $description ?: $existing->description,
                            'customer_name' => $customerName ?: $existing->customer_name,
                            'service_type' => $serviceType ?: $existing->service_type,
                            'priority' => $priority,
                            'start_date' => $parsedStartDate ?: $existing->start_date,
                            'due_date' => $parsedDueDate ?: $existing->due_date,
                            'sales_name' => $userIdentifier ?: $existing->sales_name,
                            'rp_exception' => $rpException,
                            'harga_baru' => $hargaBaru,
                        ];
                        if ($idPelanggan !== null && $idPelanggan !== '') {
                            $updatePayload['id_pelanggan'] = $idPelanggan;
                        }
                        if ($category === Task::CATEGORY_SSO_OPEN) {
                            $updatePayload['confirm_to'] = $confirmTo;
                        }
                        if ($category === Task::CATEGORY_BAA || $category === Task::CATEGORY_SSO_OPEN) {
                            $updatePayload['status'] = $status;
                            if ($status === Task::STATUS_APPROVED && ! $existing->confirmed_at) {
                                $updatePayload['completed_at'] = now();
                                $updatePayload['confirmed_at'] = now();
                                $updatePayload['confirmed_by'] = Auth::id() ?: $targetUserId;
                                $updatePayload['reviewed_at'] = now();
                                $updatePayload['admin_notes'] = $existing->admin_notes ?: 'Disetujui otomatis dari status import (Done/Selesai).';
                            }
                        } elseif ($category === Task::CATEGORY_EXCEPTION) {
                            $updatePayload['status'] = $status;
                            if ($status === Task::STATUS_APPROVED && ! $existing->confirmed_at) {
                                $updatePayload['completed_at'] = now();
                                $updatePayload['confirmed_at'] = now();
                                $updatePayload['confirmed_by'] = Auth::id() ?: $targetUserId;
                                $updatePayload['reviewed_at'] = now();
                                $updatePayload['admin_notes'] = $existing->admin_notes ?: 'Disetujui otomatis dari status import (CLOSE)';
                            }
                        } elseif ($category === Task::CATEGORY_KONTRAK_EXP) {
                            $updatePayload['status'] = $status;
                            if ($status === Task::STATUS_APPROVED && ! $existing->confirmed_at) {
                                $updatePayload['completed_at'] = now();
                                $updatePayload['confirmed_at'] = now();
                                $updatePayload['confirmed_by'] = Auth::id() ?: $targetUserId;
                                $updatePayload['reviewed_at'] = now();
                                $updatePayload['admin_notes'] = $existing->admin_notes ?: 'Disetujui otomatis dari status import (Done)';
                            }
                        }
                        $allowedCommentCategories = SbuSheetMapper::allowedCommentCategories();

                        if (in_array($category, $allowedCommentCategories, true) && ! empty($comments)) {
                            if (empty(trim((string) $existing->comments))) {
                                $updatePayload['comments'] = $comments;
                                $updatePayload['comment_updated_by'] = Auth::id();
                                $updatePayload['comment_updated_at'] = now();
                                $importedCommentsCount++;
                            }
                        }
                        if ($kp) {
                            $updatePayload['kp'] = $kp;
                        }
                        if ($kategoriSegmen) {
                            $updatePayload['kategori_segmen'] = $kategoriSegmen;
                        }

                        $existing->fill($updatePayload);
                        $dirty = $existing->getDirty();

                        $changedFields = [];
                        foreach ($dirty as $field => $newValue) {
                            if (in_array($field, ['id', 'created_at', 'updated_at'], true)) {
                                continue;
                            }

                            $oldValue = $existing->getOriginal($field);
                            if (! $this->areValuesDifferent($field, $oldValue, $newValue)) {
                                $existing->setAttribute($field, $oldValue);

                                continue;
                            }

                            $changedFields[] = [
                                'task_id' => $existing->id,
                                'customer_name' => $existing->customer_name ?: $customerName,
                                'sid' => $existing->document_number ?: $docNumber,
                                'change_type' => 'updated',
                                'field' => $field,
                                'field_label' => $this->formatFieldLabel($field),
                                'old_value' => $this->formatFieldValueForLog($field, $oldValue, $allUsers),
                                'new_value' => $this->formatFieldValueForLog($field, $newValue, $allUsers),
                            ];
                        }

                        if (! empty($changedFields)) {
                            $existing->save();
                            $updatedCount++;
                            $distribution[$targetUserName] = ($distribution[$targetUserName] ?? 0) + 1;
                            foreach ($changedFields as $cf) {
                                $detailRecords[] = $cf;
                            }
                        } else {
                            $unchangedCount++;
                        }

                        continue;
                    }
                }

                // Insert new task
                $newTaskPayload = [
                    'user_id' => $targetUserId,
                    'assigned_by' => Auth::id(),
                    'category' => $category,
                    'document_number' => $docNumber,
                    'title' => $title,
                    'description' => $description,
                    'customer_name' => $customerName,
                    'id_pelanggan' => $idPelanggan ?: null,
                    'kp' => $kp,
                    'kategori_segmen' => $kategoriSegmen,
                    'service_type' => $serviceType,
                    'priority' => $priority,
                    'status' => $status,
                    'rp_exception' => $rpException,
                    'harga_baru' => $hargaBaru,
                    'start_date' => $parsedStartDate,
                    'due_date' => $parsedDueDate,
                    'sales_name' => $userIdentifier,
                    'confirm_to' => $confirmTo,
                ];

                if (($category === Task::CATEGORY_BAA || $category === Task::CATEGORY_SSO_OPEN) && $status === Task::STATUS_APPROVED) {
                    $newTaskPayload['completed_at'] = now();
                    $newTaskPayload['confirmed_at'] = now();
                    $newTaskPayload['confirmed_by'] = Auth::id() ?: $targetUserId;
                    $newTaskPayload['reviewed_at'] = now();
                    $newTaskPayload['admin_notes'] = 'Disetujui otomatis dari status import Excel (Done/Selesai).';
                } elseif ($category === Task::CATEGORY_EXCEPTION && $status === Task::STATUS_APPROVED) {
                    $newTaskPayload['completed_at'] = now();
                    $newTaskPayload['confirmed_at'] = now();
                    $newTaskPayload['confirmed_by'] = Auth::id() ?: $targetUserId;
                    $newTaskPayload['reviewed_at'] = now();
                    $newTaskPayload['admin_notes'] = 'Disetujui otomatis dari status import (CLOSE)';
                } elseif ($category === Task::CATEGORY_KONTRAK_EXP && $status === Task::STATUS_APPROVED) {
                    $newTaskPayload['completed_at'] = now();
                    $newTaskPayload['confirmed_at'] = now();
                    $newTaskPayload['confirmed_by'] = Auth::id() ?: $targetUserId;
                    $newTaskPayload['reviewed_at'] = now();
                    $newTaskPayload['admin_notes'] = 'Disetujui otomatis dari status import (Done)';
                }

                $allowedCommentCategories = SbuSheetMapper::allowedCommentCategories();

                if (in_array($category, $allowedCommentCategories, true) && ! empty($comments)) {
                    $newTaskPayload['comments'] = $comments;
                    $newTaskPayload['comment_updated_by'] = Auth::id();
                    $newTaskPayload['comment_updated_at'] = now();
                    $importedCommentsCount++;
                }

                if (! empty($rawStatus)) {
                    $newTaskPayload['submission_notes'] = "[Status Excel: {$rawStatus}]";
                }

                $newTask = Task::create($newTaskPayload);

                $insertedCount++;
                $distribution[$targetUserName] = ($distribution[$targetUserName] ?? 0) + 1;

                $detailRecords[] = [
                    'task_id' => $newTask->id,
                    'customer_name' => $newTask->customer_name ?: $customerName,
                    'sid' => $newTask->document_number ?: $docNumber,
                    'change_type' => 'added',
                    'field' => 'task',
                    'field_label' => 'Tugas Baru',
                    'old_value' => null,
                    'new_value' => $this->formatInitialTaskValue($newTask),
                ];
            }

            $failedCount = count($errors);
            $catLabel = $targetCategory ? strtoupper($targetCategory) : 'SEMUA';
            $summary = "Import massal kategori {$catLabel}: {$insertedCount} ditambahkan, {$updatedCount} diperbarui, {$unchangedCount} tidak berubah";
            if ($skippedCount > 0) {
                $summary .= ", {$skippedCount} dilewati";
            }
            if ($failedCount > 0) {
                $summary .= ", {$failedCount} gagal";
            }
            if ($importedCommentsCount > 0) {
                $summary .= ", {$importedCommentsCount} komentar terisi";
            }

            $activityLog = ActivityLogger::log(
                ActivityLogger::ACTION_IMPORT_DONE,
                $summary,
                null,
                null,
                null,
                null,
                ['category' => $targetCategory]
            );

            if ($activityLog && ! empty($detailRecords)) {
                $now = now();
                $rowsToInsert = [];
                foreach ($detailRecords as $rec) {
                    $rec['activity_log_id'] = $activityLog->id;
                    $rec['created_at'] = $now;
                    $rec['updated_at'] = $now;
                    $rowsToInsert[] = $rec;
                }

                foreach (array_chunk($rowsToInsert, 500) as $chunk) {
                    ActivityLogDetail::insert($chunk);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['file' => 'Terjadi kesalahan saat memproses data import: '.$e->getMessage()]);
        }

        $feedback = ($isSbuFormat ? 'File Monitoring SBU terdeteksi. ' : '')."Import data berhasil! Total {$insertedCount} tugas baru ditambahkan";
        if ($updatedCount > 0) {
            $feedback .= ", {$updatedCount} diperbarui";
        }
        if ($unchangedCount > 0) {
            $feedback .= ", {$unchangedCount} tidak berubah";
        }
        if ($skippedCount > 0) {
            $feedback .= ", {$skippedCount} dilewati (duplikat)";
        }
        if ($importedCommentsCount > 0) {
            $feedback .= ", {$importedCommentsCount} komentar terisi";
        }
        $feedback .= '.';

        if (! empty($distribution)) {
            $distList = [];
            foreach ($distribution as $uName => $count) {
                $distList[] = "{$uName} ({$count} tugas)";
            }
            $feedback .= ' Terdistribusi otomatis ke: '.implode(', ', $distList).'.';
        }

        if (! empty($importWarnings)) {
            $feedback .= ' (Perhatian: ada '.count($importWarnings).' tugas perlu konfirmasi penugasan).';
        }

        if ($targetCategory) {
            return redirect()->route('tasks.category', $targetCategory)
                ->with('success', $feedback)
                ->with('import_errors', $errors)
                ->with('import_warnings', $importWarnings);
        }

        return redirect()->route('tasks.index')
            ->with('success', $feedback)
            ->with('import_errors', $errors)
            ->with('import_warnings', $importWarnings);
    }

    /**
     * Map a raw category text from Excel (column "Kategori") to a valid category key.
     */
    private function normalizeCategory(string $raw): ?string
    {
        return match (strtolower(trim($raw))) {
            'sso', 'so', 'sso_open', 'sso open', 'so open' => Task::CATEGORY_SSO_OPEN,
            'baa' => Task::CATEGORY_BAA,
            'bai' => Task::CATEGORY_BAI,
            'exception', 'exc' => Task::CATEGORY_EXCEPTION,
            'kontrak_exp', 'kontrak exp', 'kontrak' => Task::CATEGORY_KONTRAK_EXP,
            default => null,
        };
    }

    /**
     * Detect the category from an Excel sheet title (e.g. "2 BAI Open", "3 BAA Open").
     * Returns null when the title does not reveal a category.
     */
    private function detectCategoryFromSheetTitle(string $title): ?string
    {
        $title = strtolower(trim($title));

        return match (true) {
            str_contains($title, 'baa') => Task::CATEGORY_BAA,
            str_contains($title, 'bai') => Task::CATEGORY_BAI,
            str_contains($title, 'exception'), str_contains($title, 'exc') => Task::CATEGORY_EXCEPTION,
            str_contains($title, 'contract'), str_contains($title, 'kontrak') => Task::CATEGORY_KONTRAK_EXP,
            str_contains($title, 'so open'), str_contains($title, 'sso'), (bool) preg_match('/\bso\b/i', $title) => Task::CATEGORY_SSO_OPEN,
            default => null,
        };
    }

    /**
     * Detect category from document number prefix/pattern.
     */
    private function detectCategoryFromDocNumber(string $docNumber): ?string
    {
        $doc = strtoupper(trim($docNumber));
        if ($doc === '') {
            return null;
        }

        if (str_starts_with($doc, 'BAA-') || str_starts_with($doc, 'BAA/') || str_starts_with($doc, 'BAA_') || (bool) preg_match('/^BAA\b/i', $doc)) {
            return Task::CATEGORY_BAA;
        }

        if (str_starts_with($doc, 'BAI-') || str_starts_with($doc, 'BAI/') || str_starts_with($doc, 'BAI_') || (bool) preg_match('/^BAI\b/i', $doc)) {
            return Task::CATEGORY_BAI;
        }

        if (str_starts_with($doc, 'SO-') || str_starts_with($doc, 'SSO-') || str_starts_with($doc, 'SO/') || str_starts_with($doc, 'SSO/') || (bool) preg_match('/^(SO|SSO)\b/i', $doc)) {
            return Task::CATEGORY_SSO_OPEN;
        }

        if (str_starts_with($doc, 'EXC-') || str_starts_with($doc, 'EXC/') || (bool) preg_match('/^EXC\b/i', $doc)) {
            return Task::CATEGORY_EXCEPTION;
        }

        if (str_starts_with($doc, 'KTR-') || str_starts_with($doc, 'KTR/') || (bool) preg_match('/^KTR\b/i', $doc)) {
            return Task::CATEGORY_KONTRAK_EXP;
        }

        return null;
    }

    /**
     * Export tasks to Excel (.xlsx).
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        /** @var User $user */
        $user = Auth::user();
        $query = Task::with(['user', 'assigner']);

        $exportCat = $request->input('category');
        ActivityLogger::log(
            ActivityLogger::ACTION_EXPORT_DONE,
            'Mengekspor data tugas'.($exportCat ? ' kategori '.strtoupper($exportCat) : '').' ke Excel',
            null,
            $user,
            null,
            null,
            ['category' => $exportCat]
        );

        // Scoping: Admin sees all, sales/user only sees assigned tasks
        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('sales_name')) {
            $query->where('sales_name', $request->input('sales_name'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('service_type', 'like', "%{$search}%")
                    ->orWhere('sales_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kp')) {
            $query->where('kp', $request->input('kp'));
        }

        if ($request->filled('kategori_segmen')) {
            $query->where('kategori_segmen', $request->input('kategori_segmen'));
        }

        if ($request->filled('deadline_filter')) {
            if ($request->input('deadline_filter') === 'tomorrow') {
                $query->whereDate('due_date', now()->addDay());
            } elseif ($request->input('deadline_filter') === 'today') {
                $query->whereDate('due_date', now());
            } elseif ($request->input('deadline_filter') === 'overdue') {
                $query->whereDate('due_date', '<', now())
                    ->where('status', '!=', Task::STATUS_APPROVED);
            }
        }

        if ($request->filled('month') && is_numeric($request->input('month'))) {
            $query->whereMonth('due_date', (int) $request->input('month'));
        }

        if ($request->filled('year') && is_numeric($request->input('year'))) {
            $query->whereYear('due_date', (int) $request->input('year'));
        }

        $isException = ($request->input('category') === Task::CATEGORY_EXCEPTION || $request->input('category') === 'exception');

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Tugas ICON');

        // Headers: Kategori, KP, ID PA / SID, Nama Pelanggan, Nama Layanan, Sales, Status, Deadline, (rpException), Komentar terbaru
        $headers = [
            'A1' => 'Kategori',
            'B1' => 'KP',
            'C1' => 'ID PA / SID',
            'D1' => 'Nama Pelanggan',
            'E1' => 'Nama Layanan',
            'F1' => 'Sales',
            'G1' => 'Status',
            'H1' => 'Deadline',
        ];

        if ($isException) {
            $headers['I1'] = 'rpException';
            $headers['J1'] = 'Komentar terbaru';
            $highestColumn = 'J';
        } else {
            $headers['I1'] = 'Komentar terbaru';
            $highestColumn = 'I';
        }

        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }

        // Header Styling: Bold, background color, centered
        $sheet->getStyle('A1:'.$highestColumn.'1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1D4ED8'], // Blue-700
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Populate rows with query chunking for performance
        $row = 2;
        $query->orderBy('due_date', 'asc')->chunk(500, function ($tasks) use ($sheet, &$row, $isException): void {
            foreach ($tasks as $task) {
                $sheet->setCellValue('A'.$row, $task->category_label);
                $sheet->setCellValue('B'.$row, $task->kp_label ?: strtoupper($task->kp ?? '-'));
                $sheet->setCellValue('C'.$row, $task->document_number ?: '-');
                $sheet->setCellValue('D'.$row, $task->customer_name ?: '-');
                $sheet->setCellValue('E'.$row, $task->service_type ?: ($task->title ?: '-'));
                $sheet->setCellValue('F'.$row, $task->sales_name ?: ($task->user ? $task->user->name : '-'));
                $sheet->setCellValue('G'.$row, $task->status_label);
                $sheet->setCellValue('H'.$row, $task->due_date ? $task->due_date->format('d/m/Y') : '-');

                if ($isException) {
                    $sheet->setCellValue('I'.$row, (float) ($task->rp_exception ?? 0));
                    $sheet->setCellValue('J'.$row, $task->comments ?: '-');
                } else {
                    $sheet->setCellValue('I'.$row, $task->comments ?: '-');
                }

                $row++;
            }
        });

        $lastRow = max(1, $row - 1);

        // Auto-width for all columns
        foreach (range('A', $highestColumn) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Auto-filter on header
        $sheet->setAutoFilter('A1:'.$highestColumn.$lastRow);

        // Center deadline column
        if ($lastRow >= 2) {
            $sheet->getStyle('H2:H'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Number format for rpException
        if ($isException && $lastRow >= 2) {
            $sheet->getStyle('I2:I'.$lastRow)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('I2:I'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $writer = new Xlsx($spreadsheet);
        $categorySuffix = $request->input('category') ? '_'.$request->input('category') : '_semua_tugas';
        $filename = 'export_tugas_icon'.$categorySuffix.'_'.date('Ymd_His').'.xlsx';

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Determine if two values are actually different after normalization.
     */
    protected function areValuesDifferent(string $field, mixed $old, mixed $new): bool
    {
        // 1. Check if both are empty (null, empty string, or whitespace-only)
        $isOldEmpty = $old === null || (is_string($old) && trim($old) === '');
        $isNewEmpty = $new === null || (is_string($new) && trim($new) === '');

        if ($isOldEmpty && $isNewEmpty) {
            return false;
        }

        // 2. Numeric / Currency fields (rp_exception, harga_baru)
        if (in_array($field, ['rp_exception', 'harga_baru'], true)) {
            $normalizeNum = function ($val): float {
                if ($val === null || $val === '' || $val === '-' || $val === ' - ') {
                    return 0.0;
                }
                if (is_string($val)) {
                    $clean = str_replace(['Rp', 'rp', '.', ' '], '', trim($val));
                    $clean = str_replace(',', '.', $clean);

                    return is_numeric($clean) ? (float) $clean : 0.0;
                }

                return (float) $val;
            };

            return abs($normalizeNum($old) - $normalizeNum($new)) >= 0.0001;
        }

        // 3. Date fields
        if (in_array($field, ['start_date', 'due_date'], true)) {
            $parseDate = function ($val): ?string {
                if ($val === null || $val === '' || $val === '-') {
                    return null;
                }
                try {
                    return ($val instanceof \DateTimeInterface ? Carbon::instance($val) : Carbon::parse($val))->toDateString();
                } catch (\Throwable) {
                    return is_string($val) ? trim($val) : (string) $val;
                }
            };

            return $parseDate($old) !== $parseDate($new);
        }

        // 4. Datetime fields
        if (in_array($field, ['completed_at', 'confirmed_at', 'reviewed_at', 'comment_updated_at', 'done_marked_at'], true)) {
            $parseDateTime = function ($val): ?string {
                if ($val === null || $val === '' || $val === '-') {
                    return null;
                }
                try {
                    return ($val instanceof \DateTimeInterface ? Carbon::instance($val) : Carbon::parse($val))->format('Y-m-d H:i:s');
                } catch (\Throwable) {
                    return is_string($val) ? trim($val) : (string) $val;
                }
            };

            return $parseDateTime($old) !== $parseDateTime($new);
        }

        // 5. Booleans
        if (is_bool($old) || is_bool($new)) {
            return (bool) $old !== (bool) $new;
        }

        // 6. Case-insensitive fields (kp, status, priority, category, kategori_segmen)
        if (in_array($field, ['kp', 'status', 'priority', 'category', 'kategori_segmen'], true)) {
            return strtolower(trim((string) $old)) !== strtolower(trim((string) $new));
        }

        // 7. Generic string / scalar comparison
        $oldStr = is_string($old) ? trim($old) : (string) $old;
        $newStr = is_string($new) ? trim($new) : (string) $new;

        return $oldStr !== $newStr;
    }

    /**
     * Map column name to Indonesian label.
     */
    protected function formatFieldLabel(string $field): string
    {
        $labels = [
            'user_id' => 'Penerima Tugas',
            'sales_name' => 'Nama Sales',
            'title' => 'Judul Tugas',
            'description' => 'Deskripsi',
            'customer_name' => 'Nama Pelanggan',
            'id_pelanggan' => 'ID Pelanggan',
            'document_number' => 'SID / Dokumen',
            'kp' => 'Kantor Perwakilan (KP)',
            'kategori_segmen' => 'Kategori Segmen',
            'service_type' => 'Jenis Layanan',
            'priority' => 'Prioritas',
            'status' => 'Status',
            'start_date' => 'Tanggal Mulai',
            'due_date' => 'Target Selesai',
            'rp_exception' => 'Nilai Exception',
            'harga_baru' => 'Harga Baru',
            'confirm_to' => 'Konfirmasi Ke',
            'comments' => 'Komentar / Kendala',
            'completed_at' => 'Waktu Selesai',
            'confirmed_at' => 'Waktu Konfirmasi',
            'confirmed_by' => 'Dikonfirmasi Oleh',
            'reviewed_at' => 'Waktu Review',
            'admin_notes' => 'Catatan Admin',
            'submission_notes' => 'Catatan Pengajuan',
            'mutation_id' => 'Klasifikasi Mutasi',
        ];

        return $labels[$field] ?? ucwords(str_replace('_', ' ', $field));
    }

    /**
     * Format field value into human-readable text for activity log details.
     */
    protected function formatFieldValueForLog(string $field, mixed $value, $allUsers): ?string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        if (in_array($field, ['user_id', 'confirmed_by'], true)) {
            $user = $allUsers->firstWhere('id', (int) $value);

            return $user ? $user->name : (string) $value;
        }

        if (in_array($field, ['rp_exception', 'harga_baru'], true)) {
            if ($value === '-' || $value === 0 || $value === '0' || $value === '0.00') {
                return 'Rp 0';
            }
            $clean = is_string($value) ? str_replace(['Rp', 'rp', '.', ' '], '', $value) : $value;
            $clean = is_string($clean) ? str_replace(',', '.', $clean) : $clean;
            $num = is_numeric($clean) ? (float) $clean : 0.0;

            return 'Rp '.number_format($num, 0, ',', '.');
        }

        if (in_array($field, ['start_date', 'due_date'], true)) {
            try {
                return ($value instanceof \DateTimeInterface ? Carbon::instance($value) : Carbon::parse($value))->format('d/m/Y');
            } catch (\Throwable) {
                return (string) $value;
            }
        }

        if (in_array($field, ['completed_at', 'confirmed_at', 'reviewed_at', 'comment_updated_at'], true)) {
            try {
                return ($value instanceof \DateTimeInterface ? Carbon::instance($value) : Carbon::parse($value))->timezone('Asia/Jakarta')->format('d/m/Y H:i');
            } catch (\Throwable) {
                return (string) $value;
            }
        }

        if ($field === 'status') {
            return Task::statuses()[$value] ?? ucfirst((string) $value);
        }

        if ($field === 'priority') {
            return ucfirst((string) $value);
        }

        return trim((string) $value);
    }

    /**
     * Format initial summary representation for newly added tasks.
     */
    protected function formatInitialTaskValue(Task $task): string
    {
        $parts = [];
        if ($task->title) {
            $parts[] = $task->title;
        }
        if ($task->service_type) {
            $parts[] = 'Layanan: '.$task->service_type;
        }
        $statusLabel = Task::statuses()[$task->status] ?? ucfirst((string) $task->status);
        $parts[] = 'Status: '.$statusLabel;
        if ($task->harga_baru > 0) {
            $parts[] = 'Harga: Rp '.number_format((float) $task->harga_baru, 0, ',', '.');
        } elseif ($task->rp_exception > 0) {
            $parts[] = 'Rp Exception: Rp '.number_format((float) $task->rp_exception, 0, ',', '.');
        }

        return implode(' | ', $parts);
    }
}
