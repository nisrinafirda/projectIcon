<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
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
    public function showImport(): View
    {
        $categories = Task::categories();
        $employees = User::where('role', 'user')->orderBy('name')->get();

        return view('tasks.import', compact('categories', 'employees'));
    }

    /**
     * Download sample Excel template for bulk import.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import Tugas ICON');

        // Headers
        $headers = [
            'A1' => 'Kategori (sso_open/baa/bai/exception/kontrak_exp)',
            'B1' => 'Nomor Dokumen (Unik)',
            'C1' => 'Judul Tugas',
            'D1' => 'NIP atau Email Karyawan',
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

        // Sample rows to guide user
        $sampleData = [
            ['baa', 'BAA-2026-901', 'BAA Aktivasi Link Fiber Optik', 'ahmad@icon.co.id', 'PT Global Data', 'Metronet 1Gbps', 'high', '2026-09-15', '2026-09-16', 'Lengkapi BAA dan tanda tangan mitra'],
            ['bai', 'BAI-2026-902', 'BAI Penggantian SFP OLT Surabaya', 'siti@icon.co.id', 'POP Surabaya Gubeng', 'OLT Upgrade', 'urgent', '2026-09-15', '2026-09-16', 'Pemeriksaan port fisik dan redudansi'],
            ['sso_open', 'SSO-2026-903', 'Tiket SSO Flapping Jalur Barat', 'budi@icon.co.id', 'Bank Mandiri', 'Dedicated Internet', 'urgent', '2026-09-15', '2026-09-17', 'Investigasi loss packet'],
            ['exception', 'EXC-2026-904', 'Laporan Exception Pemadaman Gardu', 'ahmad@icon.co.id', 'PLN UID', 'SCADA', 'medium', '2026-09-15', '2026-09-18', 'Klaim SLA force majeure'],
            ['kontrak_exp', 'KTR-2026-905', 'Renewal Sewa Rack Co-Location', 'siti@icon.co.id', 'PT Nusantara Cloud', 'Data Center', 'high', '2026-09-15', '2026-09-22', 'Konfirmasi perpanjangan sewa'],
        ];

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

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, 'template_import_tugas_icon.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Process bulk import of tasks with anti-duplicate logic.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'duplicate_action' => ['required', 'in:skip,update,fail'],
            'default_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $file = $request->file('file');
        $duplicateAction = $request->input('duplicate_action');
        $defaultUserId = $request->input('default_user_id');

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

        // Cache users by email and nip for high-speed matching
        $usersByEmail = User::whereNotNull('email')->get()->keyBy(fn ($u) => strtolower(trim($u->email)));
        $usersByNip = User::whereNotNull('nip')->get()->keyBy(fn ($u) => strtolower(trim($u->nip)));

        $validCategories = array_keys(Task::categories());

        $insertedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errors = [];

        // Skip header row
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $rowNum = $i + 1;

            $rawCategory = strtolower(trim($row[0] ?? ''));
            $docNumber = trim((string) ($row[1] ?? ''));
            $title = trim($row[2] ?? '');
            $userIdentifier = strtolower(trim($row[3] ?? ''));
            $customerName = trim($row[4] ?? '');
            $serviceType = trim($row[5] ?? '');
            $priority = strtolower(trim($row[6] ?? 'medium'));
            $startDate = trim($row[7] ?? '');
            $dueDate = trim($row[8] ?? '');
            $description = trim($row[9] ?? '');

            // Skip completely empty rows
            if (! $docNumber && ! $title && ! $rawCategory) {
                continue;
            }

            // Normalisasi kategori
            $category = match ($rawCategory) {
                'sso', 'sso_open', 'sso open' => Task::CATEGORY_SSO_OPEN,
                'baa' => Task::CATEGORY_BAA,
                'bai' => Task::CATEGORY_BAI,
                'exception', 'exc' => Task::CATEGORY_EXCEPTION,
                'kontrak_exp', 'kontrak exp', 'kontrak' => Task::CATEGORY_KONTRAK_EXP,
                default => null,
            };

            if (! $category || ! in_array($category, $validCategories, true)) {
                $errors[] = "Baris #{$rowNum}: Kategori '{$row[0]}' tidak dikenali.";

                continue;
            }

            if (! $docNumber) {
                $errors[] = "Baris #{$rowNum}: Nomor dokumen wajib diisi.";

                continue;
            }

            if (! $title) {
                $title = 'Tugas '.$category.' - '.$docNumber;
            }

            // Match user
            $targetUserId = null;
            if ($userIdentifier) {
                if (isset($usersByEmail[$userIdentifier])) {
                    $targetUserId = $usersByEmail[$userIdentifier]->id;
                } elseif (isset($usersByNip[$userIdentifier])) {
                    $targetUserId = $usersByNip[$userIdentifier]->id;
                }
            }

            if (! $targetUserId) {
                $targetUserId = $defaultUserId ?: Auth::id();
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
                    $existing->update([
                        'user_id' => $targetUserId,
                        'title' => $title,
                        'description' => $description ?: $existing->description,
                        'customer_name' => $customerName ?: $existing->customer_name,
                        'service_type' => $serviceType ?: $existing->service_type,
                        'priority' => $priority,
                        'start_date' => $parsedStartDate ?: $existing->start_date,
                        'due_date' => $parsedDueDate ?: $existing->due_date,
                    ]);
                    $updatedCount++;

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
                'service_type' => $serviceType,
                'priority' => $priority,
                'status' => Task::STATUS_PENDING,
                'start_date' => $parsedStartDate,
                'due_date' => $parsedDueDate,
            ]);

            $insertedCount++;
        }

        $feedback = "Import selesai! Berhasil menambahkan {$insertedCount} tugas baru.";
        if ($updatedCount > 0) {
            $feedback .= " Diperbarui: {$updatedCount} tugas.";
        }
        if ($skippedCount > 0) {
            $feedback .= " Dilewati (duplikat): {$skippedCount} tugas.";
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
