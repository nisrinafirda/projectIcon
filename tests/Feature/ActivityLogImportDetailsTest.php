<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ActivityLogDetail;
use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ActivityLogImportDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $sales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Test',
        ]);

        $this->sales = User::factory()->create([
            'role' => 'user',
            'name' => 'Sales Test',
        ]);
    }

    public function test_import_added_tasks_records_added_details(): void
    {
        $header = "ID PA,NAMA PELANGGAN,LAYANAN PRODUK,HARGA LAMA,HARGA BARU,SELISIH,Kategori Customer,KP,Sales,Tanggal Upload BAI,Aging BAA (Hari),Target,Tanggal Upload BAA,Kendala,Support,Status\n";
        $row1 = "PA-NEW-001,PT PELANGGAN BARU,INTERNET,1000,5000000,4999000,PUBLIK,Surabaya,{$this->sales->name},8/7/2026,6,,8/13/2026,,Support BAA,On Process\n";
        $csv = $header.$row1;

        $file = UploadedFile::fake()->createWithContent('new_tasks.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'target_category' => Task::CATEGORY_BAA,
            'duplicate_action' => 'update',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $log = ActivityLog::where('action', ActivityLogger::ACTION_IMPORT_DONE)->first();
        $this->assertNotNull($log);
        $this->assertNotNull($log);
        $this->assertStringContainsString('1 ditambahkan, 0 diperbarui, 0 tidak berubah', $log->description);

        $this->assertCount(1, $log->details);
        $detail = $log->details->first();
        $this->assertSame('added', $detail->change_type);
        $this->assertSame('PT PELANGGAN BARU', $detail->customer_name);
        $this->assertSame('PA-NEW-001', $detail->sid);
        $this->assertSame('Tugas Baru', $detail->field_label);
        $this->assertNull($detail->old_value);
        $this->assertNotNull($detail->new_value);
    }

    public function test_import_update_tasks_detects_changes_and_ignores_formatting_differences(): void
    {
        // 1. Task that has real changes (status and harga_baru changed)
        $existingTask1 = Task::create([
            'category' => Task::CATEGORY_BAA,
            'document_number' => 'PA-EXIST-001',
            'title' => 'Tugas BAA - PA-EXIST-001',
            'customer_name' => 'PT EXISTING SATU',
            'user_id' => $this->sales->id,
            'sales_name' => $this->sales->name,
            'kp' => 'Surabaya',
            'status' => Task::STATUS_IN_PROGRESS,
            'harga_baru' => 1000000,
            'priority' => 'medium',
        ]);

        // 2. Task that has NO real changes (only format: trailing space in name, lowercase/uppercase KP, "-" for rp_exception)
        $existingTask2 = Task::create([
            'category' => Task::CATEGORY_BAA,
            'document_number' => 'PA-EXIST-002',
            'title' => 'BAA - PT EXISTING DUA (INTERNET)',
            'description' => "Harga Lama: Rp 1.000 | Harga Baru: Rp 2.000.000 | Selisih: Rp 1.999.000\nSupport: Support BAA",
            'service_type' => 'INTERNET',
            'start_date' => '2026-08-07',
            'due_date' => '2026-08-14',
            'customer_name' => 'PT EXISTING DUA',
            'user_id' => $this->sales->id,
            'sales_name' => $this->sales->name,
            'kp' => 'surabaya',
            'kategori_segmen' => 'publik',
            'status' => Task::STATUS_IN_PROGRESS,
            'harga_baru' => 2000000,
            'rp_exception' => 0,
            'priority' => 'medium',
        ]);

        $header = "ID PA,NAMA PELANGGAN,LAYANAN PRODUK,HARGA LAMA,HARGA BARU,SELISIH,Kategori Customer,KP,Sales,Tanggal Upload BAI,Aging BAA (Hari),Target,Tanggal Upload BAA,Kendala,Support,Status\n";
        // Row 1: status changed to Done, harga_baru changed to 3000000
        $row1 = "PA-EXIST-001,PT EXISTING SATU,INTERNET,1000,3000000,2999000,PUBLIK,Surabaya,{$this->sales->name},8/7/2026,6,,8/13/2026,,Support BAA,Done\n";
        // Row 2: name with space, status same, harga same
        $row2 = "PA-EXIST-002,PT EXISTING DUA ,INTERNET,1000,2000000,1999000,PUBLIK,Surabaya,{$this->sales->name},8/7/2026,6,,8/13/2026,,Support BAA,On Process\n";
        $csv = $header.$row1.$row2;

        $file = UploadedFile::fake()->createWithContent('update_tasks.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'target_category' => Task::CATEGORY_BAA,
            'duplicate_action' => 'update',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $log = ActivityLog::where('action', ActivityLogger::ACTION_IMPORT_DONE)->first();
        $this->assertNotNull($log);
        $this->assertNotNull($log);
        $this->assertStringContainsString('0 ditambahkan, 1 diperbarui, 1 tidak berubah', $log->description);

        // Details should only be recorded for PA-EXIST-001, NOT for PA-EXIST-002
        $details = $log->details;
        $this->assertGreaterThan(0, $details->count());
        $this->assertTrue($details->every(fn (ActivityLogDetail $d) => $d->sid === 'PA-EXIST-001'));
        $this->assertTrue($details->every(fn (ActivityLogDetail $d) => $d->change_type === 'updated'));

        $fieldsChanged = $details->pluck('field')->all();
        $this->assertContains('status', $fieldsChanged);
        $this->assertContains('harga_baru', $fieldsChanged);
        $this->assertNotContains('customer_name', $fieldsChanged);
    }

    public function test_activity_logs_search_matches_customer_name_and_sid_in_details(): void
    {
        $log = ActivityLog::create([
            'user_id' => $this->admin->id,
            'user_name' => $this->admin->name,
            'user_role' => 'admin',
            'action' => ActivityLogger::ACTION_IMPORT_DONE,
            'description' => 'Import massal kategori BAA: 0 ditambahkan, 1 diperbarui, 0 tidak berubah',
            'created_at' => now(),
        ]);

        ActivityLogDetail::create([
            'activity_log_id' => $log->id,
            'customer_name' => 'PT NUSANTARA MEGAH ABADI',
            'sid' => 'SID-NMA-999',
            'change_type' => 'updated',
            'field' => 'status',
            'field_label' => 'Status',
            'old_value' => 'Sedang Dikerjakan',
            'new_value' => 'Selesai (Disetujui)',
        ]);

        // Search by customer name
        $response1 = $this->actingAs($this->admin)->get(route('activity-logs.index', [
            'search' => 'NUSANTARA MEGAH',
            'preset' => 'all',
        ]));

        $response1->assertStatus(200);
        $response1->assertSee('Import massal kategori BAA');

        // Search by SID
        $response2 = $this->actingAs($this->admin)->get(route('activity-logs.index', [
            'search' => 'SID-NMA-999',
            'preset' => 'all',
        ]));

        $response2->assertStatus(200);
        $response2->assertSee('Import massal kategori BAA');

        // Search for non-existent keyword
        $response3 = $this->actingAs($this->admin)->get(route('activity-logs.index', [
            'search' => 'TIDAK_ADA_DI_MANAPUN_XYZ',
            'preset' => 'all',
        ]));

        $response3->assertStatus(200);
        $response3->assertDontSee('Import massal kategori BAA');
    }

    public function test_activity_logs_details_ajax_endpoint_returns_json_and_paginates(): void
    {
        $log = ActivityLog::create([
            'user_id' => $this->admin->id,
            'user_name' => $this->admin->name,
            'user_role' => 'admin',
            'action' => ActivityLogger::ACTION_IMPORT_DONE,
            'description' => 'Import massal kategori BAA: 60 ditambahkan, 0 diperbarui, 0 tidak berubah',
            'created_at' => now(),
        ]);

        $details = [];
        for ($i = 1; $i <= 65; $i++) {
            $details[] = [
                'activity_log_id' => $log->id,
                'customer_name' => "PT PELANGGAN {$i}",
                'sid' => "SID-{$i}",
                'change_type' => 'added',
                'field' => 'task',
                'field_label' => 'Tugas Baru',
                'old_value' => null,
                'new_value' => "Tugas {$i}",
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        ActivityLogDetail::insert($details);

        // Fetch page 1 (first 50)
        $response = $this->actingAs($this->admin)->getJson(route('activity-logs.details', [
            'activityLog' => $log->id,
            'offset' => 0,
            'limit' => 50,
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'total' => 65,
            'offset' => 0,
            'limit' => 50,
            'has_more' => true,
            'remaining' => 15,
            'next_offset' => 50,
        ]);
        $this->assertCount(50, $response->json('details'));

        // Fetch remaining 15
        $response2 = $this->actingAs($this->admin)->getJson(route('activity-logs.details', [
            'activityLog' => $log->id,
            'offset' => 50,
            'limit' => 50,
        ]));

        $response2->assertStatus(200);
        $response2->assertJson([
            'total' => 65,
            'offset' => 50,
            'has_more' => false,
            'remaining' => 0,
            'next_offset' => 65,
        ]);
        $this->assertCount(15, $response2->json('details'));

        // Non-admin trying to access admin log
        $otherUser = User::factory()->create(['role' => 'user']);
        $responseUnauthorized = $this->actingAs($otherUser)->getJson(route('activity-logs.details', [
            'activityLog' => $log->id,
        ]));
        $responseUnauthorized->assertStatus(403);
    }
}
