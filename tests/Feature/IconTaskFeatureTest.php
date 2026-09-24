<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
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

    public function test_export_excel_and_pdf(): void
    {
        Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'user_id' => $this->user->id,
        ]);

        $excelResponse = $this->actingAs($this->user)->get(route('tasks.export.excel'));
        $excelResponse->assertStatus(200);

        $pdfResponse = $this->actingAs($this->user)->get(route('tasks.export.pdf'));
        $pdfResponse->assertStatus(200);
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
            'kp' => Task::KP_SURABAYA,
            'kategori_segmen' => Task::SEGMEN_PLN,
        ]);

        $task2 = Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'user_id' => $this->user->id,
            'title' => 'Tugas Publik Malang Khusus',
            'kp' => Task::KP_MALANG,
            'kategori_segmen' => Task::SEGMEN_PUBLIK,
        ]);

        $response = $this->actingAs($this->admin)->get(route('tasks.index', [
            'kp' => Task::KP_SURABAYA,
            'kategori_segmen' => Task::SEGMEN_PLN,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Tugas PLN Surabaya Khusus');
        $response->assertDontSee('Tugas Publik Malang Khusus');
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
        $response->assertSee('Total Nilai Baru');
        $response->assertSee('KP Surabaya');
        $response->assertSee('KP Malang');
        $response->assertSee('KP Madiun');
        $response->assertSee('KP Jember');
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
        $this->assertEquals('in_progress', $task->status);
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
}
