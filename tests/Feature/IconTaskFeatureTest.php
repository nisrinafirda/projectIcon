<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));

        $dashboardResponse = $this->actingAs($this->user)->get(route('dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee("Halo, {$this->user->name}!");
        $dashboardResponse->assertSee('Total Tugas Anda');
        $dashboardResponse->assertSee('SSO Open');
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
}
