<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Services\SbuSheetMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class TaskImportCategoryGuardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@icon.co.id',
            'role' => 'admin',
        ]);

        $this->user = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@icon.co.id',
            'role' => 'user',
        ]);
    }

    /**
     * Create a temporary XLSX file for testing.
     *
     * @param  array<string, list<list<mixed>>>  $sheets  sheetName => rows
     */
    protected function createExcelFile(string $filename, array $sheets): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheetIndex = 0;

        foreach ($sheets as $sheetTitle => $rows) {
            $sheet = $sheetIndex === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $sheet->setTitle($sheetTitle);
            $sheet->fromArray($rows);

            $sheetIndex++;
        }

        $tempPath = @tempnam(sys_get_temp_dir(), 'test_excel_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return new UploadedFile($tempPath, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_baa_file_uploaded_to_bai_module_is_strictly_rejected_with_no_inserts(): void
    {
        $initialTaskCount = Task::count();

        // SBU format with BAA headers
        $csv = "ID PA,NAMA PELANGGAN,LAYANAN PRODUK,HARGA LAMA,HARGA BARU,SELISIH,Kategori Customer,KP,Sales,Tanggal Upload BAI,Aging BAA (Hari),Target,Tanggal Upload BAA,Kendala,Support,Status\n";
        $csv .= "U141303003888,PT SEACON,INTERNET,9000000,964516,964516,PUBLIK,Surabaya,Budi Santoso,8/7/2026,6,,8/13/2026,,,Done\n";

        $file = UploadedFile::fake()->createWithContent('baa_open.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'bai',
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertEquals($initialTaskCount, Task::count(), 'Tidak boleh ada baris baru yang di-insert saat file BAA ditolak di modul BAI.');

        $errors = session('errors')->get('file');
        $this->assertStringContainsString('BAA', $errors[0]);
        $this->assertStringContainsString('BAI', $errors[0]);
    }

    public function test_bai_file_uploaded_to_bai_module_is_successful(): void
    {
        $initialBaiCount = Task::where('category', 'bai')->count();

        // SBU format with BAI headers
        $csv = "ID PA,NAMA PELANGGAN,LAYANAN PRODUK,HARGA LAMA,HARGA BARU,SELISIH,Kategori Customer,KP,Sales,Target BAI,Tanggal Upload,Status\n";
        $csv .= "A141401005310,SMK DARUL ULUM,METRONET,0,1693548,1693548,PUBLIK,Surabaya,Budi Santoso,7,9/27/2026,Done\n";

        $file = UploadedFile::fake()->createWithContent('bai_open.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'bai',
        ]);

        $response->assertRedirect(route('tasks.category', 'bai'));
        $response->assertSessionHas('success');

        $this->assertEquals($initialBaiCount + 1, Task::where('category', 'bai')->count());
        $task = Task::where('document_number', 'A141401005310')->first();
        $this->assertNotNull($task);
        $this->assertEquals('bai', $task->category);
    }

    public function test_baa_file_uploaded_to_baa_module_is_successful(): void
    {
        $initialBaaCount = Task::where('category', 'baa')->count();

        $csv = "ID PA,NAMA PELANGGAN,LAYANAN PRODUK,HARGA LAMA,HARGA BARU,SELISIH,Kategori Customer,KP,Sales,Tanggal Upload BAI,Aging BAA (Hari),Target,Tanggal Upload BAA,Kendala,Support,Status\n";
        $csv .= "U141303003888,PT SEACON,INTERNET,9000000,964516,964516,PUBLIK,Surabaya,Budi Santoso,8/7/2026,6,,8/13/2026,,,Done\n";

        $file = UploadedFile::fake()->createWithContent('baa_open.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'baa',
        ]);

        $response->assertRedirect(route('tasks.category', 'baa'));
        $response->assertSessionHas('success');

        $this->assertEquals($initialBaaCount + 1, Task::where('category', 'baa')->count());
        $task = Task::where('document_number', 'U141303003888')->first();
        $this->assertNotNull($task);
        $this->assertEquals('baa', $task->category);
    }

    public function test_generic_sheet_named_sheet1_with_baa_content_is_rejected_in_bai_module(): void
    {
        $initialTaskCount = Task::count();

        $file = $this->createExcelFile('test_sheet1_baa.xlsx', [
            'Sheet1' => [
                ['ID PA', 'NAMA PELANGGAN', 'LAYANAN PRODUK', 'HARGA LAMA', 'HARGA BARU', 'SELISIH', 'Kategori Customer', 'KP', 'Sales', 'Tanggal Upload BAI', 'Aging BAA (Hari)', 'Target', 'Tanggal Upload BAA', 'Status'],
                ['U141303003999', 'PT ANGKASA', 'METRONET', '0', '1000000', '1000000', 'PUBLIK', 'Surabaya', 'Budi Santoso', '8/7/2026', '5', '7', '8/12/2026', 'Done'],
            ],
        ]);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'bai',
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertEquals($initialTaskCount, Task::count(), 'Sheet1 berisi konten BAA harus ditolak di modul BAI tanpa ada baris baru.');
        $errors = session('errors')->get('file');
        $this->assertStringContainsString('BAA', $errors[0]);
    }

    public function test_generic_sheet_named_sheet1_with_bai_content_is_accepted_in_bai_module(): void
    {
        $initialBaiCount = Task::where('category', 'bai')->count();

        $file = $this->createExcelFile('test_sheet1_bai.xlsx', [
            'Sheet1' => [
                ['ID PA', 'NAMA PELANGGAN', 'LAYANAN PRODUK', 'HARGA LAMA', 'HARGA BARU', 'SELISIH', 'Kategori Customer', 'KP', 'Sales', 'Target BAI', 'Tanggal Upload', 'Status'],
                ['A141401009999', 'PT SURYA CEMERLANG', 'METRONET', '0', '2000000', '2000000', 'PUBLIK', 'Surabaya', 'Budi Santoso', '7', '9/27/2026', 'Done'],
            ],
        ]);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'bai',
        ]);

        $response->assertRedirect(route('tasks.category', 'bai'));
        $response->assertSessionHas('success');
        $this->assertEquals($initialBaiCount + 1, Task::where('category', 'bai')->count());
    }

    public function test_icon_format_with_empty_category_column_and_baa_doc_number_is_rejected_in_bai_module(): void
    {
        $initialTaskCount = Task::count();

        $csv = "Kategori,Nomor Dokumen,Judul Tugas,PIC,Pelanggan,Layanan,Prioritas,Mulai,Deadline,Keterangan\n";
        $csv .= ",BAA-2026-888,Tugas Aktivasi Berita Acara,Budi Santoso,PT Maju,Metronet,high,2026-09-17,2026-09-20,Catatan\n";

        $file = UploadedFile::fake()->createWithContent('icon_empty_cat_baa_doc.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'bai',
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertEquals($initialTaskCount, Task::count(), 'Kolom Kategori kosong dengan nomor dokumen BAA harus ditolak di modul BAI.');
        $errors = session('errors')->get('file');
        $this->assertStringContainsString('BAA', $errors[0]);
    }

    public function test_icon_format_with_empty_category_column_and_bai_doc_number_is_accepted_in_bai_module(): void
    {
        $initialBaiCount = Task::where('category', 'bai')->count();

        $csv = "Kategori,Nomor Dokumen,Judul Tugas,PIC,Pelanggan,Layanan,Prioritas,Mulai,Deadline,Keterangan\n";
        $csv .= ",BAI-2026-777,Tugas Instalasi Berita Acara,Budi Santoso,PT Sukses,Metronet,high,2026-09-17,2026-09-20,Catatan\n";

        $file = UploadedFile::fake()->createWithContent('icon_empty_cat_bai_doc.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'bai',
        ]);

        $response->assertRedirect(route('tasks.category', 'bai'));
        $response->assertSessionHas('success');

        $this->assertEquals($initialBaiCount + 1, Task::where('category', 'bai')->count());
        $task = Task::where('document_number', 'BAI-2026-777')->first();
        $this->assertNotNull($task);
        $this->assertEquals('bai', $task->category);
    }

    public function test_icon_format_with_empty_category_column_and_neutral_doc_number_follows_target_module(): void
    {
        $initialBaiCount = Task::where('category', 'bai')->count();

        $csv = "Kategori,Nomor Dokumen,Judul Tugas,PIC,Pelanggan,Layanan,Prioritas,Mulai,Deadline,Keterangan\n";
        $csv .= ",DOC-NEUTRAL-999,Tugas Dokumen Netral,Budi Santoso,PT Netral,Metronet,high,2026-09-17,2026-09-20,Catatan\n";

        $file = UploadedFile::fake()->createWithContent('icon_empty_cat_neutral_doc.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'bai',
        ]);

        $response->assertRedirect(route('tasks.category', 'bai'));
        $response->assertSessionHas('success');

        $this->assertEquals($initialBaiCount + 1, Task::where('category', 'bai')->count());
        $task = Task::where('document_number', 'DOC-NEUTRAL-999')->first();
        $this->assertNotNull($task);
        $this->assertEquals('bai', $task->category);
    }

    public function test_multi_sheet_file_selects_sheet_matching_target_module(): void
    {
        $initialBaiCount = Task::where('category', 'bai')->count();

        // Workbook containing both BAA and BAI sheets; target BAI should pick the BAI sheet
        $file = $this->createExcelFile('multi_sheets.xlsx', [
            'Cover' => [
                ['Laporan Monitoring', 'Tahun 2026'],
            ],
            '3 BAA Open' => [
                ['ID PA', 'NAMA PELANGGAN', 'LAYANAN PRODUK', 'HARGA LAMA', 'HARGA BARU', 'SELISIH', 'Kategori Customer', 'KP', 'Sales', 'Tanggal Upload BAI', 'Aging BAA (Hari)', 'Target', 'Tanggal Upload BAA', 'Status'],
                ['BAA-ROW-001', 'PT BAA', 'INTERNET', '0', '1000', '1000', 'PUBLIK', 'Surabaya', 'Budi Santoso', '8/7/2026', '5', '7', '8/12/2026', 'Done'],
            ],
            '2 BAI Open' => [
                ['ID PA', 'NAMA PELANGGAN', 'LAYANAN PRODUK', 'HARGA LAMA', 'HARGA BARU', 'SELISIH', 'Kategori Customer', 'KP', 'Sales', 'Target BAI', 'Tanggal Upload', 'Status'],
                ['BAI-ROW-002', 'PT BAI', 'METRONET', '0', '2000', '2000', 'PUBLIK', 'Surabaya', 'Budi Santoso', '7', '9/27/2026', 'Done'],
            ],
        ]);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'bai',
        ]);

        $response->assertRedirect(route('tasks.category', 'bai'));
        $response->assertSessionHas('success');

        $this->assertEquals($initialBaiCount + 1, Task::where('category', 'bai')->count());
        $this->assertNotNull(Task::where('document_number', 'BAI-ROW-002')->first());
        $this->assertNull(Task::where('document_number', 'BAA-ROW-001')->first());
    }

    public function test_multi_sheet_file_rejects_when_sheets_exist_for_other_categories_but_not_target(): void
    {
        $initialTaskCount = Task::count();

        // Workbook only has BAA sheet, but target is BAI
        $file = $this->createExcelFile('only_baa_sheet.xlsx', [
            'Cover' => [
                ['Laporan Monitoring', 'Tahun 2026'],
            ],
            '3 BAA Open' => [
                ['ID PA', 'NAMA PELANGGAN', 'LAYANAN PRODUK', 'HARGA LAMA', 'HARGA BARU', 'SELISIH', 'Kategori Customer', 'KP', 'Sales', 'Tanggal Upload BAI', 'Aging BAA (Hari)', 'Target', 'Tanggal Upload BAA', 'Status'],
                ['BAA-ROW-999', 'PT BAA', 'INTERNET', '0', '1000', '1000', 'PUBLIK', 'Surabaya', 'Budi Santoso', '8/7/2026', '5', '7', '8/12/2026', 'Done'],
            ],
        ]);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'bai',
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertEquals($initialTaskCount, Task::count());

        $errors = session('errors')->get('file');
        $this->assertStringContainsString('3 BAA Open', $errors[0]);
        $this->assertStringContainsString('BAA', $errors[0]);
        $this->assertStringContainsString('BAI', $errors[0]);
    }

    public function test_import_from_module_requires_target_category_without_guessing(): void
    {
        $file = UploadedFile::fake()->createWithContent('dummy.xlsx', "Col1,Col2\nVal1,Val2");

        // Request with source_category (from module BAI) but missing target_category
        $response = $this->actingAs($this->admin)->post(route('tasks.import.process', ['category' => 'bai']), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'source_category' => 'bai',
            'target_category' => '',
        ]);

        $response->assertSessionHasErrors('target_category');
        $errors = session('errors')->get('target_category');
        $this->assertStringContainsString('BAI', $errors[0]);
    }

    public function test_real_sample_baa_csv_file_in_bai_module_is_rejected_and_in_baa_module_is_accepted(): void
    {
        $path = storage_path('app/imports/BAA Open.csv');
        $this->assertFileExists($path);

        $initialTaskCount = Task::count();

        // 1. Upload to BAI module -> MUST BE REJECTED
        $file = new UploadedFile($path, 'BAA Open.csv', 'text/csv', null, true);
        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'bai',
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertEquals($initialTaskCount, Task::count(), 'Tidak boleh ada baris baru yang di-insert saat file BAA Open.csv diimpor ke modul BAI.');

        // 2. Upload to BAA module -> MUST SUCCEED
        $fileForBaa = new UploadedFile($path, 'BAA Open.csv', 'text/csv', null, true);
        $response2 = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $fileForBaa,
            'duplicate_action' => 'skip',
            'target_category' => 'baa',
        ]);

        $response2->assertRedirect(route('tasks.category', 'baa'));
        $response2->assertSessionHas('success');
        $this->assertGreaterThan($initialTaskCount, Task::where('category', 'baa')->count());
    }

    public function test_real_sample_bai_csv_file_in_bai_module_is_accepted_and_in_baa_module_is_rejected(): void
    {
        $path = storage_path('app/imports/BAI Open.csv');
        $this->assertFileExists($path);

        $initialTaskCount = Task::count();

        // 1. Upload to BAA module -> MUST BE REJECTED
        $fileForBaa = new UploadedFile($path, 'BAI Open.csv', 'text/csv', null, true);
        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $fileForBaa,
            'duplicate_action' => 'skip',
            'target_category' => 'baa',
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertEquals($initialTaskCount, Task::count(), 'Tidak boleh ada baris baru saat file BAI Open.csv diimpor ke modul BAA.');

        // 2. Upload to BAI module -> MUST SUCCEED
        $fileForBai = new UploadedFile($path, 'BAI Open.csv', 'text/csv', null, true);
        $response2 = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $fileForBai,
            'duplicate_action' => 'skip',
            'target_category' => 'bai',
        ]);

        $response2->assertRedirect(route('tasks.category', 'bai'));
        $response2->assertSessionHas('success');
        $this->assertGreaterThan($initialTaskCount, Task::where('category', 'bai')->count());
    }

    public function test_real_sample_so_open_pdl_csv_is_accepted_in_so_open_module(): void
    {
        $path = storage_path('app/imports/SO Open PDL.csv');
        $this->assertFileExists($path);

        $initialSoCount = Task::where('category', 'sso_open')->count();

        $file = new UploadedFile($path, 'SO Open PDL.csv', 'text/csv', null, true);
        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'sso_open',
        ]);

        $response->assertRedirect(route('tasks.category', 'sso_open'));
        $response->assertSessionHas('success');
        $this->assertGreaterThan($initialSoCount, Task::where('category', 'sso_open')->count());

        $firstPdl = Task::where('category', 'sso_open')->where('document_number', 'like', 'A%')->first();
        $this->assertNotNull($firstPdl);
        $this->assertEquals('PDL', $firstPdl->so_type);
        $this->assertStringContainsString('[PDL]', $firstPdl->title);
    }

    public function test_real_sample_so_open_ppb_csv_is_accepted_in_so_open_module(): void
    {
        $path = storage_path('app/imports/SO Open PPB.csv');
        $this->assertFileExists($path);

        $initialSoCount = Task::where('category', 'sso_open')->count();

        $file = new UploadedFile($path, 'SO Open PPB.csv', 'text/csv', null, true);
        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'sso_open',
        ]);

        $response->assertRedirect(route('tasks.category', 'sso_open'));
        $response->assertSessionHas('success');
        $this->assertGreaterThan($initialSoCount, Task::where('category', 'sso_open')->count());

        $firstPpb = Task::where('category', 'sso_open')->where('document_number', 'like', 'B%')->first();
        $this->assertNotNull($firstPpb);
        $this->assertEquals('PPB', $firstPpb->so_type);
        $this->assertStringContainsString('[PPB]', $firstPpb->title);
    }

    public function test_sbu_import_maps_done_status_to_approved_and_on_process_to_in_progress(): void
    {
        $csv = "ID PA,NAMA PELANGGAN,LAYANAN PRODUK,HARGA LAMA,HARGA BARU,SELISIH,Kategori Customer,KP,Sales,Tanggal Upload BAI,Aging BAA (Hari),Target,Tanggal Upload BAA,Kendala,Support,Status\n"
            ."PA-BAA-DONE,PT Pelanggan Selesai,INTERNET,0,1000000,1000000,PLN,Surabaya,Budi Santoso,10/1/2026,10,10/8/2026,10/2/2026,,,Done\n"
            ."PA-BAA-PROGRESS,PT Pelanggan Proses,METRONET,0,2000000,2000000,PUBLIK,Malang,Budi Santoso,10/1/2026,10,10/8/2026,,,,On Process\n";

        $file = UploadedFile::fake()->createWithContent('Project Monitoring PPB 2026(3 BAA Open).csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'baa',
        ]);

        $response->assertRedirect(route('tasks.category', 'baa'));
        $response->assertSessionHas('success');

        $doneTask = Task::where('category', 'baa')->where('document_number', 'PA-BAA-DONE')->first();
        $this->assertNotNull($doneTask);
        $this->assertEquals(Task::STATUS_APPROVED, $doneTask->status);
        $this->assertNotNull($doneTask->completed_at);
        $this->assertNotNull($doneTask->confirmed_at);

        $progressTask = Task::where('category', 'baa')->where('document_number', 'PA-BAA-PROGRESS')->first();
        $this->assertNotNull($progressTask);
        $this->assertEquals(Task::STATUS_IN_PROGRESS, $progressTask->status);
    }

    public function test_sbu_import_non_baa_categories_remain_pending_even_with_done_status(): void
    {
        $csv = "ID PA,NAMA PELANGGAN,LAYANAN PRODUK,HARGA LAMA,HARGA BARU,SELISIH,Kategori Customer,KP,Sales,Target BAI,Tanggal Upload,Status\n"
            ."PA-BAI-DONE,PT Pelanggan BAI,INTERNET,0,1000000,1000000,PLN,Surabaya,Budi Santoso,8,10/1/2026,Done\n";

        $file = UploadedFile::fake()->createWithContent('BAI Open.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'bai',
        ]);

        $response->assertRedirect(route('tasks.category', 'bai'));
        $response->assertSessionHas('success');

        $baiTask = Task::where('category', 'bai')->where('document_number', 'PA-BAI-DONE')->first();
        $this->assertNotNull($baiTask);
        // Non-BAA category remains pending as per user rule
        $this->assertEquals(Task::STATUS_PENDING, $baiTask->status);
        $this->assertNull($baiTask->confirmed_at);
    }

    public function test_xlsx_import_for_baa_matches_csv_with_done_on_process_and_empty_status(): void
    {
        $file = $this->createExcelFile('Project Monitoring PPB 2026 (BAA Open).xlsx', [
            '3 BAA Open' => [
                // Title on row 0 to test automatic header row finding
                ['PROJECT MONITORING PPB 2026', 'UNIT PELAKSANA', 'BAA OPEN'],
                // Header on row 1 with newline inside cells
                [
                    'ID PA',
                    'NAMA PELANGGAN',
                    'LAYANAN PRODUK',
                    'HARGA LAMA',
                    'HARGA BARU',
                    'SELISIH',
                    'Kategori Customer',
                    'KP',
                    'Sales',
                    "Tanggal Upload BAI\n(MM/DD/YYYY)",
                    'Aging BAA (Hari)',
                    'Target',
                    "Tanggal Upload BAA\n(MM/DD/YYYY)",
                    'Kendala',
                    'Support',
                    'Status',
                ],
                // Row 1: Done with serial date (46290 = 2026-09-25, 46297 = 2026-10-02) and numeric ID PA
                [
                    '142402003336',
                    'PT PLN PERSERO UP3 JEMBER',
                    'ONE TIME SERVICE - OTC',
                    0,
                    41100000,
                    41100000,
                    'PLN',
                    'Jember',
                    'Budi Santoso',
                    46290,
                    21.4,
                    46297,
                    46297,
                    '',
                    '',
                    'Done',
                ],
                // Row 2: On Process with date string (10/1/2026, 10/8/2026) and comma string price
                [
                    'A141401005473',
                    'PT CENTRAL PROTEINA TBK',
                    'INTERNET BROADBAND',
                    '0',
                    '4,500,000',
                    '4,500,000',
                    'PUBLIK',
                    'Surabaya',
                    'Budi Santoso',
                    '10/1/2026',
                    15.4,
                    '10/8/2026',
                    '',
                    'Kendala teknis di lapangan',
                    '',
                    'On Process',
                ],
                // Row 3: Empty status row (should default to in_progress / Sedang Dikerjakan)
                [
                    'A142204001689',
                    'PT PLN UP3 PONOROGO',
                    'PRODUK DIGITAL',
                    '-',
                    '4500000',
                    '4500000',
                    'PLN',
                    'Madiun',
                    'Budi Santoso',
                    '10/1/2026',
                    15.4,
                    '10/8/2026',
                    '',
                    '',
                    '',
                    '',
                ],
                // Empty row to test empty row skipping
                ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ],
        ]);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process', ['category' => 'baa']), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'baa',
            'source_category' => 'baa',
        ]);

        $response->assertRedirect(route('tasks.category', 'baa'));
        $response->assertSessionHas('success');

        // Check Row 1: Done
        $task1 = Task::where('category', 'baa')->where('document_number', '142402003336')->first();
        $this->assertNotNull($task1);
        $this->assertEquals(Task::STATUS_APPROVED, $task1->status);
        $this->assertNotNull($task1->completed_at);
        $this->assertNotNull($task1->confirmed_at);
        $this->assertEquals('2026-09-25', $task1->start_date?->format('Y-m-d'));
        $this->assertEquals('2026-10-02', $task1->due_date?->format('Y-m-d'));
        $this->assertEquals(41100000, (float) $task1->harga_baru);

        // Check Row 2: On Process
        $task2 = Task::where('category', 'baa')->where('document_number', 'A141401005473')->first();
        $this->assertNotNull($task2);
        $this->assertEquals(Task::STATUS_IN_PROGRESS, $task2->status);
        $this->assertEquals('2026-10-01', $task2->start_date?->format('Y-m-d'));
        $this->assertEquals('2026-10-08', $task2->due_date?->format('Y-m-d'));
        $this->assertEquals(4500000, (float) $task2->harga_baru);

        // Check Row 3: Empty status
        $task3 = Task::where('category', 'baa')->where('document_number', 'A142204001689')->first();
        $this->assertNotNull($task3);
        $this->assertEquals(Task::STATUS_IN_PROGRESS, $task3->status);
        $this->assertEquals(4500000, (float) $task3->harga_baru);
    }

    public function test_baa_import_rejects_with_specific_error_when_id_pa_missing(): void
    {
        $file = $this->createExcelFile('invalid_sheet.xlsx', [
            'Sheet1' => [
                ['Nama', 'Alamat', 'Status'],
                ['Budi', 'Surabaya', 'Done'],
            ],
        ]);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process', ['category' => 'baa']), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'baa',
            'source_category' => 'baa',
        ]);

        $response->assertSessionHasErrors('file');
        $error = session('errors')->first('file');
        $this->assertStringContainsString('ID PA', $error);
    }

    public function test_csv_and_txt_files_with_plain_mime_are_accepted_by_validation(): void
    {
        $csvContent = "ID PA,NAMA PELANGGAN,LAYANAN PRODUK,HARGA LAMA,HARGA BARU,SELISIH,Kategori Customer,KP,Sales,Tanggal Upload BAI,Aging BAA (Hari),Target,Tanggal Upload BAA,Kendala,Support,Status\n"
            ."PA-BAA-PLAIN,PT Pelanggan Plain,INTERNET,0,1000000,1000000,PLN,Surabaya,Budi Santoso,10/1/2026,10,10/8/2026,10/2/2026,,,Done\n";

        // Uploaded file with text/plain mime
        $file = UploadedFile::fake()->createWithContent('data.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process', ['category' => 'baa']), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'baa',
            'source_category' => 'baa',
        ]);

        $response->assertRedirect(route('tasks.category', 'baa'));
        $response->assertSessionHas('success');
        $this->assertNotNull(Task::where('category', 'baa')->where('document_number', 'PA-BAA-PLAIN')->first());
    }

    public function test_normalize_segmen_detects_pln_from_customer_name_when_column_is_empty(): void
    {
        $csvContent = "ID PA,NAMA PELANGGAN,LAYANAN PRODUK,HARGA LAMA,HARGA BARU,SELISIH,Kategori Customer,KP,Sales,Tanggal Upload BAI,Aging BAA (Hari),Target,Tanggal Upload BAA,Kendala,Support,Status\n"
            ."PA-PLN-01,PT. PLN (PERSERO) ULP TUBAN,INTERNET,0,1000000,1000000,,Surabaya,Budi Santoso,10/1/2026,10,10/8/2026,10/2/2026,,,Done\n"
            ."PA-PUB-01,DINAS KESEHATAN KABUPATEN TUBAN,INTERNET,0,2000000,2000000,,Surabaya,Budi Santoso,10/1/2026,10,10/8/2026,10/2/2026,,,Done\n";

        $file = UploadedFile::fake()->createWithContent('data.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process', ['category' => 'baa']), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'baa',
            'source_category' => 'baa',
        ]);

        $response->assertRedirect(route('tasks.category', 'baa'));
        $response->assertSessionHas('success');

        $plnTask = Task::where('category', 'baa')->where('document_number', 'PA-PLN-01')->first();
        $this->assertNotNull($plnTask);
        $this->assertSame(Task::SEGMEN_PLN, $plnTask->kategori_segmen);

        $pubTask = Task::where('category', 'baa')->where('document_number', 'PA-PUB-01')->first();
        $this->assertNotNull($pubTask);
        $this->assertSame(Task::SEGMEN_PUBLIK, $pubTask->kategori_segmen);
    }

    public function test_exception_import_maps_exception_open_to_in_progress_and_close_to_approved(): void
    {
        $csv = "NO,ID Pelanggan,Nama Pelanggan,Segmen,KP,Sales,Nama Layanan,BANDWIDTH,RP EXCEPTION,STATUS\n"
            ."1,EXC-001,PT Alpha Open,PUBLIK,Surabaya,Budi Santoso,METRONET,100 Mbps,5000000,EXCEPTION OPEN\n"
            ."2,EXC-002,PT Beta Close,PLN,Malang,Budi Santoso,ASTINET,50 Mbps,7500000,CLOSE\n"
            ."3,EXC-003,PT Gamma Closed,PUBLIK,Madiun,Budi Santoso,IP VPN,20 Mbps,2000000,CLOSED\n"
            ."4,EXC-004,PT Delta Mixed,PUBLIK,Jember,Budi Santoso,INTERNET,10 Mbps,1000000,  Exception   Open  \n";

        $file = UploadedFile::fake()->createWithContent('Exception.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'exception',
        ]);

        $response->assertRedirect(route('tasks.category', 'exception'));
        $response->assertSessionHas('success');

        $openTask = Task::where('category', 'exception')->where('document_number', 'EXC-001')->first();
        $this->assertNotNull($openTask);
        $this->assertSame(Task::STATUS_IN_PROGRESS, $openTask->status);
        $this->assertNull($openTask->confirmed_at);
        $this->assertNull($openTask->completed_at);
        $this->assertNull($openTask->confirmed_by);
        $this->assertNull($openTask->reviewed_at);

        $closeTask = Task::where('category', 'exception')->where('document_number', 'EXC-002')->first();
        $this->assertNotNull($closeTask);
        $this->assertSame(Task::STATUS_APPROVED, $closeTask->status);
        $this->assertNotNull($closeTask->completed_at);
        $this->assertNotNull($closeTask->confirmed_at);
        $this->assertSame($this->admin->id, $closeTask->confirmed_by);
        $this->assertNotNull($closeTask->reviewed_at);
        $this->assertSame('Disetujui otomatis dari status import (CLOSE)', $closeTask->admin_notes);

        $closedTask = Task::where('category', 'exception')->where('document_number', 'EXC-003')->first();
        $this->assertNotNull($closedTask);
        $this->assertSame(Task::STATUS_APPROVED, $closedTask->status);
        $this->assertNotNull($closedTask->completed_at);
        $this->assertNotNull($closedTask->confirmed_at);
        $this->assertSame($this->admin->id, $closedTask->confirmed_by);
        $this->assertNotNull($closedTask->reviewed_at);
        $this->assertSame('Disetujui otomatis dari status import (CLOSE)', $closedTask->admin_notes);

        $mixedTask = Task::where('category', 'exception')->where('document_number', 'EXC-004')->first();
        $this->assertNotNull($mixedTask);
        $this->assertSame(Task::STATUS_IN_PROGRESS, $mixedTask->status);
    }

    public function test_exception_import_updates_status_on_duplicate_update(): void
    {
        $task = Task::factory()->create([
            'category' => 'exception',
            'document_number' => 'EXC-DUP-01',
            'status' => Task::STATUS_PENDING,
            'customer_name' => 'PT Existing Exception',
            'confirmed_at' => null,
            'admin_notes' => null,
        ]);

        $csv = "NO,ID Pelanggan,Nama Pelanggan,Segmen,KP,Sales,Nama Layanan,BANDWIDTH,RP EXCEPTION,STATUS\n"
            ."1,EXC-DUP-01,PT Existing Exception,PUBLIK,Surabaya,Budi Santoso,METRONET,100 Mbps,5000000,CLOSE\n";

        $file = UploadedFile::fake()->createWithContent('Exception_Update.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'update',
            'target_category' => 'exception',
        ]);

        $response->assertRedirect(route('tasks.category', 'exception'));
        $response->assertSessionHas('success');

        $updatedTask = $task->fresh();
        $this->assertSame(Task::STATUS_APPROVED, $updatedTask->status);
        $this->assertNotNull($updatedTask->completed_at);
        $this->assertNotNull($updatedTask->confirmed_at);
        $this->assertSame($this->admin->id, $updatedTask->confirmed_by);
        $this->assertNotNull($updatedTask->reviewed_at);
        $this->assertSame('Disetujui otomatis dari status import (CLOSE)', $updatedTask->admin_notes);

        // Update back to EXCEPTION OPEN
        $csvOpen = "NO,ID Pelanggan,Nama Pelanggan,Segmen,KP,Sales,Nama Layanan,BANDWIDTH,RP EXCEPTION,STATUS\n"
            ."1,EXC-DUP-01,PT Existing Exception,PUBLIK,Surabaya,Budi Santoso,METRONET,100 Mbps,5000000,EXCEPTION OPEN\n";

        $fileOpen = UploadedFile::fake()->createWithContent('Exception_Update_Open.csv', $csvOpen);

        $responseOpen = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $fileOpen,
            'duplicate_action' => 'update',
            'target_category' => 'exception',
        ]);

        $responseOpen->assertRedirect(route('tasks.category', 'exception'));
        $this->assertSame(Task::STATUS_IN_PROGRESS, $task->fresh()->status);
    }

    public function test_exception_import_duplicate_update_preserves_existing_confirmed_at_and_admin_notes(): void
    {
        $existingConfirmedAt = now()->subDays(5)->startOfSecond();
        $existingAdminNotes = 'Catatan admin manual sebelumnya';

        $task = Task::factory()->create([
            'category' => 'exception',
            'document_number' => 'EXC-ALREADY-CONFIRMED',
            'status' => Task::STATUS_APPROVED,
            'customer_name' => 'PT Pelanggan Sudah Konfirmasi',
            'confirmed_at' => $existingConfirmedAt,
            'admin_notes' => $existingAdminNotes,
            'confirmed_by' => $this->user->id,
        ]);

        $csv = "NO,ID Pelanggan,Nama Pelanggan,Segmen,KP,Sales,Nama Layanan,BANDWIDTH,RP EXCEPTION,STATUS\n"
            ."1,EXC-ALREADY-CONFIRMED,PT Pelanggan Sudah Konfirmasi,PUBLIK,Surabaya,Budi Santoso,METRONET,100 Mbps,5000000,CLOSE\n";

        $file = UploadedFile::fake()->createWithContent('Exception_Preserve.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'update',
            'target_category' => 'exception',
        ]);

        $response->assertRedirect(route('tasks.category', 'exception'));
        $response->assertSessionHas('success');

        $freshTask = $task->fresh();
        $this->assertSame(Task::STATUS_APPROVED, $freshTask->status);
        $this->assertEquals($existingConfirmedAt->timestamp, $freshTask->confirmed_at->timestamp);
        $this->assertSame($this->user->id, $freshTask->confirmed_by);
        $this->assertSame($existingAdminNotes, $freshTask->admin_notes);
    }

    public function test_sbu_import_other_category_remains_pending_even_with_done_status(): void
    {
        $csv = "ID PA,NAMA PELANGGAN,LAYANAN PRODUK,HARGA LAMA,HARGA BARU,SELISIH,Kategori Customer,KP,Sales,Target BAI,Tanggal Upload,Status\n"
            ."BAI-DONE-001,PT Pelanggan BAI,INTERNET,0,1000000,1000000,PUBLIK,Surabaya,Budi Santoso,7,9/27/2026,Done\n";

        $file = UploadedFile::fake()->createWithContent('BAI_Done.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'bai',
        ]);

        $response->assertRedirect(route('tasks.category', 'bai'));
        $response->assertSessionHas('success');

        $baiTask = Task::where('category', 'bai')->where('document_number', 'BAI-DONE-001')->first();
        $this->assertNotNull($baiTask);
        $this->assertSame(Task::STATUS_PENDING, $baiTask->status);
    }

    public function test_so_open_import_with_real_pdl_and_ppb_headers_resolves_done_and_on_process(): void
    {
        // 1. PDL real header: 'Status Pekerjaan' column
        $headerPdl = 'ID PA,NAMA PELANGGAN,LAYANAN PRODUK,HARGA LAMA,HARGA BARU,SELISIH,Kategori Customer,KP,Sales,Tanggal PA Terbit,Target Aktivasi,Confirm to,Kendala PDL,Suport dari PPB,Progress Pekerjaan,Tanggal Pekerjaan Selesai,Status Pekerjaan,Legal,Target,Tanggal Kontrak Ready,Nomor Kontrak,Tanggal SLA Ready,Kendala,Status,Adev,Setting Kontrak,Setting SLA,Konfirmasi';
        $rowPdlDone = 'A142402009001,PT PDL Selesai,INTERNET,0,1000000,1000000,PLN,Surabaya,Budi Santoso,9/9/2026,9/30/2026,PDL,,,Proses instalasi selesai,,Done,LINGGAR AIRLANGGA,,,,,,On Process,TUSELLA YULIANA,,,Aktivasi';
        $rowPdlProcess = 'A142402009002,PT PDL Proses,INTERNET,0,2000000,2000000,PLN,Surabaya,Budi Santoso,9/9/2026,9/30/2026,PDL,,,Menunggu penarikan kabel,,On Process,LINGGAR AIRLANGGA,,,,,,On Process,TUSELLA YULIANA,,,Aktivasi';

        $filePdl = UploadedFile::fake()->createWithContent('Real_PDL.csv', "{$headerPdl}\n{$rowPdlDone}\n{$rowPdlProcess}\n");

        $responsePdl = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $filePdl,
            'duplicate_action' => 'skip',
            'target_category' => 'sso_open',
        ]);

        $responsePdl->assertRedirect(route('tasks.category', 'sso_open'));
        $responsePdl->assertSessionHas('success');

        $pdlDoneTask = Task::where('category', 'sso_open')->where('document_number', 'A142402009001')->first();
        $this->assertNotNull($pdlDoneTask);
        $this->assertSame(Task::STATUS_APPROVED, $pdlDoneTask->status);
        $this->assertNotNull($pdlDoneTask->completed_at);
        $this->assertNotNull($pdlDoneTask->confirmed_at);
        $this->assertSame($this->admin->id, $pdlDoneTask->confirmed_by);
        $this->assertNotNull($pdlDoneTask->reviewed_at);
        $this->assertSame('Disetujui otomatis dari status import Excel (Done/Selesai).', $pdlDoneTask->admin_notes);
        $this->assertSame('Proses instalasi selesai', $pdlDoneTask->comments);

        $pdlProcessTask = Task::where('category', 'sso_open')->where('document_number', 'A142402009002')->first();
        $this->assertNotNull($pdlProcessTask);
        $this->assertSame(Task::STATUS_IN_PROGRESS, $pdlProcessTask->status);
        $this->assertNull($pdlProcessTask->confirmed_at);
        $this->assertNull($pdlProcessTask->completed_at);
        $this->assertSame('Menunggu penarikan kabel', $pdlProcessTask->comments);

        // 2. PPB real header: 'Status' column
        $headerPpb = 'idPA,namapelanggan,layananproduk,Harga lama,Harga Baru,Selisih,Confirm to PPB,Kategori,Sub Bidang Revenue,Sales,Konfirmasi,Progress / Kendala,Adev,Tanggal Eksekusi,Status';
        $rowPpbDone = 'B141202009001,PT PPB Selesai,METRONET,0,3000000,3000000,PPB,PUBLIK,SURABAYA,Budi Santoso,Pull Out,Perangkat sudah dicabut,DEVI LESTYASARI,,Done';
        $rowPpbProcess = 'B141202009002,PT PPB Proses,METRONET,0,4000000,4000000,PPB,PUBLIK,SURABAYA,Budi Santoso,Pull Out,Koordinasi jadwal pull out,DEVI LESTYASARI,,On Process';

        $filePpb = UploadedFile::fake()->createWithContent('Real_PPB.csv', "{$headerPpb}\n{$rowPpbDone}\n{$rowPpbProcess}\n");

        $responsePpb = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $filePpb,
            'duplicate_action' => 'skip',
            'target_category' => 'sso_open',
        ]);

        $responsePpb->assertRedirect(route('tasks.category', 'sso_open'));
        $responsePpb->assertSessionHas('success');

        $ppbDoneTask = Task::where('category', 'sso_open')->where('document_number', 'B141202009001')->first();
        $this->assertNotNull($ppbDoneTask);
        $this->assertSame(Task::STATUS_APPROVED, $ppbDoneTask->status);
        $this->assertNotNull($ppbDoneTask->completed_at);
        $this->assertNotNull($ppbDoneTask->confirmed_at);
        $this->assertSame($this->admin->id, $ppbDoneTask->confirmed_by);
        $this->assertNotNull($ppbDoneTask->reviewed_at);
        $this->assertSame('Disetujui otomatis dari status import Excel (Done/Selesai).', $ppbDoneTask->admin_notes);
        $this->assertSame('Perangkat sudah dicabut', $ppbDoneTask->comments);

        $ppbProcessTask = Task::where('category', 'sso_open')->where('document_number', 'B141202009002')->first();
        $this->assertNotNull($ppbProcessTask);
        $this->assertSame(Task::STATUS_IN_PROGRESS, $ppbProcessTask->status);
        $this->assertNull($ppbProcessTask->confirmed_at);
        $this->assertNull($ppbProcessTask->completed_at);
        $this->assertSame('Koordinasi jadwal pull out', $ppbProcessTask->comments);
    }

    public function test_so_open_import_duplicate_update_updates_status_and_confirmation(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'document_number' => 'A142402009999',
            'status' => Task::STATUS_IN_PROGRESS,
            'customer_name' => 'PT SO Update Test',
            'confirmed_at' => null,
            'admin_notes' => null,
        ]);

        $headerPdl = 'ID PA,NAMA PELANGGAN,LAYANAN PRODUK,HARGA LAMA,HARGA BARU,SELISIH,Kategori Customer,KP,Sales,Tanggal PA Terbit,Target Aktivasi,Confirm to,Kendala PDL,Suport dari PPB,Progress Pekerjaan,Tanggal Pekerjaan Selesai,Status Pekerjaan,Legal,Target,Tanggal Kontrak Ready,Nomor Kontrak,Tanggal SLA Ready,Kendala,Status,Adev,Setting Kontrak,Setting SLA,Konfirmasi';
        $rowPdlDone = 'A142402009999,PT SO Update Test,INTERNET,0,1000000,1000000,PLN,Surabaya,Budi Santoso,9/9/2026,9/30/2026,PDL,,,Selesai akhir,,Done,LINGGAR AIRLANGGA,,,,,,On Process,TUSELLA YULIANA,,,Aktivasi';

        $file = UploadedFile::fake()->createWithContent('SO_Update_Done.csv', "{$headerPdl}\n{$rowPdlDone}\n");

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'update',
            'target_category' => 'sso_open',
        ]);

        $response->assertRedirect(route('tasks.category', 'sso_open'));
        $response->assertSessionHas('success');

        $fresh = $task->fresh();
        $this->assertSame(Task::STATUS_APPROVED, $fresh->status);
        $this->assertNotNull($fresh->completed_at);
        $this->assertNotNull($fresh->confirmed_at);
        $this->assertSame($this->admin->id, $fresh->confirmed_by);
        $this->assertNotNull($fresh->reviewed_at);
        $this->assertSame('Disetujui otomatis dari status import (Done/Selesai).', $fresh->admin_notes);
    }

    public function test_so_open_import_populates_comments_from_progress_pekerjaan_for_pdl_and_progress_kendala_for_ppb(): void
    {
        // 1. PDL test with Progress Pekerjaan: filled with newlines, empty spaces, and dash
        $csvPdl = "ID PA,NAMA PELANGGAN,LAYANAN PRODUK,HARGA LAMA,HARGA BARU,SELISIH,Kategori Customer,KP,Sales,Confirm to,Progress Pekerjaan,Status\n"
            ."A142402001111,PT Pelanggan PDL 1,INTERNET,0,1000000,1000000,PLN,Surabaya,Budi Santoso,PDL,\"Kendala FO lapangan\nMenunggu izin dinas\",On Process\n"
            ."A142402002222,PT Pelanggan PDL 2,INTERNET,0,2000000,2000000,PUBLIK,Malang,Budi Santoso,PDL,\"   \",On Process\n"
            ."A142402003333,PT Pelanggan PDL 3,INTERNET,0,3000000,3000000,PLN,Madiun,Budi Santoso,PDL,\" - \",On Process\n";

        $filePdl = UploadedFile::fake()->createWithContent('SO_Open_pdl.csv', $csvPdl);

        $responsePdl = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $filePdl,
            'duplicate_action' => 'skip',
            'target_category' => 'sso_open',
        ]);

        $responsePdl->assertRedirect(route('tasks.category', 'sso_open'));
        $responsePdl->assertSessionHas('success');

        $task1 = Task::where('category', 'sso_open')->where('document_number', 'A142402001111')->first();
        $this->assertNotNull($task1);
        $this->assertSame("Kendala FO lapangan\nMenunggu izin dinas", $task1->comments);
        $this->assertEquals($this->admin->id, $task1->comment_updated_by);
        $this->assertNotNull($task1->comment_updated_at);

        $task2 = Task::where('category', 'sso_open')->where('document_number', 'A142402002222')->first();
        $this->assertNotNull($task2);
        $this->assertNull($task2->comments);
        $this->assertNull($task2->comment_updated_by);
        $this->assertNull($task2->comment_updated_at);

        $task3 = Task::where('category', 'sso_open')->where('document_number', 'A142402003333')->first();
        $this->assertNotNull($task3);
        $this->assertNull($task3->comments);
        $this->assertNull($task3->comment_updated_by);

        // 2. PPB test with Progress / Kendala
        $csvPpb = "idPA,namapelanggan,layananproduk,Harga lama,Harga Baru,Selisih,Confirm to PPB,Kategori,Sub Bidang Revenue,Sales,Progress / Kendala,Status\n"
            ."B141202009999,PT Pelanggan PPB 1,METRONET,0,5000000,5000000,PPB,PUBLIK,SURABAYA,Budi Santoso,Sedang proses pull out perangkat,On Process\n";

        $filePpb = UploadedFile::fake()->createWithContent('SO_Open_ppb.csv', $csvPpb);

        $responsePpb = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $filePpb,
            'duplicate_action' => 'skip',
            'target_category' => 'sso_open',
        ]);

        $responsePpb->assertRedirect(route('tasks.category', 'sso_open'));
        $responsePpb->assertSessionHas('success');

        $task4 = Task::where('category', 'sso_open')->where('document_number', 'B141202009999')->first();
        $this->assertNotNull($task4);
        $this->assertSame('Sedang proses pull out perangkat', $task4->comments);
        $this->assertEquals($this->admin->id, $task4->comment_updated_by);
        $this->assertNotNull($task4->comment_updated_at);
    }

    public function test_so_open_empty_comments_remain_empty_and_can_be_edited_via_app(): void
    {
        $csv = "ID PA,NAMA PELANGGAN,LAYANAN PRODUK,HARGA LAMA,HARGA BARU,SELISIH,Kategori Customer,KP,Sales,Confirm to,Progress Pekerjaan,Status\n"
            ."A142402007777,PT Pelanggan Edit,INTERNET,0,1000000,1000000,PLN,Surabaya,Budi Santoso,PDL,-,On Process\n";

        $file = UploadedFile::fake()->createWithContent('SO_Open_empty.csv', $csv);

        $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'sso_open',
        ]);

        $task = Task::where('category', 'sso_open')->where('document_number', 'A142402007777')->first();
        $this->assertNotNull($task);
        $this->assertNull($task->comments);

        // Edit comment via app AJAX as admin
        $response = $this->actingAs($this->admin)->postJson(route('tasks.save-comment', $task), [
            'comments' => 'Komentar tambahan manual dari admin',
        ]);

        $response->assertOk();
        $this->assertSame('Komentar tambahan manual dari admin', $task->fresh()->comments);
        $this->assertEquals($this->admin->id, $task->fresh()->comment_updated_by);
    }

    public function test_so_open_import_update_does_not_overwrite_existing_comments(): void
    {
        // 1. Task that already has comment written by user
        $existingTaskWithComment = Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'document_number' => 'A142402008888',
            'comments' => 'Catatan manual penting oleh sales',
            'comment_updated_by' => $this->user->id,
            'customer_name' => 'PT Pelanggan Sudah Ada Komentar',
        ]);

        // 2. Task with empty comment
        $existingTaskWithoutComment = Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'document_number' => 'A142402008889',
            'comments' => null,
            'comment_updated_by' => null,
            'customer_name' => 'PT Pelanggan Belum Ada Komentar',
        ]);

        $csv = "ID PA,NAMA PELANGGAN,LAYANAN PRODUK,HARGA LAMA,HARGA BARU,SELISIH,Kategori Customer,KP,Sales,Confirm to,Progress Pekerjaan,Status\n"
            ."A142402008888,PT Pelanggan Sudah Ada Komentar,INTERNET,0,1000000,1000000,PLN,Surabaya,Budi Santoso,PDL,Progress Baru Dari File,On Process\n"
            ."A142402008889,PT Pelanggan Belum Ada Komentar,INTERNET,0,2000000,2000000,PLN,Surabaya,Budi Santoso,PDL,Komentar Pertama Dari File,On Process\n";

        $file = UploadedFile::fake()->createWithContent('SO_Open_update.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'update',
            'target_category' => 'sso_open',
        ]);

        $response->assertRedirect(route('tasks.category', 'sso_open'));
        $response->assertSessionHas('success');

        // Existing comment must NOT be overwritten
        $refreshed1 = $existingTaskWithComment->fresh();
        $this->assertSame('Catatan manual penting oleh sales', $refreshed1->comments);
        $this->assertEquals($this->user->id, $refreshed1->comment_updated_by);

        // Empty comment gets filled from file
        $refreshed2 = $existingTaskWithoutComment->fresh();
        $this->assertSame('Komentar Pertama Dari File', $refreshed2->comments);
        $this->assertEquals($this->admin->id, $refreshed2->comment_updated_by);
    }

    public function test_so_open_overdue_task_with_imported_comment_satisfies_overdue_comment_requirement(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'user_id' => $this->user->id,
            'due_date' => now()->subDays(3),
            'status' => Task::STATUS_IN_PROGRESS,
            'comments' => 'Komentar progress pekerjaan dari import',
        ]);

        $this->assertTrue($task->is_overdue);

        // Can mark complete without error because comment is already present
        $response = $this->actingAs($this->admin)->postJson(route('tasks.toggle-complete', $task));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'is_submitted' => true,
        ]);
        $this->assertSame(Task::STATUS_SUBMITTED, $task->fresh()->status);
    }

    public function test_baa_import_populates_comments_from_kendala_column(): void
    {
        $csvBaa = "ID PA,NAMA PELANGGAN,LAYANAN PRODUK,HARGA LAMA,HARGA BARU,SELISIH,Kategori Customer,KP,Sales,Tanggal Upload BAI,Aging BAA (Hari),Target,Tanggal Upload BAA,Kendala,Support,Status\n"
            ."PA-BAA-COMMENT,PT BAA Comment,INTERNET,0,1000000,1000000,PLN,Surabaya,Budi Santoso,10/1/2026,10,10/8/2026,10/2/2026,\"Kendala FO Lapangan\nIzin belum terbit\",Support PPB,Done\n"
            ."PA-BAA-EMPTY,PT BAA Empty,INTERNET,0,2000000,2000000,PLN,Malang,Budi Santoso,10/1/2026,10,10/8/2026,10/2/2026,-,Support PPB,Done\n";

        $fileBaa = UploadedFile::fake()->createWithContent('BAA_Comment.csv', $csvBaa);

        $responseBaa = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $fileBaa,
            'duplicate_action' => 'skip',
            'target_category' => 'baa',
        ]);

        $responseBaa->assertRedirect(route('tasks.category', 'baa'));
        $responseBaa->assertSessionHas('success');

        $taskWithComment = Task::where('category', 'baa')->where('document_number', 'PA-BAA-COMMENT')->first();
        $this->assertNotNull($taskWithComment);
        $this->assertSame("Kendala FO Lapangan\nIzin belum terbit", $taskWithComment->comments);
        $this->assertEquals($this->admin->id, $taskWithComment->comment_updated_by);
        $this->assertNotNull($taskWithComment->comment_updated_at);

        $taskEmpty = Task::where('category', 'baa')->where('document_number', 'PA-BAA-EMPTY')->first();
        $this->assertNotNull($taskEmpty);
        $this->assertNull($taskEmpty->comments);
        $this->assertNull($taskEmpty->comment_updated_by);

        // Duplicate update preserves existing comments
        $taskWithComment->update(['comments' => 'Komentar awal dari sales']);
        $fileUpdate = UploadedFile::fake()->createWithContent('BAA_Update.csv', $csvBaa);
        $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $fileUpdate,
            'duplicate_action' => 'update',
            'target_category' => 'baa',
        ]);
        $this->assertSame('Komentar awal dari sales', $taskWithComment->fresh()->comments);
    }

    public function test_exception_import_populates_comments_from_progress_kendala_column(): void
    {
        $csvExc = "NO,ID Pelanggan,Nama Pelanggan,Segmen,KP,Sales,Nama Layanan,BANDWIDTH,RP EXCEPTION,Progress / Kendala,STATUS\n"
            ."1,EXC-COMMENT,PT Exception Comment,PUBLIK,Surabaya,Budi Santoso,METRONET,100 Mbps,5000000,\"CSR PLTS\nUngenerate billing\",CLOSE\n"
            ."2,EXC-EMPTY,PT Exception Empty,PUBLIK,Malang,Budi Santoso,METRONET,50 Mbps,2500000,-,EXCEPTION OPEN\n";

        $fileExc = UploadedFile::fake()->createWithContent('Exc_Comment.csv', $csvExc);

        $responseExc = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $fileExc,
            'duplicate_action' => 'skip',
            'target_category' => 'exception',
        ]);

        $responseExc->assertRedirect(route('tasks.category', 'exception'));
        $responseExc->assertSessionHas('success');

        $taskWithComment = Task::where('category', 'exception')->where('document_number', 'EXC-COMMENT')->first();
        $this->assertNotNull($taskWithComment);
        $this->assertSame("CSR PLTS\nUngenerate billing", $taskWithComment->comments);
        $this->assertEquals($this->admin->id, $taskWithComment->comment_updated_by);

        $taskEmpty = Task::where('category', 'exception')->where('document_number', 'EXC-EMPTY')->first();
        $this->assertNotNull($taskEmpty);
        $this->assertNull($taskEmpty->comments);

        // Duplicate update preserves existing comments
        $taskWithComment->update(['comments' => 'Komentar manual exception']);
        $fileUpdate = UploadedFile::fake()->createWithContent('Exc_Update.csv', $csvExc);
        $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $fileUpdate,
            'duplicate_action' => 'update',
            'target_category' => 'exception',
        ]);
        $this->assertSame('Komentar manual exception', $taskWithComment->fresh()->comments);
    }

    public function test_kontrak_exp_import_populates_comments_from_progress_kendala_column(): void
    {
        $header = 'SID,NAMA PELANGGAN,LAYANAN PRODUK,RUPIAH LAYANAN,THBL TAGIHAN,TIPE REVENUE,BILLING PERIODE AWAL,BILLING PERIODE AKHIR,Kategori Customer,KP,Sales,Target,Tanggal Konfirmasi,Konfirmasi,Legal,Target,Nomor Kontrak Baru,Progress / Kendala,Tanggal eksekusi,Status,Nomor Kontrak Lama,Tanggal Kontrak Berakhir,Jenis Layanan,Nama Sales,Email Sales,Email Niaga';
        $rowComment = "141303002991,PT Kontrak Comment,INTERNET,0,-,-,--/--/--,--/--/--,PUBLIK,Surabaya,Budi Santoso,9/24/2026,,Perpanjang,LINGGAR AIRLANGGA,,,\"Review kontrak di pelanggan\nPIC: Bella\",,Done,040/09,1-Sep-26,,,,,";
        $rowEmpty = '141303002992,PT Kontrak Empty,INTERNET,0,-,-,--/--/--,--/--/--,PUBLIK,Surabaya,Budi Santoso,9/24/2026,,Perpanjang,LINGGAR AIRLANGGA,,,-,,On Process,040/09,1-Sep-26,,,,,';

        $csv = "{$header}\n{$rowComment}\n{$rowEmpty}\n";
        $file = UploadedFile::fake()->createWithContent('Kontrak_Comment.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'kontrak_exp',
        ]);

        $response->assertRedirect(route('tasks.category', 'kontrak_exp'));
        $response->assertSessionHas('success');

        $taskWithComment = Task::where('category', 'kontrak_exp')->where('document_number', '141303002991')->first();
        $this->assertNotNull($taskWithComment);
        $this->assertSame("Review kontrak di pelanggan\nPIC: Bella", $taskWithComment->comments);
        $this->assertEquals($this->admin->id, $taskWithComment->comment_updated_by);

        $taskEmpty = Task::where('category', 'kontrak_exp')->where('document_number', '141303002992')->first();
        $this->assertNotNull($taskEmpty);
        $this->assertNull($taskEmpty->comments);

        // Duplicate update preserves existing comments
        $taskWithComment->update(['comments' => 'Komentar lama tidak boleh tertimpa']);
        $fileUpdate = UploadedFile::fake()->createWithContent('Kontrak_Update.csv', $csv);
        $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $fileUpdate,
            'duplicate_action' => 'update',
            'target_category' => 'kontrak_exp',
        ]);
        $this->assertSame('Komentar lama tidak boleh tertimpa', $taskWithComment->fresh()->comments);
    }

    public function test_bai_import_strictly_skips_comments(): void
    {
        $csvBai = "ID PA,NAMA PELANGGAN,LAYANAN PRODUK,HARGA LAMA,HARGA BARU,SELISIH,Kategori Customer,KP,Sales,Target BAI,Tanggal Upload,Kendala,Status\n"
            ."A141401005999,SMK DARUL ULUM,METRONET,0,1693548,1693548,PUBLIK,Surabaya,Budi Santoso,7,9/27/2026,Kendala BAI Lapangan,Done\n";

        $fileBai = UploadedFile::fake()->createWithContent('BAI_Skip_Comment.csv', $csvBai);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $fileBai,
            'duplicate_action' => 'skip',
            'target_category' => 'bai',
        ]);

        $response->assertRedirect(route('tasks.category', 'bai'));
        $response->assertSessionHas('success');

        $baiTask = Task::where('category', 'bai')->where('document_number', 'A141401005999')->first();
        $this->assertNotNull($baiTask);
        $this->assertNull($baiTask->comments);
    }

    public function test_category_ordering_in_sidebar_and_categories_list(): void
    {
        $expectedOrder = ['sso_open', 'bai', 'baa', 'exception', 'kontrak_exp', 'contract_verification'];
        $actualOrder = array_keys(Task::categories());
        $this->assertSame($expectedOrder, $actualOrder);

        // Check sidebar rendering order
        $response = $this->actingAs($this->admin)->get(route('tasks.index'));
        $response->assertOk();
        $html = $response->getContent();

        $posSso = strpos($html, route('tasks.category', 'sso_open'));
        $posBai = strpos($html, route('tasks.category', 'bai'));
        $posBaa = strpos($html, route('tasks.category', 'baa'));
        $posExc = strpos($html, route('tasks.category', 'exception'));
        $posKontrak = strpos($html, route('tasks.category', 'kontrak_exp'));
        $posVerifKontrak = strpos($html, route('tasks.category', 'contract_verification'));

        $this->assertNotFalse($posSso);
        $this->assertNotFalse($posBai);
        $this->assertNotFalse($posBaa);
        $this->assertNotFalse($posExc);
        $this->assertNotFalse($posKontrak);
        $this->assertNotFalse($posVerifKontrak);

        $this->assertTrue($posSso < $posBai, 'SO Open should appear before BAI');
        $this->assertTrue($posBai < $posBaa, 'BAI should appear before BAA');
        $this->assertTrue($posBaa < $posExc, 'BAA should appear before EXCEPTION');
        $this->assertTrue($posExc < $posKontrak, 'EXCEPTION should appear before Kontrak Exp');
        $this->assertTrue($posKontrak < $posVerifKontrak, 'Kontrak Exp should appear before Verifikasi Kontrak');
    }

    public function test_real_sample_so_open_pdl_csv_extracts_progress_pekerjaan_comment(): void
    {
        $path = storage_path('app/imports/Project Monitoring PPB 2026(1 SO Open pdl.csv');
        $this->assertFileExists($path);

        $file = new UploadedFile($path, 'Project Monitoring PPB 2026(1 SO Open pdl.csv', 'text/csv', null, true);
        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'sso_open',
        ]);

        $response->assertRedirect(route('tasks.category', 'sso_open'));
        $response->assertSessionHas('success');

        // Check A142402003293 (row 2 in CSV)
        $taskWithComment = Task::where('category', 'sso_open')->where('document_number', 'A142402003293')->first();
        $this->assertNotNull($taskWithComment);
        $this->assertSame('Kurang upload dokumen mutation', $taskWithComment->comments);
        $this->assertEquals($this->admin->id, $taskWithComment->comment_updated_by);
        $this->assertNotNull($taskWithComment->comment_updated_at);

        // Check A142204001177 (row 4 in CSV, empty comment)
        $taskWithoutComment = Task::where('category', 'sso_open')->where('document_number', 'A142204001177')->first();
        $this->assertNotNull($taskWithoutComment);
        $this->assertNull($taskWithoutComment->comments);
    }

    public function test_kontrak_exp_import_with_real_headers_resolves_done_and_on_process(): void
    {
        $header = 'SID,NAMA PELANGGAN,LAYANAN PRODUK,RUPIAH LAYANAN,THBL TAGIHAN,TIPE REVENUE,BILLING PERIODE AWAL,BILLING PERIODE AKHIR,Kategori Customer,KP,Sales,Target,Tanggal Konfirmasi,Konfirmasi,Legal,Target,Nomor Kontrak Baru,Progress / Kendala,Tanggal eksekusi,Status,Nomor Kontrak Lama,Tanggal Kontrak Berakhir,Jenis Layanan,Nama Sales,Email Sales,Email Niaga';

        $rowDone = '141303002800,PT GRAHA PERDANA MEDIKA,INTERNET CORPORATE,0,-,-,--/--/--,--/--/--,PUBLIK,Surabaya,Budi Santoso,9/24/2026,,Perpanjang,LINGGAR AIRLANGGA,,,Kendala pembaruan kontrak,,Done,040/09/TU.04.06,1-Sep-26,,,,,';
        $rowProcess = '140000001158,DINAS KOMINFO MOJOKERTO,ICONNET,0,-,-,--/--/--,--/--/--,PUBLIK,Surabaya,Budi Santoso,,,Deaktivasi,LINGGAR AIRLANGGA,,,Harus di deaktivasi by IT,,On Process,AGR/2210/2678,31-Dec-24,,,,,';
        $rowMultiSpace = '140130300999,PT MULTI SPACE TEST,INTERNET,0,-,-,--/--/--,--/--/--,PLN,Malang,Budi Santoso,,,Perpanjang,LINGGAR AIRLANGGA,,,Proses review,,On  Process,AGR/2026/001,31-Dec-26,,,,,';
        $rowUnknown = '140130300998,PT UNKNOWN STATUS,INTERNET,0,-,-,--/--/--,--/--/--,PLN,Madiun,Budi Santoso,,,Perpanjang,LINGGAR AIRLANGGA,,,Status tidak dikenal,,Review Ulang,AGR/2026/002,31-Dec-26,,,,,';

        $csv = "{$header}\n{$rowDone}\n{$rowProcess}\n{$rowMultiSpace}\n{$rowUnknown}\n";
        $file = UploadedFile::fake()->createWithContent('Kontrak_Exp.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'kontrak_exp',
        ]);

        $response->assertRedirect(route('tasks.category', 'kontrak_exp'));
        $response->assertSessionHas('success');

        // Done -> approved with confirmation
        $taskDone = Task::where('category', 'kontrak_exp')->where('document_number', '141303002800')->first();
        $this->assertNotNull($taskDone);
        $this->assertSame(Task::STATUS_APPROVED, $taskDone->status);
        $this->assertNotNull($taskDone->completed_at);
        $this->assertNotNull($taskDone->confirmed_at);
        $this->assertSame($this->admin->id, $taskDone->confirmed_by);
        $this->assertNotNull($taskDone->reviewed_at);
        $this->assertSame('Disetujui otomatis dari status import (Done)', $taskDone->admin_notes);

        // On Process -> in_progress
        $taskProcess = Task::where('category', 'kontrak_exp')->where('document_number', '140000001158')->first();
        $this->assertNotNull($taskProcess);
        $this->assertSame(Task::STATUS_IN_PROGRESS, $taskProcess->status);
        $this->assertNull($taskProcess->confirmed_at);
        $this->assertNull($taskProcess->completed_at);

        // On  Process (multiple spaces) -> in_progress
        $taskMultiSpace = Task::where('category', 'kontrak_exp')->where('document_number', '140130300999')->first();
        $this->assertNotNull($taskMultiSpace);
        $this->assertSame(Task::STATUS_IN_PROGRESS, $taskMultiSpace->status);

        // Unknown -> in_progress
        $taskUnknown = Task::where('category', 'kontrak_exp')->where('document_number', '140130300998')->first();
        $this->assertNotNull($taskUnknown);
        $this->assertSame(Task::STATUS_IN_PROGRESS, $taskUnknown->status);
    }

    public function test_kontrak_exp_status_normalization_handles_invisible_chars_and_non_breaking_spaces(): void
    {
        $header = 'SID,NAMA PELANGGAN,LAYANAN PRODUK,RUPIAH LAYANAN,THBL TAGIHAN,TIPE REVENUE,BILLING PERIODE AWAL,BILLING PERIODE AKHIR,Kategori Customer,KP,Sales,Target,Tanggal Konfirmasi,Konfirmasi,Legal,Target,Nomor Kontrak Baru,Progress / Kendala,Tanggal eksekusi,Status,Nomor Kontrak Lama,Tanggal Kontrak Berakhir,Jenis Layanan,Nama Sales,Email Sales,Email Niaga';

        // Non-breaking space (\xc2\xa0), Zero-width space (\xe2\x80\x8b), BOM (\xef\xbb\xbf)
        $rowNbspDone = "140130300881,PT NBSP DONE,INTERNET,0,-,-,--/--/--,--/--/--,PUBLIK,Surabaya,Budi Santoso,9/24/2026,,Perpanjang,LINGGAR AIRLANGGA,,,Kendala pembaruan kontrak,,\xc2\xa0Done\xc2\xa0,040/09/TU.04.06,1-Sep-26,,,,,";
        $rowZwspDone = "140130300882,PT ZWSP DONE,INTERNET,0,-,-,--/--/--,--/--/--,PUBLIK,Surabaya,Budi Santoso,9/24/2026,,Perpanjang,LINGGAR AIRLANGGA,,,Kendala pembaruan kontrak,,\xe2\x80\x8bDone\xe2\x80\x8b,040/09/TU.04.06,1-Sep-26,,,,,";
        $rowBomDone = "140130300883,PT BOM DONE,INTERNET,0,-,-,--/--/--,--/--/--,PUBLIK,Surabaya,Budi Santoso,9/24/2026,,Perpanjang,LINGGAR AIRLANGGA,,,Kendala pembaruan kontrak,,\xef\xbb\xbfDone,040/09/TU.04.06,1-Sep-26,,,,,";
        $rowNbspProcess = "140130300884,PT NBSP PROCESS,INTERNET,0,-,-,--/--/--,--/--/--,PUBLIK,Surabaya,Budi Santoso,9/24/2026,,Perpanjang,LINGGAR AIRLANGGA,,,Kendala pembaruan kontrak,,On\xc2\xa0Process,040/09/TU.04.06,1-Sep-26,,,,,";

        $csv = "{$header}\n{$rowNbspDone}\n{$rowZwspDone}\n{$rowBomDone}\n{$rowNbspProcess}\n";
        $file = UploadedFile::fake()->createWithContent('Kontrak_Exp_Special_Chars.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'kontrak_exp',
        ]);

        $response->assertRedirect(route('tasks.category', 'kontrak_exp'));
        $response->assertSessionHas('success');

        $taskNbspDone = Task::where('category', 'kontrak_exp')->where('document_number', '140130300881')->first();
        $this->assertNotNull($taskNbspDone);
        $this->assertSame(Task::STATUS_APPROVED, $taskNbspDone->status);
        $this->assertNotNull($taskNbspDone->confirmed_at);

        $taskZwspDone = Task::where('category', 'kontrak_exp')->where('document_number', '140130300882')->first();
        $this->assertNotNull($taskZwspDone);
        $this->assertSame(Task::STATUS_APPROVED, $taskZwspDone->status);

        $taskBomDone = Task::where('category', 'kontrak_exp')->where('document_number', '140130300883')->first();
        $this->assertNotNull($taskBomDone);
        $this->assertSame(Task::STATUS_APPROVED, $taskBomDone->status);

        $taskNbspProcess = Task::where('category', 'kontrak_exp')->where('document_number', '140130300884')->first();
        $this->assertNotNull($taskNbspProcess);
        $this->assertSame(Task::STATUS_IN_PROGRESS, $taskNbspProcess->status);
    }

    public function test_kontrak_exp_import_duplicate_update_updates_status_and_confirmation(): void
    {
        $task1 = Task::factory()->create([
            'category' => Task::CATEGORY_KONTRAK_EXP,
            'document_number' => '141303002801',
            'status' => Task::STATUS_PENDING,
            'customer_name' => 'PT Existing Kontrak Exp',
            'confirmed_at' => null,
            'admin_notes' => null,
        ]);

        $header = 'SID,NAMA PELANGGAN,LAYANAN PRODUK,RUPIAH LAYANAN,THBL TAGIHAN,TIPE REVENUE,BILLING PERIODE AWAL,BILLING PERIODE AKHIR,Kategori Customer,KP,Sales,Target,Tanggal Konfirmasi,Konfirmasi,Legal,Target,Nomor Kontrak Baru,Progress / Kendala,Tanggal eksekusi,Status,Nomor Kontrak Lama,Tanggal Kontrak Berakhir,Jenis Layanan,Nama Sales,Email Sales,Email Niaga';
        $rowDone = '141303002801,PT Existing Kontrak Exp,INTERNET CORPORATE,0,-,-,--/--/--,--/--/--,PUBLIK,Surabaya,Budi Santoso,9/24/2026,,Perpanjang,LINGGAR AIRLANGGA,,,Kendala pembaruan kontrak,,Done,040/09/TU.04.06,1-Sep-26,,,,,';

        $file = UploadedFile::fake()->createWithContent('Kontrak_Exp_Update.csv', "{$header}\n{$rowDone}\n");

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'update',
            'target_category' => 'kontrak_exp',
        ]);

        $response->assertRedirect(route('tasks.category', 'kontrak_exp'));
        $response->assertSessionHas('success');

        $fresh = $task1->fresh();
        $this->assertSame(Task::STATUS_APPROVED, $fresh->status);
        $this->assertNotNull($fresh->completed_at);
        $this->assertNotNull($fresh->confirmed_at);
        $this->assertSame($this->admin->id, $fresh->confirmed_by);
        $this->assertNotNull($fresh->reviewed_at);
        $this->assertSame('Disetujui otomatis dari status import (Done)', $fresh->admin_notes);
    }

    public function test_real_sample_kontrak_exp_csv_imports_27_approved_and_16_in_progress(): void
    {
        $path = storage_path('app/imports/Project Monitoring PPB 2026(5 Contract Expired).csv');
        $this->assertFileExists($path);

        $file = new UploadedFile($path, 'Project Monitoring PPB 2026(5 Contract Expired).csv', 'text/csv', null, true);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'kontrak_exp',
        ]);

        $response->assertRedirect(route('tasks.category', 'kontrak_exp'));
        $response->assertSessionHas('success');

        $this->assertSame(43, Task::where('category', 'kontrak_exp')->count());
        $this->assertSame(27, Task::where('category', 'kontrak_exp')->where('status', Task::STATUS_APPROVED)->count());
        $this->assertSame(16, Task::where('category', 'kontrak_exp')->where('status', Task::STATUS_IN_PROGRESS)->count());
        $this->assertSame(0, Task::where('category', 'kontrak_exp')->where('status', Task::STATUS_PENDING)->count());
        $this->assertSame(26, Task::where('category', 'kontrak_exp')->whereNotNull('comments')->count());

        // Check document 140130300481 specifically
        $targetTask = Task::where('category', 'kontrak_exp')->where('document_number', '140130300481')->first();
        $this->assertNotNull($targetTask);
        $this->assertSame('Review kontrak di pelanggan, PIC : Bella, Target : 23 Sep', $targetTask->comments);
        $this->assertEquals($this->admin->id, $targetTask->comment_updated_by);
        $this->assertNotNull($targetTask->comment_updated_at);

        // Ensure all approved tasks have confirmed_at and admin_notes
        $approvedWithoutConfirmation = Task::where('category', 'kontrak_exp')
            ->where('status', Task::STATUS_APPROVED)
            ->where(function ($q) {
                $q->whereNull('confirmed_at')
                    ->orWhereNull('completed_at')
                    ->orWhereNull('reviewed_at')
                    ->orWhere('admin_notes', '!=', 'Disetujui otomatis dari status import (Done)');
            })
            ->count();
        $this->assertSame(0, $approvedWithoutConfirmation);

        // Verify category page displays metrics accurately
        $viewResponse = $this->actingAs($this->admin)->get(route('tasks.category', 'kontrak_exp'));
        $viewResponse->assertOk();
        $viewResponse->assertSee('27'); // Selesai count
        $viewResponse->assertSee('16'); // On Process count
    }

    public function test_real_sample_files_for_all_categories(): void
    {
        // 1. Real BAA sample
        $pathBaa = storage_path('app/imports/Project Monitoring PPB 2026(3 BAA Open).csv');
        if (file_exists($pathBaa)) {
            $file = new UploadedFile($pathBaa, basename($pathBaa), 'text/csv', null, true);
            $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
                'file' => $file,
                'duplicate_action' => 'skip',
                'target_category' => 'baa',
            ]);
            $response->assertRedirect(route('tasks.category', 'baa'));
            $this->assertSame(1, Task::where('category', 'baa')->whereNotNull('comments')->count());
        }

        // 2. Real BAI sample (skipped)
        $pathBai = storage_path('app/imports/BAI Open.csv');
        if (file_exists($pathBai)) {
            $file = new UploadedFile($pathBai, basename($pathBai), 'text/csv', null, true);
            $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
                'file' => $file,
                'duplicate_action' => 'skip',
                'target_category' => 'bai',
            ]);
            $response->assertRedirect(route('tasks.category', 'bai'));
            $this->assertSame(0, Task::where('category', 'bai')->whereNotNull('comments')->count());
        }
    }

    public function test_tolerant_comment_column_header_matching_per_category(): void
    {
        $mapper = app(SbuSheetMapper::class);

        // SO Open variations
        $soPdlHeaders = ['idPA', 'namapelanggan', 'layananproduk', 'Kendala PDL', 'Suport dari PPB', "Progress Pekerjaan\n", 'Status Pekerjaan', 'Kendala'];
        $this->assertSame(5, $mapper->resolveCommentColumnIndex($soPdlHeaders, Task::CATEGORY_SSO_OPEN));

        $soPbbHeaders = ['idPA', 'namapelanggan', 'layananproduk', 'Konfirmasi', 'Progres   /   Kendala', 'Adev', 'Status'];
        $this->assertSame(4, $mapper->resolveCommentColumnIndex($soPbbHeaders, Task::CATEGORY_SSO_OPEN));

        // BAA variations
        $baaHeaders = ['ID PA', 'NAMA PELANGGAN', 'LAYANAN PRODUK', 'Kendala', 'Support', 'Status'];
        $this->assertSame(3, $mapper->resolveCommentColumnIndex($baaHeaders, Task::CATEGORY_BAA));

        // Exception variations
        $exceptionHeaders = ['idPelanggan', 'namaPelanggan', 'STATUS', "PROGRES / KENDALA / SUPPORT\r\n", 'CEK ', 'SALES', 'ADEV'];
        $this->assertSame(3, $mapper->resolveCommentColumnIndex($exceptionHeaders, Task::CATEGORY_EXCEPTION));

        // Contract Exp variations
        $contractHeaders = ['SID', 'NAMA PELANGGAN', 'Nomor Kontrak Baru', 'Progress/Kendala', 'Tanggal eksekusi', 'Status'];
        $this->assertSame(3, $mapper->resolveCommentColumnIndex($contractHeaders, Task::CATEGORY_KONTRAK_EXP));

        // Forbidden columns alone should NEVER match as comments
        $forbiddenHeaders = ['Status Pekerjaan', 'STATUS', 'CEK ', 'SALES', 'ADEV', 'KETERANGAN', 'Kendala PDL', 'Suport dari PPB', 'Harga Baru', 'Tanggal Eksekusi'];
        foreach ([Task::CATEGORY_SSO_OPEN, Task::CATEGORY_BAA, Task::CATEGORY_EXCEPTION, Task::CATEGORY_KONTRAK_EXP] as $cat) {
            $this->assertNull($mapper->resolveCommentColumnIndex($forbiddenHeaders, $cat));
        }

        // BAI is strictly skipped
        $this->assertNull($mapper->resolveCommentColumnIndex(['idPA', 'Progress / Kendala', 'Kendala'], Task::CATEGORY_BAI));
    }

    public function test_import_with_original_headers_for_all_categories_populates_comments_and_preserves_on_update(): void
    {
        // 1. SO Open PDL
        $csvPdl = "idPA,namapelanggan,layananproduk,Harga lama,Harga Baru,Selisih,Kategori,KP,Sub Bidang Revenue,Sales,Target,Setting SLA,Kendala PDL,Suport dari PPB,Progress Pekerjaan,Tanggal Pekerjaan Selesai,Status Pekerjaan,Alasan,Keterangan,Aging,Target Selesai,Setting Kontrak,Kendala\n";
        $csvPdl .= "PDL001,PELANGGAN A,INTERNET,1000,2000,1000,PUBLIK,Surabaya,Revenue,Budi Santoso,Target,SLA,Kendala1,Support1,Sedang penarikan kabel FO,2026-10-19,On Process,Alasan,Ket,5,2026-10-25,Kontrak,Kendala2\n";
        $csvPdl .= "PDL002,PELANGGAN B,INTERNET,1000,2000,1000,PUBLIK,Surabaya,Revenue,Budi Santoso,Target,SLA,Kendala1,Support1,-,2026-10-19,Done,Alasan,Ket,5,2026-10-25,Kontrak,Kendala2\n";

        $filePdl = UploadedFile::fake()->createWithContent('so_pdl.csv', $csvPdl);
        $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $filePdl,
            'duplicate_action' => 'skip',
            'target_category' => Task::CATEGORY_SSO_OPEN,
        ]);

        $taskPdl1 = Task::where('document_number', 'PDL001')->first();
        $this->assertNotNull($taskPdl1);
        $this->assertSame('Sedang penarikan kabel FO', $taskPdl1->comments);
        $this->assertSame($this->admin->id, $taskPdl1->comment_updated_by);
        $this->assertNotNull($taskPdl1->comment_updated_at);

        $taskPdl2 = Task::where('document_number', 'PDL002')->first();
        $this->assertNotNull($taskPdl2);
        $this->assertNull($taskPdl2->comments);

        // 2. SO Open PPB
        $csvPbb = "idPA,namapelanggan,layananproduk,Harga lama,Harga Baru,Selisih,Confirm to PPB,Kategori,Sub Bidang Revenue,Sales,Konfirmasi,Progress / Kendala,Adev,Tanggal Eksekusi,Status\n";
        $csvPbb .= "B001,PELANGGAN C,METRONET,1000,3000,2000,Ya,PLN,Revenue,Budi Santoso,Konf,Koordinasi dengan PIC lapangan,Adev,2026-10-19,On Process\n";

        $filePbb = UploadedFile::fake()->createWithContent('so_pbb.csv', $csvPbb);
        $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $filePbb,
            'duplicate_action' => 'skip',
            'target_category' => Task::CATEGORY_SSO_OPEN,
        ]);

        $taskPbb = Task::where('document_number', 'B001')->first();
        $this->assertNotNull($taskPbb);
        $this->assertSame('Koordinasi dengan PIC lapangan', $taskPbb->comments);

        // 3. BAA
        $csvBaa = "ID PA,NAMA PELANGGAN,LAYANAN PRODUK,HARGA LAMA,HARGA BARU,SELISIH,Kategori Customer,KP,Sales,Tanggal Upload BAI,Aging BAA (Hari),Target,Tanggal Upload BAA,Kendala,Support,Status\n";
        $csvBaa .= "BAA001,PELANGGAN D,INTERNET,1000,2000,1000,PUBLIK,Surabaya,Budi Santoso,8/7/2026,6,,8/13/2026,Menunggu tanda tangan direktur,Support BAA,Done\n";

        $fileBaa = UploadedFile::fake()->createWithContent('baa.csv', $csvBaa);
        $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $fileBaa,
            'duplicate_action' => 'skip',
            'target_category' => Task::CATEGORY_BAA,
        ]);

        $taskBaa = Task::where('document_number', 'BAA001')->first();
        $this->assertNotNull($taskBaa);
        $this->assertSame('Menunggu tanda tangan direktur', $taskBaa->comments);

        // 4. Exception
        $csvExp = "idPelanggan,namaPelanggan,idPelangganProduk,namaLayananProduk,tahunBulanTagihan,billingPeriodeAwal,billingPeriodeAkhir, rpLayanan ,rpInstalasi,rpRestitusi, rpException ,bidangRevenue,subBidangRevenue,segmenIcon,alasanException,petugasException,tanggalException,SID / THBL,SBU OWNER,STATUS,TANGGAL KOMITMEN CLOSE (MM/DD/YYYY),Bulan,PROGRES / KENDALA / SUPPORT,KETERANGAN,TANGGAL KUNCI KOMITMEN AWAL,WEEK KOMITMEN,CEK ,SALES,ADEV\n";
        $csvExp .= "CUST01,PELANGGAN E,P01,INTERNET,202610,2026-10-01,2026-10-31,1000000,0,0,500000,REV,SUB,PLN,Alasan,Petugas,2026-10-10,EXP001,SBU,CLOSE,2026-10-20,Okt,Proses rekonsiliasi billing,Keterangan,2026-10-01,W1,Cek1,Sales1,Adev1\n";

        $fileExp = UploadedFile::fake()->createWithContent('exception.csv', $csvExp);
        $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $fileExp,
            'duplicate_action' => 'skip',
            'target_category' => Task::CATEGORY_EXCEPTION,
        ]);

        $taskExp = Task::where('document_number', 'EXP001')->first();
        $this->assertNotNull($taskExp);
        $this->assertSame('Proses rekonsiliasi billing', $taskExp->comments);
        $this->assertSame(Task::STATUS_APPROVED, $taskExp->status);

        // 5. Test duplicateAction = 'update'
        // Task with existing comment should NOT be overwritten
        $taskPdl1->update(['comments' => 'Komentar manual dari sales']);
        // Task without comment (PDL002) should receive comment from file
        $csvPdlUpdate = "idPA,namapelanggan,layananproduk,Harga lama,Harga Baru,Selisih,Kategori,KP,Sub Bidang Revenue,Sales,Target,Setting SLA,Kendala PDL,Suport dari PPB,Progress Pekerjaan,Tanggal Pekerjaan Selesai,Status Pekerjaan,Alasan,Keterangan,Aging,Target Selesai,Setting Kontrak,Kendala\n";
        $csvPdlUpdate .= "PDL001,PELANGGAN A,INTERNET,1000,2000,1000,PUBLIK,Surabaya,Revenue,Budi Santoso,Target,SLA,Kendala1,Support1,Komentar baru dari file (seharusnya tidak menimpa),2026-10-19,On Process,Alasan,Ket,5,2026-10-25,Kontrak,Kendala2\n";
        $csvPdlUpdate .= "PDL002,PELANGGAN B,INTERNET,1000,2000,1000,PUBLIK,Surabaya,Revenue,Budi Santoso,Target,SLA,Kendala1,Support1,Komentar baru untuk PDL002,2026-10-19,Done,Alasan,Ket,5,2026-10-25,Kontrak,Kendala2\n";

        $filePdlUpdate = UploadedFile::fake()->createWithContent('so_pdl_update.csv', $csvPdlUpdate);
        $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $filePdlUpdate,
            'duplicate_action' => 'update',
            'target_category' => Task::CATEGORY_SSO_OPEN,
        ]);

        $freshPdl1 = $taskPdl1->fresh();
        $this->assertSame('Komentar manual dari sales', $freshPdl1->comments, 'Komentar yang sudah ada tidak boleh tertimpa.');

        $freshPdl2 = $taskPdl2->fresh();
        $this->assertSame('Komentar baru untuk PDL002', $freshPdl2->comments, 'Komentar kosong harus terisi saat mode update.');
        $this->assertSame($this->admin->id, $freshPdl2->comment_updated_by);
        $this->assertNotNull($freshPdl2->comment_updated_at);
    }
}
