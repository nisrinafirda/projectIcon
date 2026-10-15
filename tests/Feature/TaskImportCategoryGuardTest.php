<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
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

        $tempPath = tempnam(sys_get_temp_dir(), 'test_excel_').'.xlsx';
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
}
