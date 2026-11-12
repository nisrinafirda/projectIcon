<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Services\SbuSheetMapper;
use App\Services\WhatsAppReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContractVerificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $sales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Admin ICON',
            'email' => 'admin@icon.co.id',
            'role' => 'admin',
        ]);

        $this->sales = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@icon.co.id',
            'role' => 'user',
        ]);
    }

    public function test_task_model_supports_contract_verification_and_notes_alias_separate_from_comments(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_CONTRACT_VERIFICATION,
            'document_number' => 'KTR/2026/001',
            'customer_name' => 'PT Pelanggan Kontrak',
            'kp' => Task::KP_JEMBER,
            'sales_name' => 'Budi Santoso',
            'user_id' => $this->sales->id,
            'notes' => 'Dokumen Perjanjian belum di unggah',
            'comments' => null,
        ]);

        $this->assertSame('contract_verification', $task->category);
        $this->assertSame('Verifikasi Kontrak', $task->category_label);
        $this->assertSame('Dokumen Perjanjian belum di unggah', $task->verification_note);
        $this->assertSame('Dokumen Perjanjian belum di unggah', $task->notes);
        $this->assertNull($task->comments);

        // Updating sales comment does not overwrite verification_note
        $task->update(['comments' => 'Sedang proses unggah dokumen perjanjian']);
        $task->refresh();

        $this->assertSame('Dokumen Perjanjian belum di unggah', $task->verification_note);
        $this->assertSame('Sedang proses unggah dokumen perjanjian', $task->comments);
    }

    public function test_list_page_groups_by_kp_with_counts_for_admin_and_links_to_detail_page(): void
    {
        // Create 2 tasks in Surabaya and 8 tasks in Jember
        Task::factory()->count(2)->sequence(
            ['document_number' => 'KTR-SBY-01', 'customer_name' => 'PT Surabaya Maju'],
            ['document_number' => 'KTR-SBY-02', 'customer_name' => 'PT Surabaya Maju'],
        )->create([
            'category' => Task::CATEGORY_CONTRACT_VERIFICATION,
            'kp' => Task::KP_SURABAYA,
            'sales_name' => 'Budi Santoso',
            'user_id' => $this->sales->id,
            'verification_note' => 'TANGGAL KONTRAK TIDAK SESUAI',
            'status' => Task::STATUS_IN_PROGRESS,
            'due_date' => Carbon::create(2026, 11, 20),
        ]);

        Task::factory()->count(8)->sequence(
            fn ($seq) => [
                'document_number' => 'KTR-JBR-0'.($seq->index + 1),
                'customer_name' => $seq->index < 5 ? 'RSUD Jember' : 'Pemkab Jember',
            ]
        )->create([
            'category' => Task::CATEGORY_CONTRACT_VERIFICATION,
            'kp' => Task::KP_JEMBER,
            'sales_name' => 'Budi Santoso',
            'user_id' => $this->sales->id,
            'verification_note' => 'NPWP tidak sesuai dengan NPWP upload',
            'status' => Task::STATUS_IN_PROGRESS,
            'due_date' => Carbon::create(2026, 11, 25),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('tasks.category', ['category' => 'contract_verification', 'month' => 11, 'year' => 2026]));

        $response->assertOk();
        $response->assertSee('Verifikasi Kontrak');
        $response->assertSee('KP SURABAYA (2 data)');
        $response->assertSee('KP JEMBER (8 data)');
        $response->assertSee('PT Surabaya Maju');
        $response->assertSee('RSUD Jember');
        $response->assertSee('Pemkab Jember');
        $response->assertSee(route('tasks.category.customer', [
            'category' => 'contract_verification',
            'customer' => Str::slug('RSUD Jember'),
        ]), false);
    }

    public function test_detail_page_renders_contract_verification_columns_and_row_contents(): void
    {
        $task1 = Task::factory()->create([
            'category' => Task::CATEGORY_CONTRACT_VERIFICATION,
            'document_number' => '045/KTR/ICON/2026',
            'customer_name' => 'PT Mitra Nusantara',
            'kp' => Task::KP_MALANG,
            'sales_name' => 'Budi Santoso',
            'user_id' => $this->sales->id,
            'verification_note' => 'Dokumen Perjanjian belum di unggah',
            'status' => Task::STATUS_IN_PROGRESS,
            'due_date' => Carbon::today()->addDays(3),
            'comments' => 'Menunggu paraf direktur',
        ]);

        $task2 = Task::factory()->create([
            'category' => Task::CATEGORY_CONTRACT_VERIFICATION,
            'document_number' => '046/KTR/ICON/2026',
            'customer_name' => 'PT Mitra Nusantara',
            'kp' => Task::KP_MALANG,
            'sales_name' => 'Budi Santoso',
            'user_id' => $this->sales->id,
            'verification_note' => 'NPWP tidak sesuai dengan NPWP upload',
            'status' => Task::STATUS_IN_PROGRESS,
            'due_date' => Carbon::today()->addDays(5),
        ]);

        $response = $this->actingAs($this->sales)->get(route('tasks.category.customer', [
            'category' => 'contract_verification',
            'customer' => Str::slug('PT Mitra Nusantara'),
        ]));

        $response->assertOk();
        // Table headers
        $response->assertSee('NOMOR KONTRAK');
        $response->assertSee('CATATAN VERIFIKASI');
        $response->assertSee('STATUS');
        $response->assertSee('SELESAI');
        $response->assertSee('KOMENTAR');
        // Row contents
        $response->assertSee('045/KTR/ICON/2026');
        $response->assertSee('046/KTR/ICON/2026');
        $response->assertSee('Dokumen Perjanjian belum di unggah');
        $response->assertSee('NPWP tidak sesuai dengan NPWP upload');
        $response->assertSee('Menunggu paraf direktur');
        $response->assertSee('chk-btn-'.$task1->id, false);
        $response->assertSee('comment-cell-'.$task2->id, false);
    }

    public function test_sales_overdue_workflow_and_admin_verification_approval(): void
    {
        $overdueTask = Task::factory()->create([
            'category' => Task::CATEGORY_CONTRACT_VERIFICATION,
            'document_number' => 'KTR-LATE-001',
            'customer_name' => 'PT Terlambat Verifikasi',
            'kp' => Task::KP_MADIUN,
            'sales_name' => 'Budi Santoso',
            'user_id' => $this->sales->id,
            'verification_note' => 'TANGGAL KONTRAK TIDAK SESUAI',
            'status' => Task::STATUS_IN_PROGRESS,
            'due_date' => Carbon::today()->subDays(3),
            'comments' => null,
        ]);

        // 1. Sales tries to check completed without comment -> rejected with 422
        $this->actingAs($this->sales)
            ->postJson(route('tasks.toggle-complete', $overdueTask), ['completed' => 1])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'requires_comment' => true,
            ]);

        // 2. Sales saves inline comment first, then checks completed
        $this->actingAs($this->sales)
            ->postJson(route('tasks.save-comment', $overdueTask), [
                'comments' => 'Revisi tanggal kontrak baru selesai ditandatangani pelanggan',
            ])
            ->assertOk();

        $this->actingAs($this->sales)
            ->postJson(route('tasks.toggle-complete', $overdueTask), ['completed' => 1])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'is_submitted' => true,
            ]);

        $overdueTask->refresh();
        $this->assertSame(Task::STATUS_SUBMITTED, $overdueTask->status);
        $this->assertSame('TANGGAL KONTRAK TIDAK SESUAI', $overdueTask->verification_note);
        $this->assertSame('Revisi tanggal kontrak baru selesai ditandatangani pelanggan', $overdueTask->comments);

        // 3. Admin sees it in /verifikasi and approves it
        $this->actingAs($this->admin)
            ->get(route('admin.verifikasi', ['category' => 'contract_verification']))
            ->assertOk()
            ->assertSee('KTR-LATE-001')
            ->assertSee('TANGGAL KONTRAK TIDAK SESUAI');

        $this->actingAs($this->admin)
            ->post(route('tasks.review', $overdueTask), [
                'action' => 'approve',
                'admin_notes' => 'Kontrak sudah sesuai.',
            ])
            ->assertRedirect(route('tasks.show', $overdueTask));

        $overdueTask->refresh();
        $this->assertSame(Task::STATUS_APPROVED, $overdueTask->status);
        $this->assertNotNull($overdueTask->confirmed_at);
    }

    public function test_import_contract_verification_via_sbu_mapper_and_status_resolution(): void
    {
        $mapper = app(SbuSheetMapper::class);

        $this->assertSame(Task::STATUS_APPROVED, $mapper->resolveStatusForCategory(Task::CATEGORY_CONTRACT_VERIFICATION, 'Done'));
        $this->assertSame(Task::STATUS_APPROVED, $mapper->resolveStatusForCategory(Task::CATEGORY_CONTRACT_VERIFICATION, 'Selesai'));
        $this->assertSame(Task::STATUS_APPROVED, $mapper->resolveStatusForCategory(Task::CATEGORY_CONTRACT_VERIFICATION, 'Sesuai'));
        $this->assertSame(Task::STATUS_IN_PROGRESS, $mapper->resolveStatusForCategory(Task::CATEGORY_CONTRACT_VERIFICATION, 'On Process'));
        $this->assertSame(Task::STATUS_PENDING, $mapper->resolveStatusForCategory(Task::CATEGORY_CONTRACT_VERIFICATION, ''));
        $this->assertSame(Task::STATUS_PENDING, $mapper->resolveStatusForCategory(Task::CATEGORY_CONTRACT_VERIFICATION, '-'));

        $csv = implode("\n", [
            'No,Nama Pelanggan,Nomor Kontrak,Catatan Verifikasi,KP,Sales,Deadline,Status',
            'KP JEMBER (2 data),,,,,,,',
            '1,RSUD dr Soebandi,KTR/JBR/2026/01,Dokumen Perjanjian belum di unggah,,Budi Santoso,2026-11-20,On Process',
            '2,Universitas Jember,KTR/JBR/2026/02,NPWP tidak sesuai dengan NPWP upload,Jember,Budi Santoso,2026-11-22,Done',
            'KP SURABAYA (1 data),,,,,,,',
            '3,PT Pelindo Surabaya,KTR/SBY/2026/03,TANGGAL KONTRAK TIDAK SESUAI,,Budi Santoso,2026-11-25,',
        ]);

        $file = UploadedFile::fake()->createWithContent('Verifikasi_Kontrak.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => Task::CATEGORY_CONTRACT_VERIFICATION,
        ]);

        $response->assertRedirect(route('tasks.category', 'contract_verification'));
        $response->assertSessionHas('success');

        $this->assertSame(3, Task::where('category', Task::CATEGORY_CONTRACT_VERIFICATION)->count());

        $t1 = Task::where('category', Task::CATEGORY_CONTRACT_VERIFICATION)->where('document_number', 'KTR/JBR/2026/01')->first();
        $this->assertNotNull($t1);
        $this->assertSame('RSUD dr Soebandi', $t1->customer_name);
        $this->assertSame('Dokumen Perjanjian belum di unggah', $t1->verification_note);
        $this->assertSame('jember', $t1->kp);
        $this->assertSame(Task::STATUS_IN_PROGRESS, $t1->status);
        $this->assertNull($t1->comments);

        $t2 = Task::where('category', Task::CATEGORY_CONTRACT_VERIFICATION)->where('document_number', 'KTR/JBR/2026/02')->first();
        $this->assertNotNull($t2);
        $this->assertSame('NPWP tidak sesuai dengan NPWP upload', $t2->verification_note);
        $this->assertSame(Task::STATUS_APPROVED, $t2->status);
        $this->assertNotNull($t2->confirmed_at);

        $t3 = Task::where('category', Task::CATEGORY_CONTRACT_VERIFICATION)->where('document_number', 'KTR/SBY/2026/03')->first();
        $this->assertNotNull($t3);
        $this->assertSame('surabaya', $t3->kp);
        $this->assertSame('TANGGAL KONTRAK TIDAK SESUAI', $t3->verification_note);
        $this->assertSame(Task::STATUS_PENDING, $t3->status);
    }

    public function test_import_contract_verification_with_send_back_filter_empty_rows_sales_not_and_summary(): void
    {
        $rows = [
            'NOMOR KONTRAK,NAMA PERUSAHAAN,CATATAN,KANTOR PERWAKILAN REVENUE,Sales,STATUS VERIFIKASI,Status',
            // 1. SEND BACK + On Process + customer with tabs/spaces
            "KTR-SB-001,\"\t  PT Maju Bersama \t \",Dokumen Perjanjian belum di unggah,KP SURABAYA,Budi Santoso,SEND BACK,On Process",
            // 2. SEND BACK + Done + Sales = Not
            'KTR-SB-002,CV Karya Mandiri,TANGGAL KONTRAK TIDAK SESUAI,KP MALANG,Not,SEND BACK,Done',
            // 3. SEND BACK + empty Status + customer = "-" + empty Sales
            'KTR-SB-003,-,NPWP tidak sesuai dengan NPWP upload,KP JEMBER,,SEND BACK,',
            // 4. APPROVED -> must be skipped (counted as dilewati APPROVED)
            'KTR-APP-001,PT Sudah Sesuai,-,KP SURABAYA,Budi Santoso,APPROVED,Done',
            'KTR-APP-002,PT Approved Dua,-,KP MADIUN,Not,APPROVED,',
            // 5. Invalid STATUS VERIFIKASI -> must be counted as failed with reason
            'KTR-ERR-001,PT Status Salah,Catatan cek,KP SURABAYA,Budi Santoso,HOLD,',
            // 6. Row with empty NOMOR KONTRAK -> must be skipped as empty (not error)
            ',PT Tanpa Nomor Kontrak,Catatan,KP SURABAYA,Budi Santoso,SEND BACK,',
        ];

        // Append 2,500 completely empty rows simulating Excel CSV trailing rows
        for ($i = 0; $i < 2500; $i++) {
            $rows[] = ',,,,,,';
        }

        $csv = implode("\n", $rows);
        $file = UploadedFile::fake()->createWithContent('Export_Verifikasi_Kontrak.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => Task::CATEGORY_CONTRACT_VERIFICATION,
        ]);

        $response->assertRedirect(route('tasks.category', 'contract_verification'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('import_summary');
        $response->assertSessionHas('import_errors');

        $summary = session('import_summary');
        // Total data rows after header: 7 explicit rows + 2500 empty rows = 2507
        $this->assertSame(2507, $summary['rows_read']);
        // 1 row with empty NOMOR KONTRAK + 2500 trailing empty rows = 2501 skipped empty
        $this->assertSame(2501, $summary['skipped_empty']);
        // 2 APPROVED rows
        $this->assertSame(2, $summary['skipped_approved']);
        // 3 SEND BACK rows imported
        $this->assertSame(3, $summary['imported']);
        // 1 failed row (STATUS VERIFIKASI = HOLD)
        $this->assertSame(1, $summary['failed']);
        $this->assertNotEmpty($summary['errors']);
        $this->assertStringContainsString('KTR-ERR-001', $summary['errors'][0]);
        $this->assertStringContainsString('HOLD', $summary['errors'][0]);

        $flashSuccess = (string) session('success');
        $this->assertStringContainsString('2507 baris dibaca', $flashSuccess);
        $this->assertStringContainsString('2501 dilewati (kosong)', $flashSuccess);
        $this->assertStringContainsString('2 dilewati (APPROVED)', $flashSuccess);
        $this->assertStringContainsString('3 berhasil diimpor', $flashSuccess);
        $this->assertStringContainsString('1 gagal', $flashSuccess);

        // Verify database records
        $this->assertSame(3, Task::where('category', Task::CATEGORY_CONTRACT_VERIFICATION)->count());
        $this->assertNull(Task::where('document_number', 'KTR-APP-001')->first());
        $this->assertNull(Task::where('document_number', 'KTR-APP-002')->first());

        // Row 1: trimmed customer_name, On Process -> in_progress
        $task1 = Task::where('document_number', 'KTR-SB-001')->firstOrFail();
        $this->assertSame('PT Maju Bersama', $task1->customer_name);
        $this->assertSame('Dokumen Perjanjian belum di unggah', $task1->verification_note);
        $this->assertSame(Task::KP_SURABAYA, $task1->kp);
        $this->assertSame('Budi Santoso', $task1->sales_name);
        $this->assertSame($this->sales->id, $task1->user_id);
        $this->assertSame(Task::STATUS_IN_PROGRESS, $task1->status);

        // Row 2: Sales = "Not" -> sales_name = null, user_id = null, Done -> approved
        $task2 = Task::where('document_number', 'KTR-SB-002')->firstOrFail();
        $this->assertSame('CV Karya Mandiri', $task2->customer_name);
        $this->assertSame(Task::KP_MALANG, $task2->kp);
        $this->assertNull($task2->sales_name);
        $this->assertNull($task2->user_id);
        $this->assertSame(Task::STATUS_APPROVED, $task2->status);

        // Row 3: NAMA PERUSAHAAN = "-" -> customer_name = null, Sales = "" -> sales_name = null, Status = "" -> pending
        $task3 = Task::where('document_number', 'KTR-SB-003')->firstOrFail();
        $this->assertNull($task3->customer_name);
        $this->assertSame('NPWP tidak sesuai dengan NPWP upload', $task3->verification_note);
        $this->assertSame(Task::KP_JEMBER, $task3->kp);
        $this->assertNull($task3->sales_name);
        $this->assertNull($task3->user_id);
        $this->assertSame(Task::STATUS_PENDING, $task3->status);

        // Verify "Belum ada PIC" is displayed on list and detail pages for tasks with no sales
        $listResponse = $this->actingAs($this->admin)->get(route('tasks.category', 'contract_verification'));
        $listResponse->assertOk();
        $listResponse->assertSee('Belum ada PIC');
        $listResponse->assertSee('CV Karya Mandiri');

        $detailResponse = $this->actingAs($this->admin)->get(route('tasks.category.customer', [
            'category' => 'contract_verification',
            'customer' => Str::slug('CV Karya Mandiri'),
        ]));
        $detailResponse->assertOk();
        $detailResponse->assertSee('Sales: Belum ada PIC');
    }

    public function test_whatsapp_report_includes_contract_verification_section(): void
    {
        $today = Carbon::now('Asia/Jakarta');

        // 1 Unfinished task
        Task::factory()->create([
            'category' => Task::CATEGORY_CONTRACT_VERIFICATION,
            'document_number' => 'KTR-WA-01',
            'customer_name' => 'PT Belum Selesai Kontrak',
            'sales_name' => 'Budi Santoso',
            'user_id' => $this->sales->id,
            'verification_note' => 'Dokumen Perjanjian belum di unggah',
            'comments' => 'Menunggu tanda tangan pelanggan',
            'status' => Task::STATUS_IN_PROGRESS,
        ]);

        // 1 Finished today task
        Task::factory()->create([
            'category' => Task::CATEGORY_CONTRACT_VERIFICATION,
            'document_number' => 'KTR-WA-02',
            'customer_name' => 'PT Selesai Hari Ini Kontrak',
            'sales_name' => 'Budi Santoso',
            'user_id' => $this->sales->id,
            'verification_note' => 'TANGGAL KONTRAK TIDAK SESUAI',
            'comments' => 'Sudah diperbaiki dan diupload ulang',
            'status' => Task::STATUS_APPROVED,
            'completed_at' => $today,
            'confirmed_at' => $today,
            'confirmed_by' => $this->admin->id,
        ]);

        /** @var WhatsAppReportService $service */
        $service = app(WhatsAppReportService::class);
        $report = $service->generateReport();

        $this->assertStringContainsString('📌 *5. VERIFIKASI KONTRAK*', $report);
        $this->assertStringContainsString('• *TOTAL KESELURUHAN* : 2 Pelanggan | 2 Kontrak', $report);
        $this->assertStringContainsString('• *SELESAI* : 1 Pelanggan | 1 Kontrak', $report);
        $this->assertStringContainsString('• *BELUM SELESAI* : 1 Pelanggan | 1 Kontrak', $report);
        $this->assertStringContainsString('PT Belum Selesai Kontrak', $report);
        $this->assertStringContainsString('Catatan: Dokumen Perjanjian belum di unggah | Kendala: Menunggu tanda tangan pelanggan', $report);
        $this->assertStringContainsString('PT Selesai Hari Ini Kontrak', $report);
        $this->assertStringContainsString('Catatan: TANGGAL KONTRAK TIDAK SESUAI | Progress: Sudah diperbaiki dan diupload ulang', $report);
    }

    public function test_import_contract_verification_takes_doc_number_from_nomor_kontrak_not_mitra_and_allows_dash_mitra(): void
    {
        // 1. Column order where NOMOR KONTRAK comes first, NOMOR KONTRAK MITRA has '-'
        // 2. Row with valid partner contract number
        // 3. Row where NOMOR KONTRAK MITRA has '-' and different contract numbers
        $csv = implode("\n", [
            'NOMOR KONTRAK,NOMOR KONTRAK MITRA,NAMA PERUSAHAAN,CATATAN,KANTOR PERWAKILAN REVENUE,Sales,STATUS VERIFIKASI,Status',
            'KTR-ICON-001,-,PT Pelanggan Satu,Dokumen belum lengkap,KP SURABAYA,Budi Santoso,SEND BACK,On Process',
            'KTR-ICON-002,MITRA-REF-999,PT Pelanggan Dua,Tanggal salah,KP MALANG,Budi Santoso,SEND BACK,On Process',
        ]);

        $file = UploadedFile::fake()->createWithContent('Verif_Kontrak_Mitra.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => Task::CATEGORY_CONTRACT_VERIFICATION,
        ]);

        $response->assertRedirect(route('tasks.category', 'contract_verification'));
        $response->assertSessionHas('success');

        $t1 = Task::where('category', Task::CATEGORY_CONTRACT_VERIFICATION)->where('document_number', 'KTR-ICON-001')->first();
        $this->assertNotNull($t1, 'Task with document_number KTR-ICON-001 must exist');
        $this->assertNull(Task::where('document_number', '-')->first());

        $t2 = Task::where('category', Task::CATEGORY_CONTRACT_VERIFICATION)->where('document_number', 'KTR-ICON-002')->first();
        $this->assertNotNull($t2, 'Task with document_number KTR-ICON-002 must exist');
        $this->assertSame('MITRA-REF-999', $t2->partner_contract_number);
    }

    public function test_import_contract_verification_handles_mitra_column_appearing_before_nomor_kontrak(): void
    {
        // NOMOR KONTRAK MITRA is Column A, NOMOR KONTRAK is Column B
        $csv = implode("\n", [
            'NOMOR KONTRAK MITRA,NOMOR KONTRAK,NAMA PERUSAHAAN,CATATAN,KANTOR PERWAKILAN REVENUE,Sales,STATUS VERIFIKASI,Status',
            '-,KTR-REV-101,PT Maju Bersama,Catatan verifikasi,KP JEMBER,Budi Santoso,SEND BACK,On Process',
            'MITRA-202,KTR-REV-102,PT Maju Jaya,Catatan verifikasi dua,KP MADIUN,Budi Santoso,SEND BACK,On Process',
        ]);

        $file = UploadedFile::fake()->createWithContent('Verif_Kontrak_Mitra_First.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => Task::CATEGORY_CONTRACT_VERIFICATION,
        ]);

        $response->assertRedirect(route('tasks.category', 'contract_verification'));
        $response->assertSessionHas('success');

        $this->assertNotNull(Task::where('document_number', 'KTR-REV-101')->first());
        $this->assertNotNull(Task::where('document_number', 'KTR-REV-102')->first());
        $this->assertNull(Task::where('document_number', 'MITRA-202')->first());
        $this->assertNull(Task::where('document_number', '-')->first());
    }

    public function test_import_contract_verification_dash_validation_applies_only_when_nomor_kontrak_itself_is_dash(): void
    {
        $csv = implode("\n", [
            'NOMOR KONTRAK,NOMOR KONTRAK MITRA,NAMA PERUSAHAAN,CATATAN,KANTOR PERWAKILAN REVENUE,Sales,STATUS VERIFIKASI,Status',
            // Valid NOMOR KONTRAK with '-' in mitra -> must NOT fail
            'KTR-VALID-01,-,PT Aman,Catatan oke,KP SURABAYA,Budi Santoso,SEND BACK,On Process',
            // NOMOR KONTRAK itself is '-' -> must FAIL with invalid contract number error
            '-,MITRA-VALID,PT Tidak Valid,Catatan error,KP SURABAYA,Budi Santoso,SEND BACK,On Process',
        ]);

        $file = UploadedFile::fake()->createWithContent('Verif_Kontrak_Dash_Check.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => Task::CATEGORY_CONTRACT_VERIFICATION,
        ]);

        $response->assertRedirect(route('tasks.category', 'contract_verification'));
        $summary = session('import_summary');

        $this->assertSame(1, $summary['imported']);
        $this->assertSame(1, $summary['failed']);
        $this->assertStringContainsString("Nomor Kontrak '-' tidak valid", $summary['errors'][0]);
    }

    public function test_import_contract_verification_duplicate_check_uses_nomor_kontrak_not_mitra(): void
    {
        $csv = implode("\n", [
            'NOMOR KONTRAK,NOMOR KONTRAK MITRA,NAMA PERUSAHAAN,CATATAN,KANTOR PERWAKILAN REVENUE,Sales,STATUS VERIFIKASI,Status',
            // Row 1 & 2: Different NOMOR KONTRAK, but both have '-' in Mitra column -> NOT duplicates!
            'KTR-DUP-001,-,PT Satu,Catatan satu,KP SURABAYA,Budi Santoso,SEND BACK,On Process',
            'KTR-DUP-002,-,PT Dua,Catatan dua,KP SURABAYA,Budi Santoso,SEND BACK,On Process',
            // Row 3: Same NOMOR KONTRAK as Row 1 -> IS a duplicate!
            'KTR-DUP-001,-,PT Satu Duplicate,Catatan duplikat,KP SURABAYA,Budi Santoso,SEND BACK,On Process',
        ]);

        $file = UploadedFile::fake()->createWithContent('Verif_Kontrak_Duplicates.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => Task::CATEGORY_CONTRACT_VERIFICATION,
        ]);

        $response->assertRedirect(route('tasks.category', 'contract_verification'));
        $summary = session('import_summary');

        // 2 unique rows imported, 1 duplicate skipped
        $this->assertSame(2, $summary['imported']);
        $this->assertSame(1, $summary['duplicate']);
        $this->assertSame(1, $summary['skipped_duplicate']);
        $this->assertSame(0, $summary['failed']);

        $this->assertSame(1, Task::where('document_number', 'KTR-DUP-001')->count());
        $this->assertSame(1, Task::where('document_number', 'KTR-DUP-002')->count());
    }

    public function test_import_contract_verification_skips_approved_rows_before_contract_validation(): void
    {
        $csv = implode("\n", [
            'NOMOR KONTRAK,NOMOR KONTRAK MITRA,NAMA PERUSAHAAN,CATATAN,KANTOR PERWAKILAN REVENUE,Sales,STATUS VERIFIKASI,Status',
            // 1. APPROVED row with '-' as NOMOR KONTRAK -> must be skipped as APPROVED, not treated as error
            '-,MITRA-APP-1,PT Approved Dash,Catatan,KP SURABAYA,Budi Santoso,APPROVED,Done',
            // 2. APPROVED row with empty NOMOR KONTRAK -> must be skipped as APPROVED
            ',MITRA-APP-2,PT Approved Kosong,Catatan,KP SURABAYA,Budi Santoso,APPROVED,Done',
            // 3. APPROVED row with valid NOMOR KONTRAK -> must be skipped as APPROVED
            'KTR-APP-OK,-,PT Approved Biasa,Catatan,KP SURABAYA,Budi Santoso,APPROVED,Done',
            // 4. SEND BACK row with valid NOMOR KONTRAK -> imported successfully
            'KTR-SB-OK,-,PT Sendback Sukses,Catatan,KP SURABAYA,Budi Santoso,SEND BACK,On Process',
        ]);

        $file = UploadedFile::fake()->createWithContent('Verif_Kontrak_Approved_First.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => Task::CATEGORY_CONTRACT_VERIFICATION,
        ]);

        $response->assertRedirect(route('tasks.category', 'contract_verification'));
        $summary = session('import_summary');

        // All 3 APPROVED rows are skipped as APPROVED, none appear as errors
        $this->assertSame(3, $summary['skipped_approved']);
        $this->assertSame(1, $summary['imported']);
        $this->assertSame(0, $summary['failed']);
        $this->assertEmpty($summary['errors']);

        $flashSuccess = (string) session('success');
        $this->assertStringContainsString('3 dilewati (APPROVED)', $flashSuccess);
        $this->assertStringContainsString('0 gagal', $flashSuccess);
    }

    public function test_import_contract_verification_summary_displays_all_six_metrics(): void
    {
        $csv = implode("\n", [
            'NOMOR KONTRAK,NOMOR KONTRAK MITRA,NAMA PERUSAHAAN,CATATAN,KANTOR PERWAKILAN REVENUE,Sales,STATUS VERIFIKASI,Status',
            // 1. Berhasil baru
            'KTR-SUM-001,-,PT Sukses Satu,Catatan satu,KP SURABAYA,Budi Santoso,SEND BACK,On Process',
            // 2. Dilewati (APPROVED) - even if contract number is empty
            ',-,PT Approved,Catatan approved,KP MALANG,Budi Santoso,APPROVED,Done',
            // 3. Dilewati (kosong) - empty contract and not approved
            ',-,PT Kosong Kontrak,Catatan kosong,KP JEMBER,Budi Santoso,SEND BACK,On Process',
            // 4. Duplikat (skip)
            'KTR-SUM-001,-,PT Sukses Duplicate,Catatan duplikat,KP SURABAYA,Budi Santoso,SEND BACK,On Process',
            // 5. Gagal - STATUS VERIFIKASI invalid
            'KTR-SUM-ERR,-,PT Gagal Status,Catatan gagal,KP MADIUN,Budi Santoso,INVALID_STATUS,On Process',
        ]);

        $file = UploadedFile::fake()->createWithContent('Verif_Kontrak_Summary_Check.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => Task::CATEGORY_CONTRACT_VERIFICATION,
        ]);

        $response->assertRedirect(route('tasks.category', 'contract_verification'));
        $flashSuccess = (string) session('success');
        $summary = session('import_summary');

        // Check metrics array
        $this->assertSame(5, $summary['rows_read']);
        $this->assertSame(1, $summary['skipped_empty']);
        $this->assertSame(1, $summary['skipped_approved']);
        $this->assertSame(1, $summary['imported']);
        $this->assertSame(1, $summary['duplicate']);
        $this->assertSame(1, $summary['failed']);

        // Check flash message text format: dibaca, dilewati (kosong), dilewati (APPROVED), berhasil, duplikat, gagal
        $this->assertStringContainsString('5 baris dibaca', $flashSuccess);
        $this->assertStringContainsString('1 dilewati (kosong)', $flashSuccess);
        $this->assertStringContainsString('1 dilewati (APPROVED)', $flashSuccess);
        $this->assertStringContainsString('1 berhasil diimpor', $flashSuccess);
        $this->assertStringContainsString('1 duplikat', $flashSuccess);
        $this->assertStringContainsString('1 gagal', $flashSuccess);
    }

    public function test_import_contract_verification_skips_footer_legend_notes_without_status_verifikasi(): void
    {
        $csv = implode("\n", [
            'NOMOR KONTRAK,NOMOR KONTRAK MITRA,NAMA PERUSAHAAN,CATATAN,KANTOR PERWAKILAN REVENUE,Sales,STATUS VERIFIKASI,Status',
            // 1. Valid contract row
            'KTR-NOTE-001,-,PT Sukses Verifikasi,Catatan valid,KP SURABAYA,Budi Santoso,SEND BACK,On Process',
            // 2. Explanatory footnote rows with empty STATUS VERIFIKASI (like SBU Excel legends)
            'Permasalahan utama dapat dikelompokkan ke dalam empat kategori berikut:,,,,,,,',
            '1. Ketidaksesuaian Data (Paling Sering Muncul),,,,,,,',
            'Nomor & Tanggal Kontrak: Tidak tercantum di dokumen salah ketik atau berbeda antara fisik dokumen dengan input di sistem.,,,,,,,',
            'Identitas Para Pihak: Nama perusahaan nama Pihak Pertama Pihak Kedua tidak sesuai.,,,,,,,',
            '2. Kekurangan Dokumen Fisik,,,,,,,',
            'Secara keseluruhan prioritas perbaikan ada pada penyesuaian data identitas,,,,,,,',
        ]);

        $file = UploadedFile::fake()->createWithContent('Verif_Kontrak_Footnotes.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => Task::CATEGORY_CONTRACT_VERIFICATION,
        ]);

        $response->assertRedirect(route('tasks.category', 'contract_verification'));
        $summary = session('import_summary');

        // 1 contract imported, 6 footnote rows skipped as empty, 0 failures!
        $this->assertSame(7, $summary['rows_read']);
        $this->assertSame(1, $summary['imported']);
        $this->assertSame(6, $summary['skipped_empty']);
        $this->assertSame(0, $summary['failed']);
        $this->assertEmpty($summary['errors']);
    }
}
