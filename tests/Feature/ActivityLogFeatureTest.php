<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ActivityLogFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $salesA;

    protected User $salesB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@icon.co.id',
            'role' => 'admin',
            'name' => 'Admin ICON',
        ]);

        $this->salesA = User::factory()->create([
            'email' => 'sales.a@icon.co.id',
            'role' => 'user',
            'name' => 'Sales A',
        ]);

        $this->salesB = User::factory()->create([
            'email' => 'sales.b@icon.co.id',
            'role' => 'user',
            'name' => 'Sales B',
        ]);
    }

    public function test_comment_change_logs_activity_with_old_and_new_value(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'customer_name' => 'PT MAJU MAPAN',
            'document_number' => 'BAA-LOG-001',
            'user_id' => $this->salesA->id,
            'comments' => 'Komentar Pertama',
        ]);

        $this->actingAs($this->salesA)
            ->postJson(route('tasks.save-comment', $task), [
                'comments' => 'Komentar Kedua yang Diperbarui',
            ])
            ->assertOk();

        $log = ActivityLog::where('action', ActivityLogger::ACTION_COMMENT_UPDATED)
            ->where('document_number', 'BAA-LOG-001')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($this->salesA->id, $log->user_id);
        $this->assertSame('PT MAJU MAPAN', $log->customer_name);
        $this->assertSame('Komentar Pertama', $log->old_value);
        $this->assertSame('Komentar Kedua yang Diperbarui', $log->new_value);
    }

    public function test_verification_approval_and_rejection_logs_activity(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_SO_OPEN,
            'customer_name' => 'PT VERIFIKASI TEST',
            'document_number' => 'SO-VERIF-001',
            'status' => Task::STATUS_SUBMITTED,
            'user_id' => $this->salesA->id,
        ]);

        // Admin approves task
        $this->actingAs($this->admin)
            ->post(route('tasks.review', $task), [
                'action' => 'approve',
                'admin_notes' => 'Disetujui sudah lengkap',
            ])
            ->assertRedirect();

        $approveLog = ActivityLog::where('action', ActivityLogger::ACTION_TASK_VERIFIED)
            ->where('document_number', 'SO-VERIF-001')
            ->first();

        $this->assertNotNull($approveLog);
        $this->assertSame($this->admin->id, $approveLog->user_id);
        $this->assertSame('PT VERIFIKASI TEST', $approveLog->customer_name);

        // Another task rejected
        $task2 = Task::factory()->create([
            'category' => Task::CATEGORY_SO_OPEN,
            'customer_name' => 'PT VERIFIKASI REJECT',
            'document_number' => 'SO-VERIF-002',
            'status' => Task::STATUS_SUBMITTED,
            'user_id' => $this->salesA->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('tasks.review', $task2), [
                'action' => 'reject',
                'admin_notes' => 'Kurang data pendukung',
            ])
            ->assertRedirect();

        $rejectLog = ActivityLog::where('action', ActivityLogger::ACTION_TASK_REJECTED)
            ->where('document_number', 'SO-VERIF-002')
            ->first();

        $this->assertNotNull($rejectLog);
        $this->assertSame($this->admin->id, $rejectLog->user_id);
        $this->assertSame('PT VERIFIKASI REJECT', $rejectLog->customer_name);
    }

    public function test_mass_import_creates_single_log_entry_for_entire_batch(): void
    {
        $header = "ID PA,NAMA PELANGGAN,NAMA SALES,KP,STATUS,HARGA BARU\n";
        $row1 = "PA-IMP-001,PELANGGAN IMPORT 1,{$this->salesA->name},Surabaya,On Process,1000000\n";
        $row2 = "PA-IMP-002,PELANGGAN IMPORT 2,{$this->salesA->name},Surabaya,Done,2000000\n";
        $csvContent = $header.$row1.$row2;

        $file = UploadedFile::fake()->createWithContent('import_test.csv', $csvContent);

        $this->actingAs($this->admin)
            ->post(route('tasks.import.process'), [
                'file' => $file,
                'target_category' => Task::CATEGORY_BAA,
                'duplicate_action' => 'skip',
            ])
            ->assertRedirect();

        $importLogs = ActivityLog::where('action', ActivityLogger::ACTION_IMPORT_DONE)->get();

        $this->assertCount(1, $importLogs, 'Harus mencatat tepat SATU entri log untuk keseluruhan proses import batch.');
        $this->assertSame($this->admin->id, $importLogs->first()->user_id);
        $this->assertSame(Task::CATEGORY_BAA, $importLogs->first()->category);
        $this->assertStringContainsString('2 ditambahkan', $importLogs->first()->description);
        $this->assertCount(2, $importLogs->first()->details);
    }

    public function test_task_deletion_preserves_log_with_customer_name_and_document_number(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_BAI,
            'customer_name' => 'PT HAPUS DOKUMEN',
            'document_number' => 'BAI-DEL-001',
            'user_id' => $this->salesA->id,
        ]);

        $taskId = $task->id;

        $this->actingAs($this->admin)
            ->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseMissing('tasks', ['id' => $taskId]);

        $deleteLog = ActivityLog::where('action', ActivityLogger::ACTION_TASK_DELETED)
            ->where('document_number', 'BAI-DEL-001')
            ->first();

        $this->assertNotNull($deleteLog);
        $this->assertSame('PT HAPUS DOKUMEN', $deleteLog->customer_name);
        $this->assertSame('BAI-DEL-001', $deleteLog->document_number);
    }

    public function test_sales_user_only_sees_their_own_activity_logs(): void
    {
        // Activity for Sales A
        ActivityLog::create([
            'user_id' => $this->salesA->id,
            'user_name' => $this->salesA->name,
            'user_role' => 'user',
            'action' => ActivityLogger::ACTION_TASK_COMPLETED_CHECKED,
            'category' => Task::CATEGORY_BAA,
            'customer_name' => 'PELANGGAN KHUSUS SALES A',
            'document_number' => 'DOC-A-001',
            'description' => 'Aktivitas Sales A',
            'created_at' => now(),
        ]);

        // Activity for Sales B
        ActivityLog::create([
            'user_id' => $this->salesB->id,
            'user_name' => $this->salesB->name,
            'user_role' => 'user',
            'action' => ActivityLogger::ACTION_TASK_COMPLETED_CHECKED,
            'category' => Task::CATEGORY_BAA,
            'customer_name' => 'PELANGGAN KHUSUS SALES B',
            'document_number' => 'DOC-B-002',
            'description' => 'Aktivitas Sales B',
            'created_at' => now(),
        ]);

        // When Sales A views the activity log page
        $responseA = $this->actingAs($this->salesA)->get(route('activity-logs.index', ['preset' => 'all']));
        $responseA->assertOk();
        $responseA->assertSee('PELANGGAN KHUSUS SALES A');
        $responseA->assertDontSee('PELANGGAN KHUSUS SALES B');

        // When Sales B views the activity log page
        $responseB = $this->actingAs($this->salesB)->get(route('activity-logs.index', ['preset' => 'all']));
        $responseB->assertOk();
        $responseB->assertSee('PELANGGAN KHUSUS SALES B');
        $responseB->assertDontSee('PELANGGAN KHUSUS SALES A');

        // When Admin views the activity log page, both are visible
        $responseAdmin = $this->actingAs($this->admin)->get(route('activity-logs.index', ['preset' => 'all']));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee('PELANGGAN KHUSUS SALES A');
        $responseAdmin->assertSee('PELANGGAN KHUSUS SALES B');
    }

    public function test_date_and_year_filters(): void
    {
        $today = Carbon::today('Asia/Jakarta')->setTime(10, 0, 0);
        $yesterday = Carbon::yesterday('Asia/Jakarta')->setTime(10, 0, 0);
        $oldDate = Carbon::create(2024, 5, 20, 10, 0, 0, 'Asia/Jakarta');

        ActivityLog::create([
            'user_id' => $this->admin->id,
            'user_name' => $this->admin->name,
            'user_role' => 'admin',
            'action' => ActivityLogger::ACTION_TASK_CREATED,
            'customer_name' => 'PELANGGAN HARI INI',
            'document_number' => 'DOC-TODAY',
            'description' => 'Dibuat hari ini',
            'created_at' => $today,
        ]);

        ActivityLog::create([
            'user_id' => $this->admin->id,
            'user_name' => $this->admin->name,
            'user_role' => 'admin',
            'action' => ActivityLogger::ACTION_TASK_CREATED,
            'customer_name' => 'PELANGGAN KEMARIN',
            'document_number' => 'DOC-YESTERDAY',
            'description' => 'Dibuat kemarin',
            'created_at' => $yesterday,
        ]);

        ActivityLog::create([
            'user_id' => $this->admin->id,
            'user_name' => $this->admin->name,
            'user_role' => 'admin',
            'action' => ActivityLogger::ACTION_TASK_CREATED,
            'customer_name' => 'PELANGGAN TAHUN LALU',
            'document_number' => 'DOC-OLD',
            'description' => 'Dibuat tahun 2024',
            'created_at' => $oldDate,
        ]);

        // Preset: today
        $resToday = $this->actingAs($this->admin)->get(route('activity-logs.index', ['preset' => 'today']));
        $resToday->assertOk();
        $resToday->assertSee('PELANGGAN HARI INI');
        $resToday->assertDontSee('PELANGGAN KEMARIN');
        $resToday->assertDontSee('PELANGGAN TAHUN LALU');

        // Preset: yesterday
        $resYesterday = $this->actingAs($this->admin)->get(route('activity-logs.index', ['preset' => 'yesterday']));
        $resYesterday->assertOk();
        $resYesterday->assertSee('PELANGGAN KEMARIN');
        $resYesterday->assertDontSee('PELANGGAN HARI INI');
        $resYesterday->assertDontSee('PELANGGAN TAHUN LALU');

        // Filter: year 2024
        $resYear = $this->actingAs($this->admin)->get(route('activity-logs.index', ['year' => 2024]));
        $resYear->assertOk();
        $resYear->assertSee('PELANGGAN TAHUN LALU');
        $resYear->assertDontSee('PELANGGAN HARI INI');
        $resYear->assertDontSee('PELANGGAN KEMARIN');
    }

    public function test_toggle_complete_and_uncheck_logs_activity(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_SO_OPEN,
            'customer_name' => 'PT TOGGLE TEST',
            'document_number' => 'SO-TOGGLE-001',
            'status' => Task::STATUS_IN_PROGRESS,
            'user_id' => $this->salesA->id,
            'due_date' => now()->addDays(5),
        ]);

        // Sales checks complete
        $this->actingAs($this->salesA)
            ->postJson(route('tasks.toggle-complete', $task))
            ->assertOk();

        $checkLog = ActivityLog::where('action', ActivityLogger::ACTION_TASK_COMPLETED_CHECKED)
            ->where('document_number', 'SO-TOGGLE-001')
            ->first();

        $this->assertNotNull($checkLog);
        $this->assertSame($this->salesA->id, $checkLog->user_id);
        $this->assertSame('PT TOGGLE TEST', $checkLog->customer_name);

        // Sales unchecks complete
        $this->actingAs($this->salesA)
            ->postJson(route('tasks.toggle-complete', $task), ['uncheck' => 1])
            ->assertOk();

        $uncheckLog = ActivityLog::where('action', ActivityLogger::ACTION_TASK_UNCHECKED)
            ->where('document_number', 'SO-TOGGLE-001')
            ->first();

        $this->assertNotNull($uncheckLog);
        $this->assertSame($this->salesA->id, $uncheckLog->user_id);
    }

    public function test_export_excel_logs_activity(): void
    {
        $this->actingAs($this->admin)
            ->get(route('tasks.export.excel', ['category' => 'baa']))
            ->assertOk();

        $exportLog = ActivityLog::where('action', ActivityLogger::ACTION_EXPORT_DONE)
            ->where('category', 'baa')
            ->first();

        $this->assertNotNull($exportLog);
        $this->assertSame($this->admin->id, $exportLog->user_id);
    }

    public function test_whatsapp_report_settings_update_logs_activity(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.report.whatsapp.settings'), [
                'opportunity_publik' => 50000000,
                'revenue_as_is_publik' => 20000000,
            ])
            ->assertOk();

        $reportLog = ActivityLog::where('action', 'report_updated')->first();
        $this->assertNotNull($reportLog);
        $this->assertSame($this->admin->id, $reportLog->user_id);
    }
}
