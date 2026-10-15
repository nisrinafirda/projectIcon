<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class IconTaskFeatureTest extends TestCase
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
            'email' => 'karyawan@icon.co.id',
            'role' => 'user',
            'nip' => 'NIP101',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('login'));
    }

    public function test_user_can_save_comment_and_comment_is_visible_to_admin(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'customer_name' => 'PT MAJU JAYA BAA',
            'status' => Task::STATUS_APPROVED,
            'user_id' => $this->user->id,
            'comments' => null,
        ]);

        $this->actingAs($this->user)
            ->postJson(route('tasks.save-comment', $task), ['comments' => 'Sudah dikonfirmasi pelanggan'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('Sudah dikonfirmasi pelanggan', $task->fresh()->comments);

        $customerDetailUrl = route('tasks.category.customer', ['category' => 'baa', 'customer' => 'pt-maju-jaya-baa']);

        $this->actingAs($this->admin)
            ->get($customerDetailUrl)
            ->assertOk()
            ->assertSee('Sudah dikonfirmasi pelanggan');
    }

    public function test_user_cannot_comment_on_another_users_task(): void
    {
        $task = Task::factory()->create([
            'user_id' => User::factory()->create(['role' => 'user'])->id,
        ]);

        $this->actingAs($this->user)
            ->postJson(route('tasks.save-comment', $task), ['comments' => 'Tidak boleh'])
            ->assertForbidden();
    }

    public function test_overdue_task_toggle_requires_comment(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'PELANGGAN OVERDUE',
            'status' => Task::STATUS_IN_PROGRESS,
            'due_date' => now()->subDays(5),
            'user_id' => $this->user->id,
            'comments' => null,
        ]);

        // Attempting to check without comment returns 422
        $this->actingAs($this->user)
            ->postJson(route('tasks.toggle-complete', $task), ['completed' => 1])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'requires_reason' => true,
            ]);

        // Providing comment in request allows checking
        $this->actingAs($this->user)
            ->postJson(route('tasks.toggle-complete', $task), [
                'completed' => 1,
                'comments' => 'Kendala teknis pelanggan',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'is_submitted' => true,
            ]);

        $this->assertSame(Task::STATUS_SUBMITTED, $task->fresh()->status);
        $this->assertSame('Kendala teknis pelanggan', $task->fresh()->comments);
        $this->assertSame('Kendala teknis pelanggan', $task->fresh()->late_reason);
        $this->assertTrue($task->fresh()->was_late);
    }

    public function test_user_can_login_and_see_user_dashboard(): void
    {
        $response = $this->post('/login', [
            'username' => $this->user->username,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));

        $dashboardResponse = $this->actingAs($this->user)->get(route('dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee("Halo, {$this->user->name}!");
        $dashboardResponse->assertSee('Total Tugas Anda');
        $dashboardResponse->assertSee('SO Open');
        $dashboardResponse->assertSee('BAA');
        $dashboardResponse->assertSee('BAI');
    }

    public function test_admin_sees_admin_dashboard_with_category_monitoring(): void
    {
        // Create 3 BAA tasks due tomorrow
        Task::factory()->count(3)->create([
            'category' => Task::CATEGORY_BAA,
            'status' => Task::STATUS_PENDING,
            'due_date' => now()->addDay(),
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Dashboard Monitoring Keseluruhan Tugas');
        $response->assertSee('3 tugas');
        $response->assertSee('deadline besok');
    }

    public function test_prevent_duplicate_task_creation_in_same_category(): void
    {
        Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'document_number' => 'BAA-DUPLICATE-001',
            'user_id' => $this->user->id,
        ]);

        // Attempt to create another task with identical category + document_number
        $response = $this->actingAs($this->admin)->post(route('tasks.store'), [
            'category' => Task::CATEGORY_BAA,
            'document_number' => 'BAA-DUPLICATE-001',
            'title' => 'Tugas Uji Coba Duplikat',
            'user_id' => $this->user->id,
            'priority' => 'medium',
            'due_date' => now()->addDay()->toDateString(),
        ]);

        $response->assertSessionHasErrors('document_number');
        $this->assertEquals(1, Task::where('category', Task::CATEGORY_BAA)->where('document_number', 'BAA-DUPLICATE-001')->count());
    }

    public function test_check_duplicate_api_endpoint(): void
    {
        Task::factory()->create([
            'category' => Task::CATEGORY_BAI,
            'document_number' => 'BAI-CHECK-01',
            'user_id' => $this->user->id,
        ]);

        // Querying existing doc number
        $responseExisting = $this->actingAs($this->admin)->getJson('/api/tasks/check-duplicate?category=bai&document_number=BAI-CHECK-01');
        $responseExisting->assertStatus(200);
        $responseExisting->assertJson(['exists' => true]);

        // Querying non-existing doc number
        $responseNew = $this->actingAs($this->admin)->getJson('/api/tasks/check-duplicate?category=bai&document_number=BAI-NEW-99');
        $responseNew->assertStatus(200);
        $responseNew->assertJson(['exists' => false]);
    }

    public function test_user_can_submit_task(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'user_id' => $this->user->id,
            'status' => Task::STATUS_IN_PROGRESS,
        ]);

        $response = $this->actingAs($this->user)->post(route('tasks.submit', $task), [
            'submission_notes' => 'Pekerjaan telah selesai diuji dan normal.',
        ]);

        $response->assertRedirect(route('tasks.show', $task));

        $task->refresh();
        $this->assertEquals(Task::STATUS_SUBMITTED, $task->status);
        $this->assertEquals('Pekerjaan telah selesai diuji dan normal.', $task->submission_notes);
    }

    public function test_admin_can_approve_submitted_task(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'user_id' => $this->user->id,
            'status' => Task::STATUS_SUBMITTED,
        ]);

        $response = $this->actingAs($this->admin)->post(route('tasks.review', $task), [
            'action' => 'approve',
            'admin_notes' => 'Disetujui, hasil kerja bagus.',
        ]);

        $response->assertRedirect(route('tasks.show', $task));

        $task->refresh();
        $this->assertEquals(Task::STATUS_APPROVED, $task->status);
        $this->assertNotNull($task->reviewed_at);
    }

    public function test_admin_can_reject_submitted_task(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_KONTRAK_EXP,
            'user_id' => $this->user->id,
            'status' => Task::STATUS_SUBMITTED,
        ]);

        $response = $this->actingAs($this->admin)->post(route('tasks.review', $task), [
            'action' => 'reject',
            'admin_notes' => 'Tolong lengkapi lampiran tanda tangan.',
        ]);

        $response->assertRedirect(route('tasks.show', $task));

        $task->refresh();
        $this->assertEquals(Task::STATUS_REJECTED, $task->status);
        $this->assertEquals('Tolong lengkapi lampiran tanda tangan.', $task->admin_notes);
    }

    public function test_export_excel(): void
    {
        Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'user_id' => $this->user->id,
            'kp' => Task::KP_SURABAYA,
            'sales_name' => 'Sales Demo',
            'comments' => 'Ada kendala kabel',
        ]);

        Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'user_id' => $this->user->id,
            'kp' => Task::KP_MALANG,
            'rp_exception' => 50000000,
        ]);

        $excelResponse = $this->actingAs($this->user)->get(route('tasks.export.excel'));
        $excelResponse->assertStatus(200);

        // Test export Excel with category=exception filter
        $excelExceptionResponse = $this->actingAs($this->user)->get(route('tasks.export.excel', ['category' => 'exception']));
        $excelExceptionResponse->assertStatus(200);
    }

    public function test_tasks_index_shows_export_excel_and_laporan_update_for_admin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('tasks.index'));
        $response->assertStatus(200);
        $response->assertSee('Export Excel');
        $response->assertSee('Laporan Update');
        $response->assertDontSee('Export PDF');
    }

    public function test_whatsapp_report_access_and_format(): void
    {
        // Setup specific tasks for testing status markers
        // 1. Task unconfirmed (submitted): Customer A
        Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'customer_name' => 'PT SELESAI BELUM VERIFIKASI',
            'status' => Task::STATUS_SUBMITTED,
            'completed_at' => now(),
        ]);

        // 2. Task confirmed (approved with confirmed_at): Customer B
        Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'customer_name' => 'PT SELESAI SUDAH TERKONFIRMASI',
            'status' => Task::STATUS_APPROVED,
            'completed_at' => now(),
            'reviewed_at' => now(),
            'confirmed_at' => now(),
            'confirmed_by' => $this->admin->id,
        ]);

        // 3. Task unfinished (in_progress): Customer C
        Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'customer_name' => 'PT BELUM SELESAI',
            'status' => Task::STATUS_IN_PROGRESS,
        ]);

        // 1. Sales user cannot access whatsapp report
        $salesResponse = $this->actingAs($this->user)->get(route('admin.report.whatsapp'));
        $salesResponse->assertStatus(403);

        // 2. Admin can access whatsapp report page
        $adminResponse = $this->actingAs($this->admin)->get(route('admin.report.whatsapp'));
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Laporan Update Project Monitoring PPB');
        $adminResponse->assertSee('Salin Laporan WhatsApp');

        // 3. Admin can fetch report via JSON (AJAX)
        $ajaxResponse = $this->actingAs($this->admin)->getJson(route('admin.report.whatsapp'));
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertJsonStructure(['success', 'report']);

        $reportText = $ajaxResponse->json('report');
        $this->assertStringContainsString('UPDATE PROJECT MONITORING PPB', $reportText);
        $this->assertStringContainsString('*Publik*', $reportText);
        $this->assertStringContainsString('*PLN*', $reportText);
        $this->assertStringContainsString('-Status BAA Open', $reportText);
        $this->assertStringContainsString('-Status SO Open', $reportText);
        $this->assertStringContainsString('📌 *1. EXCEPTION (STATUS: EXCEPTION OPEN)*', $reportText);
        $this->assertStringContainsString('📌 *2. SO OPEN*', $reportText);
        $this->assertStringContainsString('📌 *3. BAA OPEN*', $reportText);
        $this->assertStringContainsString('📌 *4. CONTRACT EXPIRED*', $reportText);

        // Check header time and timezone
        $this->assertStringContainsString('Jam:', $reportText);
        $this->assertStringContainsString('WIB', $reportText);

        // Check legend Note
        $this->assertStringContainsString('Note :', $reportText);
        $this->assertStringContainsString('❌ = Belum selesai', $reportText);
        $this->assertStringContainsString('✅ = Sudah selesai', $reportText);
        $this->assertStringContainsString('☑️ = Terkonfirmasi', $reportText);

        // Check markers in BAA section (marker at end of customer header line)
        // Customer with unconfirmed completed task has ✅
        $this->assertMatchesRegularExpression('/\*PT SELESAI BELUM VERIFIKASI\* \| 1 ID PA ✅/', $reportText);
        $this->assertDoesNotMatchRegularExpression('/\*PT SELESAI BELUM VERIFIKASI\* \| 1 ID PA ✅☑️/', $reportText);

        // Customer with confirmed completed task has ✅☑️
        $this->assertMatchesRegularExpression('/\*PT SELESAI SUDAH TERKONFIRMASI\* \| 1 ID PA ✅☑️/', $reportText);

        // Customer with unfinished task has ❌
        $this->assertMatchesRegularExpression('/\*PT BELUM SELESAI\* \| 1 ID PA ❌/', $reportText);
    }

    public function test_whatsapp_report_all_sections_split_completed_and_multi_sid_progress(): void
    {
        // 1. BAA: Customer with 1 unfinished and 2 finished SIDs
        Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'customer_name' => 'PT BAA SEBAGIAN',
            'status' => Task::STATUS_SUBMITTED,
        ]);
        Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'customer_name' => 'PT BAA SEBAGIAN',
            'status' => Task::STATUS_APPROVED,
        ]);
        Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'customer_name' => 'PT BAA SEBAGIAN',
            'status' => Task::STATUS_IN_PROGRESS,
        ]);

        // 2. Exception: 1 finished confirmed, 1 finished submitted, 1 partial customer
        Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'PT EXC SUDAH KONFIRMASI',
            'rp_exception' => 50000000,
            'status' => Task::STATUS_APPROVED,
            'confirmed_at' => now(),
            'confirmed_by' => $this->admin->id,
        ]);
        Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'PT EXC BELUM KONFIRMASI',
            'rp_exception' => 30000000,
            'status' => Task::STATUS_SUBMITTED,
        ]);
        Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'PT EXC SEBAGIAN',
            'rp_exception' => 10000000,
            'status' => Task::STATUS_SUBMITTED,
        ]);
        Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'PT EXC SEBAGIAN',
            'rp_exception' => 20000000,
            'status' => Task::STATUS_IN_PROGRESS,
        ]);

        // 3. Contract Expired: 1 finished confirmed, 1 partial customer with multiple dates
        Task::factory()->create([
            'category' => Task::CATEGORY_KONTRAK_EXP,
            'customer_name' => 'PT KONTRAK SELESAI',
            'due_date' => now()->addDays(5),
            'status' => Task::STATUS_APPROVED,
            'confirmed_at' => now(),
            'confirmed_by' => $this->admin->id,
        ]);
        Task::factory()->create([
            'category' => Task::CATEGORY_KONTRAK_EXP,
            'customer_name' => 'PT KONTRAK SEBAGIAN',
            'due_date' => now()->addDays(10),
            'status' => Task::STATUS_SUBMITTED,
        ]);
        Task::factory()->create([
            'category' => Task::CATEGORY_KONTRAK_EXP,
            'customer_name' => 'PT KONTRAK SEBAGIAN',
            'due_date' => now()->addDays(15),
            'status' => Task::STATUS_IN_PROGRESS,
        ]);

        $ajaxResponse = $this->actingAs($this->admin)->getJson(route('admin.report.whatsapp'));
        $ajaxResponse->assertStatus(200);
        $report = $ajaxResponse->json('report');

        // Check BAA: SIDs split by status
        $this->assertStringContainsString('*PT BAA SEBAGIAN* | 1 ID PA ❌', $report);
        $this->assertStringContainsString('*PT BAA SEBAGIAN* | 2 ID PA ✅', $report);

        // Check Exception split and PIC formatting
        $this->assertStringContainsString('📌 *1. EXCEPTION (STATUS: EXCEPTION OPEN)*', $report);
        $this->assertStringContainsString('*📊 REKAPITULASI*', $report);
        $this->assertStringContainsString('• *BELUM SELESAI*', $report);
        $this->assertStringContainsString('• *SELESAI*', $report);
        $this->assertStringContainsString('*TOTAL KESELURUHAN', $report);
        $this->assertMatchesRegularExpression('/\*PT EXC SUDAH KONFIRMASI\* \| PIC: [^✅\n]+ ✅☑️/', $report);
        $this->assertMatchesRegularExpression('/\*PT EXC BELUM KONFIRMASI\* \| PIC: [^✅\n]+ ✅/', $report);
        $this->assertMatchesRegularExpression('/\*PT EXC SEBAGIAN\* \| PIC: [^❌\n]+ ❌/', $report);

        // Check Contract Expired split and combined dates
        $this->assertStringContainsString('📌 *4. CONTRACT EXPIRED*', $report);
        $this->assertMatchesRegularExpression('/\*PT KONTRAK SELESAI\* \| Exp: \d{2}\/\d{2}\/\d{4} ✅☑️/', $report);
        $this->assertMatchesRegularExpression('/\*PT KONTRAK SEBAGIAN\* \| Exp: \d{2}\/\d{2}\/\d{4} ❌/', $report);
    }

    public function test_admin_can_update_report_settings_and_see_reflected_in_report(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('admin.report.whatsapp.settings'), [
            'opportunity_publik' => 500000000,
            'revenue_as_is_publik' => 250000000,
            'opportunity_pln' => 1000000000,
            'revenue_as_is_pln' => 750000000,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'settings' => [
                'opportunity_publik' => 500000000,
                'revenue_as_is_publik' => 250000000,
                'opportunity_pln' => 1000000000,
                'revenue_as_is_pln' => 750000000,
            ],
        ]);

        $report = $response->json('report');
        $this->assertStringContainsString('-Status Opportunity Rp 500.000.000', $report);
        $this->assertStringContainsString('-Revenue AS IS Rp 250.000.000', $report);
        $this->assertStringContainsString('-Status Opportunity Rp 1.000.000.000', $report);
        $this->assertStringContainsString('-Revenue AS IS Rp 750.000.000', $report);
    }

    public function test_admin_dashboard_shows_kategori_and_kp_charts_and_no_verifikasi_queue(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Diagram Per Kategori');
        $response->assertSee('Diagram Per KP');
        $response->assertDontSee('Antrean Verifikasi Tugas Masuk');
        $response->assertSee('PLN');
        $response->assertSee('Publik');
        $response->assertSee('Surabaya');
        $response->assertSee('Malang');
    }

    public function test_verifikasi_page_accessible_by_admin(): void
    {
        Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'user_id' => $this->user->id,
            'status' => Task::STATUS_SUBMITTED,
            'title' => 'Tugas Menunggu Verifikasi',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.verifikasi'));
        $response->assertStatus(200);
        $response->assertSee('Verifikasi Tugas');
        $response->assertSee('Tugas Menunggu Verifikasi');
    }

    public function test_verifikasi_page_forbidden_for_regular_user(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.verifikasi'));
        $response->assertStatus(403);
    }

    public function test_tasks_filtered_by_kp_and_kategori_segmen(): void
    {
        $task1 = Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'user_id' => $this->user->id,
            'title' => 'Tugas PLN Surabaya Khusus',
            'service_type' => null,
            'kp' => Task::KP_SURABAYA,
            'kategori_segmen' => Task::SEGMEN_PLN,
        ]);

        $task2 = Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'user_id' => $this->user->id,
            'title' => 'Tugas Publik Malang Khusus',
            'service_type' => null,
            'kp' => Task::KP_MALANG,
            'kategori_segmen' => Task::SEGMEN_PUBLIK,
        ]);

        $response = $this->actingAs($this->admin)->get(route('tasks.index', [
            'kp' => Task::KP_SURABAYA,
            'kategori_segmen' => Task::SEGMEN_PLN,
        ]));

        $response->assertStatus(200);
        $response->assertSee($task1->customer_name);
        $response->assertDontSee($task2->customer_name);
    }

    public function test_so_open_page_shows_custom_metrics_and_kp_breakdown(): void
    {
        Task::factory()->create([
            'category' => Task::CATEGORY_SO_OPEN,
            'user_id' => $this->user->id,
            'title' => 'SO Open Task 1',
            'kp' => Task::KP_SURABAYA,
        ]);

        $response = $this->actingAs($this->admin)->get(route('tasks.category', 'sso_open'));
        $response->assertStatus(200);
        $response->assertSee('SO Open');
        $response->assertSee('TOTAL PROYEK SO Open');
        $response->assertSee('TOTAL NILAI BARU');
        $response->assertDontSee('NET SELISIH / DELTA');
        $response->assertSee('STATUS SELESAI (DONE)');
        $response->assertSee('ON PROCESS / AKTIF');
        $response->assertSee('Nilai Proyek Baru:');
        $response->assertSee('KP Surabaya');
        $response->assertSee('KP Malang');
        $response->assertSee('KP Madiun');
        $response->assertSee('KP Jember');
    }

    public function test_kontrak_exp_page_hides_nilai_baru_and_net_delta_metrics(): void
    {
        Task::factory()->create([
            'category' => Task::CATEGORY_KONTRAK_EXP,
            'user_id' => $this->user->id,
            'title' => 'Renewal Kontrak Test',
            'kp' => Task::KP_SURABAYA,
        ]);

        $response = $this->actingAs($this->admin)->get(route('tasks.category', 'kontrak_exp'));
        $response->assertStatus(200);
        $response->assertSee('Kontrak Exp');
        $response->assertSee('TOTAL PROYEK Kontrak Exp');
        $response->assertSee('STATUS SELESAI (DONE)');
        $response->assertSee('ON PROCESS / AKTIF');
        $response->assertDontSee('TOTAL NILAI BARU');
        $response->assertDontSee('NET SELISIH / DELTA');
        $response->assertDontSee('Nilai Proyek Baru:');
    }

    public function test_admin_can_access_create_and_edit_task_pages(): void
    {
        $createResponse = $this->actingAs($this->admin)->get(route('tasks.create'));
        $createResponse->assertStatus(200);
        $createResponse->assertSee('Beri Tugas Baru');
        $createResponse->assertSee('Kantor Perwakilan (KP)');

        $task = Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'user_id' => $this->user->id,
            'kp' => Task::KP_MALANG,
            'kategori_segmen' => Task::SEGMEN_PUBLIK,
        ]);

        $editResponse = $this->actingAs($this->admin)->get(route('tasks.edit', $task));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Edit Tugas');
        $editResponse->assertSee('Kantor Perwakilan (KP)');
    }

    public function test_import_tasks_automatically_assigns_per_employee_name(): void
    {
        $employee1 = User::factory()->create([
            'name' => 'Siti Rahma',
            'email' => 'siti.rahma@icon.co.id',
            'nip' => 'NIP-SITI-01',
            'role' => 'user',
        ]);

        $employee2 = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi.santoso@icon.co.id',
            'nip' => 'NIP-BUDI-02',
            'role' => 'user',
        ]);

        $csv = "Kategori,Nomor Dokumen,Judul Tugas,PIC,Pelanggan,Layanan,Prioritas,Mulai,Deadline,Keterangan\n";
        $csv .= "sso_open,SO-NAME-101,Tugas Khusus Siti,Siti Rahma,PT ABC,Metronet,high,2026-09-17,2026-09-20,Catatan 1\n";
        $csv .= "sso_open,SO-NAME-102,Tugas Khusus Budi,Budi Santoso,PT XYZ,IP Transit,urgent,2026-09-17,2026-09-20,Catatan 2\n";

        $file = UploadedFile::fake()->createWithContent('import_per_name.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'sso_open',
        ]);

        $response->assertRedirect(route('tasks.category', 'sso_open'));
        $response->assertSessionHas('success');

        $task1 = Task::where('document_number', 'SO-NAME-101')->first();
        $this->assertNotNull($task1);
        $this->assertEquals($employee1->id, $task1->user_id);
        $this->assertEquals('sso_open', $task1->category);

        $task2 = Task::where('document_number', 'SO-NAME-102')->first();
        $this->assertNotNull($task2);
        $this->assertEquals($employee2->id, $task2->user_id);
        $this->assertEquals('sso_open', $task2->category);
    }

    public function test_category_page_has_direct_import_link_and_import_view_loads(): void
    {
        $response = $this->actingAs($this->admin)->get(route('tasks.category', 'sso_open'));
        $response->assertStatus(200);
        $response->assertSee(route('tasks.import.view', ['category' => 'sso_open']));
        $response->assertSee('Import SO Open');

        $importViewResponse = $this->actingAs($this->admin)->get(route('tasks.import.view', ['category' => 'sso_open']));
        $importViewResponse->assertStatus(200);
        $importViewResponse->assertSee('Mode Import Khusus: SO Open');
        $importViewResponse->assertSee('📌 Khusus Modul SO Open');
    }

    public function test_import_sbu_monitoring_sheet_format_automatically_maps_fields(): void
    {
        $salesUser = User::factory()->create([
            'name' => 'Yeni Primahapsari',
            'email' => 'yeni@icon.co.id',
            'role' => 'user',
        ]);

        $csv = "ID PA,NAMA PELANGGAN,LAYANAN PRODUK,HARGA LAMA,HARGA BARU,SELISIH,Kategori Customer,KP,Sales,Tanggal Upload BAI,Target,Status\n";
        $csv .= "A142204001215,UNIVERSITAS MUHAMMADIYAH MALANG,PRODUK DIGITAL - EV CHARGER,0,5795000,5795000,PUBLIK,Malang,YENI PRIMAHAPSARI,2026-08-21,7,On Process\n";

        $file = UploadedFile::fake()->createWithContent('sbu_baa_open.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'baa',
        ]);

        $response->assertRedirect(route('tasks.category', 'baa'));
        $response->assertSessionHas('success');

        $task = Task::where('document_number', 'A142204001215')->first();
        $this->assertNotNull($task);
        $this->assertEquals('baa', $task->category);
        $this->assertEquals($salesUser->id, $task->user_id);
        $this->assertEquals('UNIVERSITAS MUHAMMADIYAH MALANG', $task->customer_name);
        $this->assertEquals('malang', $task->kp);
        $this->assertEquals('publik', $task->kategori_segmen);
        $this->assertEquals('pending', $task->status);
        $this->assertEquals('[Status Excel: On Process]', $task->submission_notes);
        $this->assertStringContainsString('Harga Baru: Rp 5.795.000', $task->description);
    }

    public function test_import_similarity_matching_rules(): void
    {
        $targetUser = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi.santoso@icon.co.id',
            'role' => 'user',
        ]);

        // Row 1: "Buddi Santoso" -> >= 90% similarity -> auto-assigned to $targetUser
        // Row 2: "Buddi Santoz" -> 70% - 89% similarity -> fallback to admin, warnings generated
        // Row 3: "Zulfaqar Siregar" -> < 70% similarity -> fallback to admin, no warning
        $csv = "Kategori,Nomor Dokumen,Judul Tugas,PIC,Pelanggan,Layanan,Prioritas,Mulai,Deadline,Keterangan\n";
        $csv .= "sso_open,SO-SIM-90,Tugas Sangat Mirip,Buddi Santoso,PT ABC,Metronet,high,2026-09-17,2026-09-20,Catatan 1\n";
        $csv .= "sso_open,SO-SIM-75,Tugas Agak Mirip,Buddi Santoz,PT XYZ,IP Transit,medium,2026-09-17,2026-09-20,Catatan 2\n";
        $csv .= "sso_open,SO-SIM-00,Tugas Tidak Mirip,Zulfaqar Siregar,PT DEF,IP Transit,low,2026-09-17,2026-09-20,Catatan 3\n";

        $file = UploadedFile::fake()->createWithContent('import_similarity.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'sso_open',
        ]);

        $response->assertRedirect(route('tasks.category', 'sso_open'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('import_warnings');

        $warnings = session('import_warnings');
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('Perlu Konfirmasi', $warnings[0]);
        $this->assertStringContainsString('Buddi Santoz', $warnings[0]);
        $this->assertStringContainsString('Budi Santoso', $warnings[0]);

        // Task 1: Auto assigned to Budi Santoso
        $task1 = Task::where('document_number', 'SO-SIM-90')->first();
        $this->assertNotNull($task1);
        $this->assertEquals($targetUser->id, $task1->user_id);
        $this->assertEquals('Buddi Santoso', $task1->sales_name);

        // Task 2: Assigned to admin (fallback), sales_name preserved
        $task2 = Task::where('document_number', 'SO-SIM-75')->first();
        $this->assertNotNull($task2);
        $this->assertEquals($this->admin->id, $task2->user_id);
        $this->assertEquals('Buddi Santoz', $task2->sales_name);

        // Task 3: Assigned to admin (fallback), sales_name preserved, not in warnings
        $task3 = Task::where('document_number', 'SO-SIM-00')->first();
        $this->assertNotNull($task3);
        $this->assertEquals($this->admin->id, $task3->user_id);
        $this->assertEquals('Zulfaqar Siregar', $task3->sales_name);
    }

    public function test_exception_category_calculates_total_rp_across_all_statuses_even_with_status_filter(): void
    {
        // 1 Approved task with Rp 750.000.000
        Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'BADAN PENDAPATAN DAERAH PROVINSI JAWA TIMUR',
            'status' => Task::STATUS_APPROVED,
            'rp_exception' => 750000000.00,
            'document_number' => 'EXC-BAPENDA-APP',
        ]);

        // 1 In Progress task with Rp 0
        Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'BADAN PENDAPATAN DAERAH PROVINSI JAWA TIMUR',
            'status' => Task::STATUS_IN_PROGRESS,
            'rp_exception' => 0.00,
            'document_number' => 'EXC-BAPENDA-PROG',
        ]);

        // Visit exception category with status=in_progress filter
        $response = $this->actingAs($this->admin)->get(route('tasks.category', [
            'category' => 'exception',
            'status' => 'in_progress',
        ]));

        $response->assertStatus(200);
        // The total on the customer row should still show Rp 750.000.000 (total of all tasks for that customer)
        $response->assertSee('Rp 750.000.000');
        // Accordion details row should NOT be rendered for exception; customer row links to separate page
        $response->assertDontSee('id="details-group-0"', false);
        $response->assertSee(route('tasks.exception.customer', 'badan-pendapatan-daerah-provinsi-jawa-timur'));
    }

    public function test_exception_category_links_to_customer_detail_page_without_inline_tasks(): void
    {
        Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'BADAN PENDAPATAN DAERAH PROVINSI JAWA TIMUR',
            'status' => Task::STATUS_APPROVED,
            'rp_exception' => 750000000.00,
            'document_number' => 'EXC-BAPENDA-APP-1',
        ]);

        Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'BADAN PENDAPATAN DAERAH PROVINSI JAWA TIMUR',
            'status' => Task::STATUS_IN_PROGRESS,
            'rp_exception' => 0.00,
            'document_number' => '141601002040 / 2026-09',
            'service_type' => 'MANAGE SERVICE ROUTER BRONZE',
        ]);

        $response = $this->actingAs($this->admin)->get(route('tasks.category', 'exception'));

        $response->assertStatus(200);
        $response->assertSee('BADAN PENDAPATAN DAERAH PROVINSI JAWA TIMUR');
        $response->assertSee('Rp 750.000.000');
        // Child tasks must NOT be rendered inline on the outer exception list
        $response->assertDontSee('141601002040 / 2026-09');
        $response->assertDontSee('MANAGE SERVICE ROUTER BRONZE');
        $response->assertDontSee('id="details-group-0"', false);
    }

    public function test_exception_customer_detail_page_loads_successfully(): void
    {
        Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'BADAN PENDAPATAN DAERAH KABUPATEN MALANG',
            'status' => Task::STATUS_IN_PROGRESS,
            'rp_exception' => 12000000.00,
            'document_number' => '110130308825 / 2026-10',
            'service_type' => 'INTERNET CORPORATE',
        ]);

        $response = $this->actingAs($this->admin)->get(route('tasks.exception.customer', 'badan-pendapatan-daerah-kabupaten-malang'));

        $response->assertStatus(200);
        $response->assertSee('BADAN PENDAPATAN DAERAH KABUPATEN MALANG');
        $response->assertSee('110130308825 / 2026-10');
        $response->assertSee('INTERNET CORPORATE');
        $response->assertSee('Rp 12.000.000');
        $response->assertSee('Kembali ke Daftar Exception');
        $response->assertSee('Per halaman:');
    }

    public function test_exception_customer_detail_page_respects_sales_authorization(): void
    {
        $salesUser = User::factory()->create(['role' => 'user']);
        $otherUser = User::factory()->create(['role' => 'user']);

        Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'DINAS KESEHATAN KABUPATEN MALANG',
            'user_id' => $salesUser->id,
            'document_number' => 'SID-SALES-1',
            'service_type' => 'METRONET 100 MBPS',
        ]);

        Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'DINAS KESEHATAN KABUPATEN MALANG',
            'user_id' => $otherUser->id,
            'document_number' => 'SID-OTHER-2',
            'service_type' => 'ASTINET 50 MBPS',
        ]);

        $response = $this->actingAs($salesUser)->get(route('tasks.exception.customer', 'dinas-kesehatan-kabupaten-malang'));

        $response->assertStatus(200);
        $response->assertSee('SID-SALES-1');
        $response->assertDontSee('SID-OTHER-2');
    }

    public function test_exception_pagination_and_per_page_options(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            Task::factory()->create([
                'category' => Task::CATEGORY_EXCEPTION,
                'customer_name' => 'PELANGGAN BESAR EXCEPTION',
                'document_number' => sprintf('SID-MULTI-%02d', $i),
                'due_date' => now()->addDays($i),
            ]);
        }

        // Test with valid per_page=50
        $response = $this->actingAs($this->admin)->get(route('tasks.exception.customer', [
            'customer' => 'pelanggan-besar-exception',
            'per_page' => 50,
        ]));
        $response->assertStatus(200);
        $response->assertSee('SID-MULTI-01');
        $response->assertSee('SID-MULTI-30');

        // Test with invalid per_page=999 (should fallback to 25)
        $response2 = $this->actingAs($this->admin)->get(route('tasks.exception.customer', [
            'customer' => 'pelanggan-besar-exception',
            'per_page' => 999,
        ]));
        $response2->assertStatus(200);
        $response2->assertSee('Menampilkan 1 - 25 dari 30 data');
        $response2->assertSee('SID-MULTI-01');
        // Page 1 with per_page 25 (default fallback) should not show task 30 (which is on page 2 due to due_date asc ordering)
        $response2->assertDontSee('SID-MULTI-30');
    }

    public function test_kontrak_exp_renders_accordion_grouped_by_customer_with_contract_badges(): void
    {
        $task1 = Task::factory()->create([
            'category' => Task::CATEGORY_KONTRAK_EXP,
            'customer_name' => 'PT. PLN PUSAT PENDIDIKAN',
            'document_number' => '112204001451',
            'description' => "No. Kontrak: 0081.PJ/HKM.02.01/F29000000/2025\nKendala: Menunggu approval",
            'service_type' => 'PRODUK DIGITAL - ZOOM',
            'sales_name' => 'FIRMANDA KHOLIQ',
            'status' => Task::STATUS_IN_PROGRESS,
            'due_date' => now()->addDays(5),
            'kp' => Task::KP_SURABAYA,
            'kategori_segmen' => Task::SEGMEN_PLN,
        ]);

        $task2 = Task::factory()->create([
            'category' => Task::CATEGORY_KONTRAK_EXP,
            'customer_name' => 'PT. PLN PUSAT PENDIDIKAN',
            'document_number' => '112204001452',
            'description' => "No. Kontrak: 0082.PJ/HKM.02.01/F29000000/2025\nKendala: Proses adendum",
            'service_type' => 'METRONET 50 MBPS',
            'sales_name' => 'FIRMANDA KHOLIQ',
            'status' => Task::STATUS_IN_PROGRESS,
            'due_date' => now()->addDays(8),
            'kp' => Task::KP_SURABAYA,
            'kategori_segmen' => Task::SEGMEN_PLN,
        ]);

        $response = $this->actingAs($this->admin)->get(route('tasks.category', 'kontrak_exp'));

        $response->assertStatus(200);
        // Header contains No Kontrak column for parent customer row
        $response->assertSee('No Kontrak');
        // Parent row shows first contract and "+1 lainnya" badge
        $response->assertSee('PT. PLN PUSAT PENDIDIKAN');
        $response->assertSee('0081.PJ/HKM.02.01/F29000000/2025');
        $response->assertSee('+1 lainnya');
        $response->assertSee('FIRMANDA KHOLIQ');
        $response->assertSee('Rincian');

        // Banner "Daftar Layanan / SID" is removed from parent table
        $response->assertDontSee('Daftar Layanan / SID');

        // Customer detail URL is present in the parent row
        $detailUrl = route('tasks.category.customer', ['category' => 'kontrak_exp', 'customer' => 'pt-pln-pusat-pendidikan']);
        $response->assertSee($detailUrl);

        // Accessing customer detail page displays all SID details
        $detailResponse = $this->actingAs($this->admin)->get($detailUrl);
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('PT. PLN PUSAT PENDIDIKAN');
        $detailResponse->assertSee('NO KONTRAK / ID PA');
        $detailResponse->assertSee('0081.PJ/HKM.02.01/F29000000/2025');
        $detailResponse->assertSee('0082.PJ/HKM.02.01/F29000000/2025');
        $detailResponse->assertSee('112204001451');
        $detailResponse->assertSee('112204001452');
        $detailResponse->assertSee('PRODUK DIGITAL - ZOOM');
        $detailResponse->assertSee('METRONET 50 MBPS');

        // Links to task detail and edit are present in customer detail page
        $detailResponse->assertSee(route('tasks.show', $task1));
        $detailResponse->assertSee(route('tasks.edit', $task1));
    }

    public function test_kontrak_exp_search_by_contract_number_and_customer(): void
    {
        $task1 = Task::factory()->create([
            'category' => Task::CATEGORY_KONTRAK_EXP,
            'customer_name' => 'DINAS KOMINFO MOJOKERTO',
            'document_number' => '140000001158',
            'description' => "No. Kontrak: AGR/2210/2678\nKendala: Deaktivasi",
            'status' => Task::STATUS_IN_PROGRESS,
        ]);

        $task2 = Task::factory()->create([
            'category' => Task::CATEGORY_KONTRAK_EXP,
            'customer_name' => 'PT. PLN NUSANTARA POWER',
            'document_number' => '140130300481',
            'description' => "No. Kontrak: 0811123/PJ/10203/SURABAYA\nKendala: Review",
            'status' => Task::STATUS_IN_PROGRESS,
        ]);

        // Search by contract number
        $response1 = $this->actingAs($this->admin)->get(route('tasks.category', [
            'category' => 'kontrak_exp',
            'search' => 'AGR/2210/2678',
        ]));
        $response1->assertStatus(200);
        $response1->assertSee('DINAS KOMINFO MOJOKERTO');
        $response1->assertDontSee('PT. PLN NUSANTARA POWER');

        // Search by customer name
        $response2 = $this->actingAs($this->admin)->get(route('tasks.category', [
            'category' => 'kontrak_exp',
            'search' => 'NUSANTARA POWER',
        ]));
        $response2->assertStatus(200);
        $response2->assertSee('PT. PLN NUSANTARA POWER');
        $response2->assertDontSee('DINAS KOMINFO MOJOKERTO');
    }

    public function test_harga_baru_column_is_displayed_only_for_so_open_category(): void
    {
        $soTask = Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'customer_name' => 'PT SO OPEN TEST',
            'sales_name' => 'SALES AGENT ONE',
            'harga_baru' => 15000000,
            'user_id' => $this->user->id,
        ]);

        $baaTask = Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'customer_name' => 'PT BAA TEST',
            'sales_name' => 'SALES AGENT TWO',
            'harga_baru' => 20000000,
            'user_id' => $this->user->id,
        ]);

        // Access SO Open category
        $responseSo = $this->actingAs($this->admin)->get(route('tasks.category', 'sso_open'));
        $responseSo->assertOk();
        $responseSo->assertSee('Harga Baru');
        $responseSo->assertSee('Rp 15.000.000');

        // Access BAA category - should NOT see Harga Baru column
        $responseBaa = $this->actingAs($this->admin)->get(route('tasks.category', 'baa'));
        $responseBaa->assertOk();
        $responseBaa->assertDontSee('Harga Baru');
    }

    public function test_admin_dashboard_shows_diagram_per_kp_and_breakdown_cards(): void
    {
        // Surabaya: 1 BAA, 2 EXCEPTION
        Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'kp' => 'surabaya',
            'user_id' => $this->user->id,
        ]);
        Task::factory()->count(2)->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'kp' => 'surabaya',
            'user_id' => $this->user->id,
        ]);

        // Malang: 1 SSO OPEN
        Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'kp' => 'malang',
            'user_id' => $this->user->id,
        ]);

        // Madiun and Jember have 0 tasks

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();

        // Check header and cards
        $response->assertSee('Diagram Per KP');
        $response->assertSee('Surabaya');
        $response->assertSee('3 tugas');
        $response->assertSee('Malang');
        $response->assertSee('1 tugas');
        $response->assertSee('Madiun');
        $response->assertSee('0 tugas');
        $response->assertSee('Jember');

        // Check category links in Surabaya card
        $expectedSurabayaBaaUrl = route('tasks.category', ['category' => 'baa', 'kp' => 'surabaya']);
        $expectedSurabayaExceptionUrl = route('tasks.category', ['category' => 'exception', 'kp' => 'surabaya']);
        $response->assertSee($expectedSurabayaBaaUrl, false);
        $response->assertSee($expectedSurabayaExceptionUrl, false);

        // Clicking the filtered link works and filters by KP
        $filterResponse = $this->actingAs($this->admin)->get($expectedSurabayaBaaUrl);
        $filterResponse->assertOk();
    }

    public function test_tasks_index_renders_one_row_per_customer_and_links_to_customer_detail_page(): void
    {
        $task1 = Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'customer_name' => 'BADAN PENDAPATAN DAERAH JAWA TIMUR',
            'document_number' => 'SID-ALL-001',
            'service_type' => 'METRONET 100 MBPS',
            'sales_name' => 'AHMAD SALES',
            'user_id' => $this->user->id,
            'kp' => Task::KP_SURABAYA,
        ]);

        $task2 = Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'customer_name' => 'BADAN PENDAPATAN DAERAH JAWA TIMUR',
            'document_number' => 'SID-ALL-002',
            'service_type' => 'ASTINET 50 MBPS',
            'sales_name' => 'AHMAD SALES',
            'user_id' => $this->user->id,
            'kp' => Task::KP_SURABAYA,
        ]);

        $response = $this->actingAs($this->admin)->get(route('tasks.index'));
        $response->assertOk();

        // Customer row is rendered with customer name and KP
        $response->assertSee('BADAN PENDAPATAN DAERAH JAWA TIMUR');
        $response->assertSee('KP Surabaya');
        $response->assertSee('AHMAD SALES');

        // Customer row links to customer detail page
        $expectedCustomerUrl = route('tasks.customer', 'badan-pendapatan-daerah-jawa-timur');
        $response->assertSee($expectedCustomerUrl);

        // Child accordion rows and inner SID table must NOT be rendered on main /tasks
        $response->assertDontSee('id="details-group-0"', false);
        $response->assertDontSee('SID-ALL-001');
        $response->assertDontSee('SID-ALL-002');
    }

    public function test_tasks_customer_detail_page_loads_with_sids_and_sidebar_active(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'customer_name' => 'PT INDONESIA POWER',
            'document_number' => 'SID-IP-999',
            'service_type' => 'INTERNET DEDICATED',
            'sales_name' => 'BUDI SALES',
            'status' => Task::STATUS_IN_PROGRESS,
            'user_id' => $this->user->id,
            'comments' => 'Sedang proses instalasi',
        ]);

        $response = $this->actingAs($this->admin)->get(route('tasks.customer', 'pt-indonesia-power'));
        $response->assertOk();

        // Title and customer heading
        $response->assertSee('PT INDONESIA POWER');
        $response->assertSee('Kembali ke Semua Tugas');

        // Table content
        $response->assertSee('SID-IP-999');
        $response->assertSee('INTERNET DEDICATED');
        $response->assertSee('BUDI SALES');
        $response->assertSee('Sedang Dikerjakan');
        $response->assertSee('Sedang proses instalasi');
        $response->assertSee(route('tasks.show', $task));
        $response->assertSee(route('tasks.edit', $task));

        // Sidebar marks "Semua Tugas" as active
        $response->assertSee('bg-blue-600 text-white shadow-md shadow-blue-600/30 font-semibold', false);
    }

    public function test_tasks_customer_detail_page_respects_sales_authorization(): void
    {
        $salesUser = User::factory()->create(['role' => 'user']);
        $otherUser = User::factory()->create(['role' => 'user']);

        Task::factory()->create([
            'category' => Task::CATEGORY_BAI,
            'customer_name' => 'SEKOLAH TINGGI TEKNIK',
            'document_number' => 'SID-MY-TASK',
            'user_id' => $salesUser->id,
        ]);

        Task::factory()->create([
            'category' => Task::CATEGORY_BAI,
            'customer_name' => 'SEKOLAH TINGGI TEKNIK',
            'document_number' => 'SID-OTHER-TASK',
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($salesUser)->get(route('tasks.customer', 'sekolah-tinggi-teknik'));
        $response->assertOk();
        $response->assertSee('SID-MY-TASK');
        $response->assertDontSee('SID-OTHER-TASK');

        // Accessing customer that has no tasks for this user aborts with 404
        $otherCustomerTask = Task::factory()->create([
            'category' => Task::CATEGORY_BAI,
            'customer_name' => 'PELANGGAN ORANG LAIN',
            'document_number' => 'SID-PRIVATE-1',
            'user_id' => $otherUser->id,
        ]);

        $forbiddenResponse = $this->actingAs($salesUser)->get(route('tasks.customer', 'pelanggan-orang-lain'));
        $forbiddenResponse->assertStatus(404);
    }

    public function test_category_customer_detail_page_works_for_all_four_categories_and_respects_permissions(): void
    {
        $salesUser = User::factory()->create(['role' => 'karyawan']);
        $otherUser = User::factory()->create(['role' => 'karyawan']);

        $categories = [
            'sso_open' => [
                'name' => 'PT SINAR SURYA ABADI',
                'slug' => 'pt-sinar-surya-abadi',
                'doc' => 'SID-SSO-001',
                'harga' => 12500000,
            ],
            'baa' => [
                'name' => 'PT BERKAH AGUNG ANUGERAH',
                'slug' => 'pt-berkah-agung-anugerah',
                'doc' => 'SID-BAA-002',
            ],
            'bai' => [
                'name' => 'PT BINA AGRO INDUSTRI',
                'slug' => 'pt-bina-agro-industri',
                'doc' => 'SID-BAI-003',
            ],
            'kontrak_exp' => [
                'name' => 'PT KONTRAK EXP NUSANTARA',
                'slug' => 'pt-kontrak-exp-nusantara',
                'doc' => 'SID-KEXP-004',
                'nomor_kontrak' => '0099.PJ/HKM/2026',
            ],
        ];

        foreach ($categories as $catKey => $catData) {
            $taskData = [
                'category' => $catKey,
                'customer_name' => $catData['name'],
                'document_number' => $catData['doc'],
                'service_type' => 'INTERNET DEDICATED',
                'user_id' => $salesUser->id,
            ];
            if ($catKey === 'sso_open') {
                $taskData['harga_baru'] = $catData['harga'];
            }
            if ($catKey === 'kontrak_exp') {
                $taskData['description'] = 'No. Kontrak: '.$catData['nomor_kontrak']."\nKendala: Proses perpanjangan";
            }
            Task::factory()->create($taskData);

            // Other user's task for the same customer
            Task::factory()->create([
                'category' => $catKey,
                'customer_name' => $catData['name'],
                'document_number' => 'SID-OTHER-'.$catKey,
                'user_id' => $otherUser->id,
            ]);

            // Parent category page has link to category customer detail
            $listResponse = $this->actingAs($this->admin)->get(route('tasks.category', $catKey));
            $listResponse->assertOk();
            $customerDetailUrl = route('tasks.category.customer', ['category' => $catKey, 'customer' => $catData['slug']]);
            $listResponse->assertSee($customerDetailUrl);

            // Admin accesses category customer detail
            $detailResponse = $this->actingAs($this->admin)->get($customerDetailUrl);
            $detailResponse->assertOk();
            $detailResponse->assertSee($catData['name']);
            $detailResponse->assertSee($catData['doc']);
            $detailResponse->assertSee('SID-OTHER-'.$catKey);
            $detailResponse->assertSee(route('tasks.category', $catKey));

            if ($catKey === 'sso_open') {
                $detailResponse->assertSee('HARGA BARU');
                $detailResponse->assertSee('Rp 12.500.000');
            }
            if ($catKey === 'kontrak_exp') {
                $detailResponse->assertSee('NO KONTRAK / ID PA');
                $detailResponse->assertSee($catData['nomor_kontrak']);
            }

            // Sales user only sees their own tasks in category customer detail
            $salesDetailResponse = $this->actingAs($salesUser)->get($customerDetailUrl);
            $salesDetailResponse->assertOk();
            $salesDetailResponse->assertSee($catData['doc']);
            $salesDetailResponse->assertDontSee('SID-OTHER-'.$catKey);
        }
    }

    public function test_month_filter_works_on_index_all_categories_and_customer_detail_and_excel_export(): void
    {
        $salesUser = User::factory()->create(['role' => 'karyawan']);
        $otherSales = User::factory()->create(['role' => 'karyawan']);

        // Task with deadline in 2024-12
        $taskOld = Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'customer_name' => 'PT OLD CUSTOMER 2024',
            'document_number' => 'SID-OLD-2024',
            'due_date' => Carbon::create(2024, 12, 15),
            'harga_baru' => 5000000,
            'user_id' => $salesUser->id,
        ]);

        // Task with deadline in 2026-10
        $taskCurrent = Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'customer_name' => 'PT CURRENT CUSTOMER 2026',
            'document_number' => 'SID-NEW-2026',
            'due_date' => Carbon::create(2026, 10, 20),
            'harga_baru' => 10000000,
            'user_id' => $salesUser->id,
        ]);

        // Another task for OLD customer in 2026-10
        $taskOldCustomerNewSid = Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'customer_name' => 'PT OLD CUSTOMER 2024',
            'document_number' => 'SID-OLD-NEW-2026',
            'due_date' => Carbon::create(2026, 10, 25),
            'harga_baru' => 8000000,
            'user_id' => $salesUser->id,
        ]);

        // Task belonging to other sales
        $taskOtherSales = Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'customer_name' => 'PT OTHER SALES 2024',
            'document_number' => 'SID-OTHER-2024',
            'due_date' => Carbon::create(2024, 12, 10),
            'harga_baru' => 3000000,
            'user_id' => $otherSales->id,
        ]);

        // 1. All tasks /tasks filtered by month=12 and year=2024
        $respAll = $this->actingAs($this->admin)->get(route('tasks.index', ['month' => 12, 'year' => 2024]));
        $respAll->assertOk();
        $respAll->assertSee('PT OLD CUSTOMER 2024');
        $respAll->assertSee('Desember 2024'); // Button label
        $respAll->assertDontSee('PT CURRENT CUSTOMER 2026'); // Not in 2024-12

        // Nearest deadline and total on outer row reflect ONLY month 12 year 2024
        $respAll->assertSee('15/12/2024');
        $respAll->assertDontSee('25/10/2026');

        // 1b. Filter by month only (month=10 across all years)
        $respMonthOnly = $this->actingAs($this->admin)->get(route('tasks.index', ['month' => 10]));
        $respMonthOnly->assertOk();
        $respMonthOnly->assertSee('PT CURRENT CUSTOMER 2026');
        $respMonthOnly->assertSee('PT OLD CUSTOMER 2024'); // has a task in Oct 2026
        $respMonthOnly->assertSee('Oktober');

        // 1c. Filter by year only ("Seluruh Tahun" 2026)
        $respYearOnly = $this->actingAs($this->admin)->get(route('tasks.index', ['year' => 2026]));
        $respYearOnly->assertOk();
        $respYearOnly->assertSee('Tahun 2026'); // Button label
        $respYearOnly->assertSee('PT CURRENT CUSTOMER 2026');

        // 1d. Empty state when month has no tasks
        $respEmpty = $this->actingAs($this->admin)->get(route('tasks.index', ['month' => 1, 'year' => 2020]));
        $respEmpty->assertOk();
        $respEmpty->assertSee('Tidak ada tugas pada bulan ini.');

        // 2. SO Open category page filtered by month=12 and year=2024
        $respCategory = $this->actingAs($this->admin)->get(route('tasks.category', ['category' => 'sso_open', 'month' => 12, 'year' => 2024]));
        $respCategory->assertOk();
        $respCategory->assertSee('PT OLD CUSTOMER 2024');
        $respCategory->assertDontSee('PT CURRENT CUSTOMER 2026');
        // Total Harga Baru for PT OLD CUSTOMER 2024 should be 5.000.000 (not including 8.000.000 from October)
        $respCategory->assertSee('Rp 5.000.000');

        // 3. Customer detail page forward month & year query string
        $customerDetailUrl = route('tasks.category.customer', [
            'category' => 'sso_open',
            'customer' => 'pt-old-customer-2024',
            'month' => 12,
            'year' => 2024,
        ]);
        $respDetail = $this->actingAs($this->admin)->get($customerDetailUrl);
        $respDetail->assertOk();
        $respDetail->assertSee('SID-OLD-2024');
        $respDetail->assertDontSee('SID-OLD-NEW-2026'); // Filtered out by month/year
        $respDetail->assertSee('Tampilkan semua bulan'); // Link to reset month & year

        // 4. Resetting month on customer detail shows both SIDs
        $respDetailAllMonths = $this->actingAs($this->admin)->get(route('tasks.category.customer', [
            'category' => 'sso_open',
            'customer' => 'pt-old-customer-2024',
        ]));
        $respDetailAllMonths->assertOk();
        $respDetailAllMonths->assertSee('SID-OLD-2024');
        $respDetailAllMonths->assertSee('SID-OLD-NEW-2026');

        // 5. Sales scoping: Sales cannot see other sales' customer
        $respSalesFiltered = $this->actingAs($salesUser)->get(route('tasks.category', ['category' => 'sso_open', 'month' => 12, 'year' => 2024]));
        $respSalesFiltered->assertSee('PT OLD CUSTOMER 2024');
        $respSalesFiltered->assertDontSee('PT OTHER SALES 2024');

        // 6. Excel export follows month and year filter
        $excelExportResponse = $this->actingAs($this->admin)->get(route('tasks.export.excel', [
            'category' => 'sso_open',
            'month' => 12,
            'year' => 2024,
        ]));
        $excelExportResponse->assertOk();
        $excelExportResponse->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_user_can_mark_non_overdue_task_as_done_without_comment(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'user_id' => $this->user->id,
            'status' => Task::STATUS_IN_PROGRESS,
            'due_date' => now()->addDays(5),
            'comments' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('tasks.toggle-complete', $task));

        $response->assertSessionHas('success');

        $task->refresh();
        $this->assertSame(Task::STATUS_SUBMITTED, $task->status);
        $this->assertNotNull($task->completed_at);
        $this->assertNotNull($task->done_marked_at);
        $this->assertSame($this->user->id, $task->done_marked_by);
        $this->assertFalse($task->was_late);
    }

    public function test_user_cannot_mark_overdue_task_as_done_without_late_reason(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'user_id' => $this->user->id,
            'status' => Task::STATUS_IN_PROGRESS,
            'due_date' => now()->subDays(3),
            'comments' => null,
        ]);

        // HTML form POST without late reason
        $response = $this->actingAs($this->user)
            ->post(route('tasks.toggle-complete', $task), [
                'late_reason' => '',
            ]);

        $response->assertSessionHasErrors(['late_reason']);
        $task->refresh();
        $this->assertSame(Task::STATUS_IN_PROGRESS, $task->status);

        // JSON POST with spaces only
        $jsonResp = $this->actingAs($this->user)
            ->postJson(route('tasks.toggle-complete', $task), [
                'late_reason' => '     ',
            ]);

        $jsonResp->assertStatus(422);
        $jsonResp->assertJsonValidationErrors(['late_reason']);
        $task->refresh();
        $this->assertSame(Task::STATUS_IN_PROGRESS, $task->status);
    }

    public function test_user_can_mark_overdue_task_as_done_with_late_reason_and_enters_verification(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'user_id' => $this->user->id,
            'status' => Task::STATUS_IN_PROGRESS,
            'due_date' => now()->subDays(2),
            'comments' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('tasks.toggle-complete', $task), [
                'late_reason' => 'Menunggu persetujuan diskon vendor',
            ]);

        $response->assertSessionHas('success');

        $task->refresh();
        $this->assertSame(Task::STATUS_SUBMITTED, $task->status);
        $this->assertTrue($task->was_late);
        $this->assertSame('Menunggu persetujuan diskon vendor', $task->late_reason);
        $this->assertSame('Menunggu persetujuan diskon vendor', $task->comments);
        $this->assertSame($this->user->id, $task->done_marked_by);
        $this->assertNotNull($task->done_marked_at);

        // Appears in Admin Verifikasi list
        $verifResponse = $this->actingAs($this->admin)->get(route('admin.verifikasi'));
        $verifResponse->assertOk();
        $verifResponse->assertSee('Menunggu persetujuan diskon vendor');
    }

    public function test_user_can_uncheck_submitted_task_reverting_to_in_progress(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_BAI,
            'user_id' => $this->user->id,
            'status' => Task::STATUS_SUBMITTED,
            'completed_at' => now(),
            'done_marked_at' => now(),
            'done_marked_by' => $this->user->id,
            'comments' => 'Catatan penting tetap ada',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('tasks.toggle-complete', $task), ['uncheck' => 1]);

        $response->assertSessionHas('success');

        $task->refresh();
        $this->assertSame(Task::STATUS_IN_PROGRESS, $task->status);
        $this->assertNull($task->completed_at);
        $this->assertNull($task->done_marked_at);
        $this->assertNull($task->done_marked_by);
        // Comments are preserved
        $this->assertSame('Catatan penting tetap ada', $task->comments);
    }

    public function test_admin_confirms_task_in_verification_locks_task_from_user(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_KONTRAK_EXP,
            'user_id' => $this->user->id,
            'status' => Task::STATUS_SUBMITTED,
            'completed_at' => now(),
            'done_marked_at' => now(),
            'done_marked_by' => $this->user->id,
        ]);

        // Admin confirms task
        $response = $this->actingAs($this->admin)
            ->post(route('tasks.review', $task), [
                'action' => 'approve',
                'admin_notes' => 'Tugas terverifikasi lengkap',
            ]);

        $response->assertSessionHas('success');

        $task->refresh();
        $this->assertSame(Task::STATUS_APPROVED, $task->status);
        $this->assertNotNull($task->confirmed_at);
        $this->assertSame($this->admin->id, $task->confirmed_by);
        $this->assertNotNull($task->reviewed_at);

        // User cannot toggle or modify approved task
        $userAttempt = $this->actingAs($this->user)
            ->post(route('tasks.toggle-complete', $task));

        $userAttempt->assertStatus(403);
    }

    public function test_admin_returns_task_to_employee_for_revision(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'user_id' => $this->user->id,
            'status' => Task::STATUS_SUBMITTED,
            'completed_at' => now(),
            'done_marked_at' => now(),
            'done_marked_by' => $this->user->id,
            'comments' => 'Hasil pengerjaan',
        ]);

        // Admin returns task with notes
        $response = $this->actingAs($this->admin)
            ->post(route('tasks.review', $task), [
                'action' => 'reject',
                'admin_notes' => 'Mohon cek kembali lampiran',
            ]);

        $response->assertSessionHas('success');

        $task->refresh();
        $this->assertSame(Task::STATUS_REJECTED, $task->status);
        $this->assertNull($task->completed_at);
        $this->assertNull($task->done_marked_at);
        $this->assertNull($task->done_marked_by);
        $this->assertSame('Mohon cek kembali lampiran', $task->admin_notes);
        // User comments remain preserved
        $this->assertSame('Hasil pengerjaan', $task->comments);

        // Employee can fix and re-toggle
        $recheckResp = $this->actingAs($this->user)
            ->post(route('tasks.toggle-complete', $task));

        $recheckResp->assertSessionHas('success');
        $task->refresh();
        $this->assertSame(Task::STATUS_SUBMITTED, $task->status);
    }

    public function test_user_cannot_mark_task_assigned_to_another_user(): void
    {
        $otherUser = User::factory()->create(['role' => 'user']);
        $task = Task::factory()->create([
            'user_id' => $otherUser->id,
            'status' => Task::STATUS_IN_PROGRESS,
            'due_date' => now()->addDays(2),
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('tasks.toggle-complete', $task));

        $response->assertStatus(403);
    }

    public function test_admin_cannot_toggle_via_employee_checkbox_endpoint(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => Task::STATUS_IN_PROGRESS,
            'due_date' => now()->addDays(2),
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('tasks.toggle-complete', $task));

        $response->assertStatus(403);
    }

    public function test_table_views_contain_selesai_column_for_user_and_hidden_for_admin(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'customer_name' => 'PT CHECKBOX TEST',
            'user_id' => $this->user->id,
            'status' => Task::STATUS_IN_PROGRESS,
            'due_date' => now()->addDays(5),
            'document_number' => 'SID-CHK-1',
        ]);

        // User sees "SELESAI" column header
        $userView = $this->actingAs($this->user)
            ->get(route('tasks.customer', ['customer' => 'pt-checkbox-test']));
        $userView->assertOk();
        $userView->assertSee('SELESAI');
        $userView->assertSee('toggleCompleteAjax('.$task->id.', false)', false);
        $userView->assertSee('openCommentEdit('.$task->id.')', false);

        // Admin does NOT see "SELESAI" checkbox column
        $adminView = $this->actingAs($this->admin)
            ->get(route('tasks.customer', ['customer' => 'pt-checkbox-test']));
        $adminView->assertOk();
        $adminView->assertDontSee('>SELESAI<', false);
    }

    public function test_employee_inline_comment_and_overdue_check_flow(): void
    {
        // (a) Isi komentar dari kolom Komentar lalu refresh dan pastikan masih ada dan sama di halaman Detail
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'customer_name' => 'PT INLINE COMMENT TEST',
            'user_id' => $this->user->id,
            'status' => Task::STATUS_IN_PROGRESS,
            'due_date' => now()->addDays(3),
            'comments' => null,
        ]);

        $commentSaveResp = $this->actingAs($this->user)
            ->postJson(route('tasks.save-comment', $task), [
                'comments' => 'Komentar inline awal oleh karyawan',
            ]);
        $commentSaveResp->assertOk();
        $commentSaveResp->assertJson([
            'success' => true,
            'comments' => 'Komentar inline awal oleh karyawan',
        ]);

        // Refresh and check table view
        $task->refresh();
        $this->assertSame('Komentar inline awal oleh karyawan', $task->comments);

        $tableResp = $this->actingAs($this->user)
            ->get(route('tasks.customer', ['customer' => 'pt-inline-comment-test']));
        $tableResp->assertOk();
        $tableResp->assertSee('Komentar inline awal oleh karyawan');

        // Check Detail page has the exact same comment
        $detailResp = $this->actingAs($this->user)->get(route('tasks.show', $task));
        $detailResp->assertOk();
        $detailResp->assertSee('Komentar inline awal oleh karyawan');

        // (b) Centang tugas yang tidak terlambat tanpa komentar (harus berhasil)
        $nonOverdueTask = Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'customer_name' => 'PT ON TIME TEST',
            'user_id' => $this->user->id,
            'status' => Task::STATUS_IN_PROGRESS,
            'due_date' => now()->addDays(5),
            'comments' => null,
        ]);

        $checkNonOverdueResp = $this->actingAs($this->user)
            ->postJson(route('tasks.toggle-complete', $nonOverdueTask));
        $checkNonOverdueResp->assertOk();
        $checkNonOverdueResp->assertJson(['success' => true, 'is_submitted' => true]);
        $this->assertSame(Task::STATUS_SUBMITTED, $nonOverdueTask->fresh()->status);

        // (c) Centang tugas terlambat tanpa komentar (harus ditolak dan membuka edit komentar)
        $overdueTask = Task::factory()->create([
            'category' => Task::CATEGORY_BAI,
            'customer_name' => 'PT OVERDUE TEST',
            'user_id' => $this->user->id,
            'status' => Task::STATUS_IN_PROGRESS,
            'due_date' => now()->subDays(4),
            'comments' => null,
        ]);

        $checkOverdueNoCommentResp = $this->actingAs($this->user)
            ->postJson(route('tasks.toggle-complete', $overdueTask));
        $checkOverdueNoCommentResp->assertStatus(422);
        $checkOverdueNoCommentResp->assertJson([
            'success' => false,
            'requires_comment' => true,
        ]);
        $this->assertSame(Task::STATUS_IN_PROGRESS, $overdueTask->fresh()->status);

        // (d) Isi komentar lalu centang lagi (harus berhasil dan muncul di Verifikasi admin)
        $this->actingAs($this->user)
            ->postJson(route('tasks.save-comment', $overdueTask), [
                'comments' => 'Alasan: Menunggu material kabel FO',
            ])
            ->assertOk();

        $this->assertSame('Alasan: Menunggu material kabel FO', $overdueTask->fresh()->comments);

        $checkOverdueWithCommentResp = $this->actingAs($this->user)
            ->postJson(route('tasks.toggle-complete', $overdueTask));
        $checkOverdueWithCommentResp->assertOk();
        $checkOverdueWithCommentResp->assertJson(['success' => true, 'is_submitted' => true]);

        $overdueTask->refresh();
        $this->assertSame(Task::STATUS_SUBMITTED, $overdueTask->status);
        $this->assertSame('Alasan: Menunggu material kabel FO', $overdueTask->late_reason);
        $this->assertTrue($overdueTask->was_late);

        // Muncul di Verifikasi admin
        $adminVerifResp = $this->actingAs($this->admin)->get(route('admin.verifikasi'));
        $adminVerifResp->assertOk();
        $adminVerifResp->assertSee('PT OVERDUE TEST');
        $adminVerifResp->assertSee('Alasan: Menunggu material kabel FO');
    }
}
