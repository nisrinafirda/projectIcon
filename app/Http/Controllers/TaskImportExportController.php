<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Services\SbuSheetMapper;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
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
        $validCategories = array_keys(Task::categories());

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'duplicate_action' => ['required', 'in:skip,update,fail'],
            'default_user_id' => ['nullable', 'exists:users,id'],
            'target_category' => ['nullable', 'string', 'in:'.implode(',', $validCategories)],
        ]);

        $file = $request->file('file');
        $duplicateAction = $request->input('duplicate_action');
        $defaultUserId = $request->input('default_user_id');
        $targetCategory = $request->input('target_category');

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Gagal membaca file Excel/CSV: '.$e->getMessage()]);
        }

        if (count($rows) <= 1) {
            return back()->withErrors(['file' => 'File kosong atau hanya berisi baris judul header.']);
        }

        $sbuMapper = new SbuSheetMapper;
        $isSbuFormat = $sbuMapper->detectFormat($rows[0]) === 'sbu';
        $columnMap = $isSbuFormat ? $sbuMapper->buildColumnMap($rows[0]) : [];

        // Auto-detect category from sheet title if target_category is not explicitly provided
        if (! $targetCategory && $isSbuFormat) {
            $sheetTitle = strtolower($sheet->getTitle());
            if (str_contains($sheetTitle, 'baa')) {
                $targetCategory = Task::CATEGORY_BAA;
            } elseif (str_contains($sheetTitle, 'bai')) {
                $targetCategory = Task::CATEGORY_BAI;
            } elseif (str_contains($sheetTitle, 'exception') || str_contains($sheetTitle, 'exc')) {
                $targetCategory = Task::CATEGORY_EXCEPTION;
            } elseif (str_contains($sheetTitle, 'contract') || str_contains($sheetTitle, 'kontrak')) {
                $targetCategory = Task::CATEGORY_KONTRAK_EXP;
            } elseif (str_contains($sheetTitle, 'so open') || str_contains($sheetTitle, 'sso')) {
                $targetCategory = Task::CATEGORY_SSO_OPEN;
            }
        }

        if ($isSbuFormat && ! $targetCategory) {
            return back()->withErrors(['file' => 'Format file Project Monitoring (SBU) terdeteksi. Silakan pilih Target Modul Kategori Tugas di form sebelum mengimpor file ini.']);
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
        $skippedCount = 0;
        $errors = [];
        $distribution = [];

        // Skip header row
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $rowNum = $i + 1;

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
                $status = $mapped['status'];
                $parsedStartDate = $mapped['start_date'];
                $parsedDueDate = $mapped['due_date'];
                $userIdentifier = $mapped['sales_identifier'];
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

                // Skip completely empty rows
                if (! $docNumber && ! $title && ! $rawCategory && ! $userIdentifier) {
                    continue;
                }

                // Category resolution: use selected target_category if specified, otherwise parse from row
                if ($targetCategory) {
                    $category = $targetCategory;
                } else {
                    $category = match ($rawCategory) {
                        'sso', 'sso_open', 'sso open' => Task::CATEGORY_SSO_OPEN,
                        'baa' => Task::CATEGORY_BAA,
                        'bai' => Task::CATEGORY_BAI,
                        'exception', 'exc' => Task::CATEGORY_EXCEPTION,
                        'kontrak_exp', 'kontrak exp', 'kontrak' => Task::CATEGORY_KONTRAK_EXP,
                        default => null,
                    };
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
                    ];
                    if ($kp) {
                        $updatePayload['kp'] = $kp;
                    }
                    if ($kategoriSegmen) {
                        $updatePayload['kategori_segmen'] = $kategoriSegmen;
                    }
                    if ($isSbuFormat && $status) {
                        $updatePayload['status'] = $status;
                    }

                    $existing->update($updatePayload);
                    $updatedCount++;
                    $distribution[$targetUserName] = ($distribution[$targetUserName] ?? 0) + 1;

                    continue;
                }
            }

            // Insert new task
            Task::create([
                'user_id' => $targetUserId,
                'assigned_by' => Auth::id(),
                'category' => $category,
                'document_number' => $docNumber,
                'title' => $title,
                'description' => $description,
                'customer_name' => $customerName,
                'kp' => $kp,
                'kategori_segmen' => $kategoriSegmen,
                'service_type' => $serviceType,
                'priority' => $priority,
                'status' => $status,
                'start_date' => $parsedStartDate,
                'due_date' => $parsedDueDate,
            ]);

            $insertedCount++;
            $distribution[$targetUserName] = ($distribution[$targetUserName] ?? 0) + 1;
        }

        $feedback = ($isSbuFormat ? 'File Monitoring SBU terdeteksi. ' : '')."Import data berhasil! Total {$insertedCount} tugas baru ditambahkan";
        if ($updatedCount > 0) {
            $feedback .= ", {$updatedCount} diperbarui";
        }
        if ($skippedCount > 0) {
            $feedback .= ", {$skippedCount} dilewati (duplikat)";
        }
        $feedback .= '.';

        if (! empty($distribution)) {
            $distList = [];
            foreach ($distribution as $uName => $count) {
                $distList[] = "{$uName} ({$count} tugas)";
            }
            $feedback .= ' Terdistribusi otomatis ke: '.implode(', ', $distList).'.';
        }

        if ($targetCategory) {
            return redirect()->route('tasks.category', $targetCategory)
                ->with('success', $feedback)
                ->with('import_errors', $errors);
        }

        return redirect()->route('tasks.index')
            ->with('success', $feedback)
            ->with('import_errors', $errors);
    }

    /**
     * Export tasks to Excel (.xlsx).
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $query = Task::with(['user', 'assigner']);

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $tasks = $query->orderBy('due_date', 'asc')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Tugas ICON');

        // Header
        $headers = [
            'A1' => 'No',
            'B1' => 'Kategori',
            'C1' => 'No Dokumen',
            'D1' => 'Judul Tugas',
            'E1' => 'Karyawan',
            'F1' => 'Pelanggan / Site',
            'G1' => 'Layanan',
            'H1' => 'Prioritas',
            'I1' => 'Status',
            'J1' => 'Deadline',
            'K1' => 'Selesai Pada',
            'L1' => 'Catatan Admin',
        ];

        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }

        // Header Styling
        $sheet->getStyle('A1:L1')->applyFromArray([
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
        $sheet->getRowDimension(1)->setRowHeight(26);

        $row = 2;
        $num = 1;
        foreach ($tasks as $task) {
            $sheet->setCellValue('A'.$row, $num++);
            $sheet->setCellValue('B'.$row, $task->category_label);
            $sheet->setCellValue('C'.$row, $task->document_number);
            $sheet->setCellValue('D'.$row, $task->title);
            $sheet->setCellValue('E'.$row, $task->user ? $task->user->name : '-');
            $sheet->setCellValue('F'.$row, $task->customer_name ?: '-');
            $sheet->setCellValue('G'.$row, $task->service_type ?: '-');
            $sheet->setCellValue('H'.$row, ucfirst($task->priority));
            $sheet->setCellValue('I'.$row, $task->status_label);
            $sheet->setCellValue('J'.$row, $task->due_date ? $task->due_date->format('d/m/Y') : '-');
            $sheet->setCellValue('K'.$row, $task->completed_at ? $task->completed_at->format('d/m/Y H:i') : '-');
            $sheet->setCellValue('L'.$row, $task->admin_notes ?: '-');
            $row++;
        }

        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'rekap_tugas_icon_'.date('Ymd_His').'.xlsx';

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export tasks to PDF with branded layout.
     */
    public function exportPdf(Request $request): Response
    {
        $user = Auth::user();
        $query = Task::with(['user', 'assigner']);

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        $category = $request->input('category');
        if ($category) {
            $query->where('category', $category);
        }

        $status = $request->input('status');
        if ($status) {
            $query->where('status', $status);
        }

        $tasks = $query->orderBy('due_date', 'asc')->get();

        $pdf = Pdf::loadView('reports.tasks_pdf', [
            'tasks' => $tasks,
            'category' => $category,
            'status' => $status,
            'user' => $user,
            'generatedAt' => now()->format('d F Y H:i'),
        ])->setPaper('a4', 'landscape');

        $filename = 'laporan_monitoring_tugas_icon_'.date('Ymd').'.pdf';

        return $pdf->download($filename);
    }
}
