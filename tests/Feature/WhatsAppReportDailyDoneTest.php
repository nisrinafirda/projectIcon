<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Services\WhatsAppReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppReportDailyDoneTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $user;

    protected WhatsAppReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Test',
        ]);

        $this->user = User::factory()->create([
            'role' => 'user',
            'name' => 'Sales Test',
        ]);

        $this->reportService = app(WhatsAppReportService::class);
    }

    public function test_done_section_only_shows_today_confirmed_and_unconfirmed_tasks(): void
    {
        $todayJakarta = Carbon::now('Asia/Jakarta');
        $yesterdayJakarta = $todayJakarta->copy()->subDays(2);

        // 1. Confirmed today: should appear with ✅☑️
        Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'PT HARI INI CONFIRMED',
            'status' => Task::STATUS_APPROVED,
            'rp_exception' => 15000000,
            'confirmed_at' => $todayJakarta,
            'confirmed_by' => $this->admin->id,
        ]);

        // 2. Confirmed yesterday: should NOT appear in DONE (HARI INI) block
        Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'PT KEMARIN CONFIRMED',
            'status' => Task::STATUS_APPROVED,
            'rp_exception' => 25000000,
            'confirmed_at' => $yesterdayJakarta,
            'confirmed_by' => $this->admin->id,
        ]);

        // 3. Checked by sales earlier but not yet confirmed: MUST appear with ✅
        Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'PT SALES CENTANG BELUM KONFIRMASI',
            'status' => Task::STATUS_SUBMITTED,
            'rp_exception' => 10000000,
            'completed_at' => $yesterdayJakarta,
            'done_marked_at' => $yesterdayJakarta,
            'done_marked_by' => $this->user->id,
        ]);

        // 4. In progress / unfinished: MUST appear in ON PROCESS block
        Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'PT MASIH ON PROCESS',
            'status' => Task::STATUS_IN_PROGRESS,
            'rp_exception' => 5000000,
        ]);

        $report = $this->reportService->generateExceptionSection();

        // Rekapitulasi must count all-time completed (PT HARI INI + PT KEMARIN + PT SALES CENTANG = 3 customers, Rp 50.000.000)
        $this->assertStringContainsString('• *SELESAI* : Rp 50.000.000 (3 Pelanggan | 3 SID)', $report);
        // "Selesai hari ini" line is removed from REKAPITULASI
        $this->assertStringNotContainsString('↳ Selesai hari ini', $report);

        // Header for done section
        $this->assertStringContainsString('[✅] *SELESAI / DONE (HARI INI):*', $report);

        // Items in done today:
        $this->assertStringContainsString('PT HARI INI CONFIRMED', $report);
        $this->assertStringContainsString('PT SALES CENTANG BELUM KONFIRMASI', $report);
        // Must NOT appear in DONE section
        $this->assertStringNotContainsString('PT KEMARIN CONFIRMED', $report);

        // On process section unchanged
        $this->assertStringContainsString('PT MASIH ON PROCESS', $report);
    }

    public function test_shows_belum_ada_penyelesaian_hari_ini_when_done_today_is_empty(): void
    {
        $yesterday = Carbon::now('Asia/Jakarta')->subDays(3);

        // Create tasks that were confirmed in the past
        Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'PT LAMA SELESAI',
            'status' => Task::STATUS_APPROVED,
            'confirmed_at' => $yesterday,
            'confirmed_by' => $this->admin->id,
        ]);
        Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'customer_name' => 'PT LAMA SO',
            'status' => Task::STATUS_APPROVED,
            'confirmed_at' => $yesterday,
            'confirmed_by' => $this->admin->id,
        ]);
        Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'customer_name' => 'PT LAMA BAA',
            'status' => Task::STATUS_APPROVED,
            'confirmed_at' => $yesterday,
            'confirmed_by' => $this->admin->id,
        ]);
        Task::factory()->create([
            'category' => Task::CATEGORY_KONTRAK_EXP,
            'customer_name' => 'PT LAMA KONTRAK',
            'status' => Task::STATUS_APPROVED,
            'confirmed_at' => $yesterday,
            'confirmed_by' => $this->admin->id,
        ]);

        $exceptionSection = $this->reportService->generateExceptionSection();
        $soSection = $this->reportService->generateSoOpenSection();
        $baaSection = $this->reportService->generateBaaSection();
        $contractSection = $this->reportService->generateContractExpiredSection();

        // All sections should show "Belum ada penyelesaian hari ini"
        $this->assertStringContainsString("[✅] *SELESAI / DONE (HARI INI):*\nBelum ada penyelesaian hari ini", $exceptionSection);
        $this->assertStringContainsString("[✅] *SELESAI / DONE (HARI INI):*\nBelum ada penyelesaian hari ini", $soSection);
        $this->assertStringContainsString("[✅] *SELESAI / DONE (HARI INI):*\nBelum ada penyelesaian hari ini", $baaSection);
        $this->assertStringContainsString("[✅] *SELESAI / DONE (HARI INI):*\nBelum ada penyelesaian hari ini", $contractSection);

        // Rekapitulasi must still show all-time completed total (1) and not show "Selesai hari ini"
        $this->assertStringContainsString('• *SELESAI*', $exceptionSection);
        $this->assertStringNotContainsString('↳ Selesai hari ini', $exceptionSection);
        $this->assertStringNotContainsString('↳ Selesai hari ini', $soSection);
        $this->assertStringNotContainsString('↳ Selesai hari ini', $baaSection);
        $this->assertStringNotContainsString('↳ Selesai hari ini', $contractSection);
    }

    public function test_legacy_confirmed_tasks_without_confirmed_at_use_updated_at(): void
    {
        $today = Carbon::now('Asia/Jakarta');

        $task = Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'customer_name' => 'PT LEGACY TODAY',
            'status' => Task::STATUS_APPROVED,
            'confirmed_at' => null,
            'updated_at' => $today,
        ]);

        $this->assertTrue($this->reportService->isTaskEligibleForDoneToday($task));

        $soSection = $this->reportService->generateSoOpenSection();
        $this->assertStringContainsString('PT LEGACY TODAY', $soSection);
    }

    public function test_admin_review_sets_confirmed_at_and_appears_in_report(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'customer_name' => 'PT REVIEW TEST',
            'status' => Task::STATUS_SUBMITTED,
            'completed_at' => now(),
            'done_marked_at' => now(),
        ]);

        $this->actingAs($this->admin)->post(route('tasks.review', $task), [
            'action' => 'approve',
            'admin_notes' => 'OK verified',
        ]);

        $task->refresh();
        $this->assertEquals(Task::STATUS_APPROVED, $task->status);
        $this->assertNotNull($task->confirmed_at);
        $this->assertEquals($this->admin->id, $task->confirmed_by);

        $report = $this->reportService->generateReport();
        $this->assertStringContainsString('PT REVIEW TEST', $report);
        $this->assertStringContainsString('✅☑️', $report);
    }

    public function test_exception_on_process_desa_grouping_and_formatting(): void
    {
        $userGumilang = User::factory()->create(['name' => 'GUMILANG ADI PRAKOSO']);
        $userYesy = User::factory()->create(['name' => 'YESY KURNIA']);
        $userFirmanda = User::factory()->create(['name' => 'FIRMANDA']);

        // 1. Desa in Okt-Des group under Gumilang (3 desas)
        Task::factory()->create([
            'user_id' => $userGumilang->id,
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'DESA GROGOL',
            'sales_name' => 'GUMILANG ADI PRAKOSO',
            'status' => Task::STATUS_IN_PROGRESS,
            'rp_exception' => 700000,
            'comments' => 'Termin 3 bulan (Periode Okt, Nov, Des) terbit 15 Des',
        ]);
        Task::factory()->create([
            'user_id' => $userGumilang->id,
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'BENDAHARA DESA SUGIHWARAS',
            'sales_name' => 'GUMILANG ADI PRAKOSO',
            'status' => Task::STATUS_IN_PROGRESS,
            'rp_exception' => 700000,
            'comments' => 'Termin 3 bulan',
        ]);
        Task::factory()->create([
            'user_id' => $userGumilang->id,
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'DESA SEMBULUNG',
            'sales_name' => 'GUMILANG ADI PRAKOSO',
            'status' => Task::STATUS_IN_PROGRESS,
            'rp_exception' => 700000,
            'comments' => 'Termin 3 bulan (Periode Okt, Nov, Des) terbit 15 Des',
        ]);

        // 2. Desa in Okt-Des group under Yesy (2 desas)
        Task::factory()->create([
            'user_id' => $userYesy->id,
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'BEND DESA SIDOMULYO',
            'sales_name' => 'YESY KURNIA',
            'status' => Task::STATUS_IN_PROGRESS,
            'rp_exception' => 700000,
            'comments' => 'Termin 3 bulan (Periode Okt, Nov, Des) terbit 15 Des',
        ]);
        Task::factory()->create([
            'user_id' => $userYesy->id,
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'DESA KEDAYUNAN',
            'sales_name' => 'YESY KURNIA',
            'status' => Task::STATUS_IN_PROGRESS,
            'rp_exception' => 700000,
            'comments' => 'Termin 3 bulan (Periode Okt, Nov, Des) terbit 15 Des',
        ]);

        // 3. Single desa in Jul-Sep group under Gumilang
        Task::factory()->create([
            'user_id' => $userGumilang->id,
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'Desa Karanganyar Kab. Ngawi',
            'sales_name' => 'GUMILANG ADI PRAKOSO',
            'status' => Task::STATUS_IN_PROGRESS,
            'rp_exception' => 700000,
            'comments' => 'Termin 3 (Periode Juli sd Sept) terbit 5 oktober',
        ]);

        // 4. Bendahara Pengeluaran Dinas: MUST NOT be grouped as desa
        Task::factory()->create([
            'user_id' => $userGumilang->id,
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'BENDAHARA PENGELUARAN DINAS KESEHATAN',
            'sales_name' => 'GUMILANG ADI PRAKOSO',
            'status' => Task::STATUS_IN_PROGRESS,
            'rp_exception' => 5000000,
            'comments' => 'Termin 3 bulan (Periode Okt, Nov, Des) terbit 15 Des',
        ]);

        // 5. Desa partially completed: 1 finished today, 1 unfinished
        Task::factory()->create([
            'user_id' => $userFirmanda->id,
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'DESA SEBAGIAN SELESAI',
            'sales_name' => 'FIRMANDA',
            'status' => Task::STATUS_APPROVED,
            'rp_exception' => 700000,
            'confirmed_at' => now(),
            'confirmed_by' => $this->admin->id,
            'comments' => 'Sudah bayar termin 3',
        ]);
        Task::factory()->create([
            'user_id' => $userFirmanda->id,
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'DESA SEBAGIAN SELESAI',
            'sales_name' => 'FIRMANDA',
            'status' => Task::STATUS_IN_PROGRESS,
            'rp_exception' => 700000,
            'comments' => 'Termin 3 bulan (Periode Okt, Nov, Des) terbit 15 Des',
        ]);

        $report = $this->reportService->generateExceptionSection();

        // Rekapitulasi counts all individual unfinished customers (7 desa + 1 dinas = 8 pelanggan | 8 SID)
        $this->assertStringContainsString('• *BELUM SELESAI* : Rp 9.900.000 (8 Pelanggan | 8 SID)', $report);
        $this->assertStringContainsString('*Total: Rp 9.900.000 (8 Pelanggan/PIC | 8 SID)*', $report);

        // DINAS appears individually
        $this->assertStringContainsString('*BENDAHARA PENGELUARAN DINAS KESEHATAN*', $report);

        // Okt-Des group header: 6 desa (Grogol, Sugihwaras, Sembulung, Sidomulyo, Kedayunan, Sebagian Selesai)
        $this->assertStringContainsString('*DESA / BENDAHARA DESA - TERMIN OKT s.d DES (6 Pelanggan)* | PIC: GUMILANG (3), YESY (2), FIRMANDA (1) ❌', $report);
        $this->assertStringContainsString('↳ Nilai: Rp 4.200.000 | Kendala: Termin 3 bulan (Periode Okt, Nov, Des) terbit 15 Des', $report);
        $this->assertStringContainsString('• Gumilang: 3 desa | Rp 2.100.000', $report);
        $this->assertStringContainsString('• Yesy: 2 desa | Rp 1.400.000', $report);
        $this->assertStringContainsString('• Firmanda: 1 desa | Rp 700.000', $report);

        // Single desa group header: 1 desa (Desa Karanganyar Kab. Ngawi)
        $this->assertStringContainsString('*DESA / BENDAHARA DESA - TERMIN JUL s.d SEP (1 Pelanggan)* | PIC: GUMILANG (1) ❌', $report);
        $this->assertStringContainsString('↳ Nilai: Rp 700.000 | Kendala: Termin 3 (Periode Juli sd Sept) terbit 5 oktober', $report);
        $this->assertStringContainsString('• Gumilang: Desa Karanganyar Kab. Ngawi | Rp 700.000', $report);

        // SELESAI / DONE (HARI INI) block: DESA SEBAGIAN SELESAI appears individually with full name and marker
        $this->assertStringContainsString('[✅] *SELESAI / DONE (HARI INI):*', $report);
        $this->assertStringContainsString('*DESA SEBAGIAN SELESAI* | PIC: FIRMANDA ✅☑️', $report);
    }

    public function test_rekapitulasi_format_standardized_across_all_sections(): void
    {
        // 1. Exception
        Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'PT EXC A',
            'status' => Task::STATUS_APPROVED,
            'rp_exception' => 2000000,
            'confirmed_at' => now(),
            'confirmed_by' => $this->admin->id,
        ]);
        Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'PT EXC B',
            'status' => Task::STATUS_IN_PROGRESS,
            'rp_exception' => 3000000,
        ]);

        // 2. SO Open
        Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'customer_name' => 'PT SO A',
            'status' => Task::STATUS_APPROVED,
            'confirmed_at' => now(),
            'confirmed_by' => $this->admin->id,
        ]);
        Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'customer_name' => 'PT SO B',
            'status' => Task::STATUS_IN_PROGRESS,
        ]);

        // 3. BAA Open
        Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'customer_name' => 'PT BAA A',
            'status' => Task::STATUS_APPROVED,
            'confirmed_at' => now(),
            'confirmed_by' => $this->admin->id,
        ]);
        Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'customer_name' => 'PT BAA B',
            'status' => Task::STATUS_IN_PROGRESS,
        ]);

        // 4. Contract Expired
        Task::factory()->create([
            'category' => Task::CATEGORY_KONTRAK_EXP,
            'customer_name' => 'PT CONTRACT A',
            'status' => Task::STATUS_APPROVED,
            'confirmed_at' => now(),
            'confirmed_by' => $this->admin->id,
        ]);
        Task::factory()->create([
            'category' => Task::CATEGORY_KONTRAK_EXP,
            'customer_name' => 'PT CONTRACT B',
            'status' => Task::STATUS_IN_PROGRESS,
        ]);

        $exc = $this->reportService->generateExceptionSection();
        $this->assertStringContainsString("📌 *1. EXCEPTION (STATUS: EXCEPTION OPEN)*\n*📊 REKAPITULASI*\n• *TOTAL KESELURUHAN* : Rp 5.000.000 (2 Pelanggan | 2 SID)\n----------------------------------------------------------\n• *SELESAI* : Rp 2.000.000 (1 Pelanggan | 1 SID)\n• *BELUM SELESAI* : Rp 3.000.000 (1 Pelanggan | 1 SID)", $exc);

        $so = $this->reportService->generateSoOpenSection();
        $this->assertStringContainsString("📌 *2. SO OPEN*\n*📊 REKAPITULASI*\n• *TOTAL KESELURUHAN* : 2 Pelanggan | 2 SID\n----------------------------------------------------------\n• *SELESAI* : 1 Pelanggan | 1 SID\n• *BELUM SELESAI* : 1 Pelanggan | 1 SID", $so);

        $baa = $this->reportService->generateBaaSection();
        $this->assertStringContainsString("📌 *3. BAA OPEN*\n*📊 REKAPITULASI*\n• *TOTAL KESELURUHAN* : 2 Item\n----------------------------------------------------------\n• *SELESAI* : 1 Item\n• *BELUM SELESAI* : 1 Item", $baa);

        $contract = $this->reportService->generateContractExpiredSection();
        $this->assertStringContainsString("📌 *4. CONTRACT EXPIRED*\n*📊 REKAPITULASI*\n• *TOTAL KESELURUHAN* : 2 Pekerjaan\n----------------------------------------------------------\n• *SELESAI* : 1 Pekerjaan\n• *BELUM SELESAI* : 1 Pekerjaan", $contract);
    }
}
