<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\View\Composers\SidebarComposer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SidebarCategoryBadgeTest extends TestCase
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
        ]);

        $this->salesA = User::factory()->create([
            'email' => 'salesa@icon.co.id',
            'role' => 'user',
            'nip' => 'NIP001',
        ]);

        $this->salesB = User::factory()->create([
            'email' => 'salesb@icon.co.id',
            'role' => 'user',
            'nip' => 'NIP002',
        ]);
    }

    public function test_admin_sees_only_in_progress_counts_with_mixed_task_statuses(): void
    {
        // SO Open: 2 in_progress, 1 pending, 1 approved, 1 submitted
        Task::factory()->count(2)->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'status' => Task::STATUS_IN_PROGRESS,
            'user_id' => $this->salesA->id,
        ]);
        Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'status' => Task::STATUS_PENDING,
            'user_id' => $this->salesA->id,
        ]);
        Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'status' => Task::STATUS_APPROVED,
            'user_id' => $this->salesA->id,
        ]);
        Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'status' => Task::STATUS_SUBMITTED,
            'user_id' => $this->salesA->id,
        ]);

        // BAI: 0 in_progress, 2 pending, 1 approved (should show 0)
        Task::factory()->count(2)->create([
            'category' => Task::CATEGORY_BAI,
            'status' => Task::STATUS_PENDING,
            'user_id' => $this->salesA->id,
        ]);
        Task::factory()->create([
            'category' => Task::CATEGORY_BAI,
            'status' => Task::STATUS_APPROVED,
            'user_id' => $this->salesA->id,
        ]);

        // BAA: 3 in_progress, 2 approved
        Task::factory()->count(3)->create([
            'category' => Task::CATEGORY_BAA,
            'status' => Task::STATUS_IN_PROGRESS,
            'user_id' => $this->salesB->id,
        ]);
        Task::factory()->count(2)->create([
            'category' => Task::CATEGORY_BAA,
            'status' => Task::STATUS_APPROVED,
            'user_id' => $this->salesB->id,
        ]);

        // EXCEPTION: 1 in_progress, 2 pending, 3 approved
        Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'status' => Task::STATUS_IN_PROGRESS,
            'user_id' => $this->salesA->id,
        ]);
        Task::factory()->count(2)->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'status' => Task::STATUS_PENDING,
            'user_id' => $this->salesB->id,
        ]);
        Task::factory()->count(3)->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'status' => Task::STATUS_APPROVED,
            'user_id' => $this->salesB->id,
        ]);

        // KONTRAK EXP: 4 in_progress, 1 approved
        Task::factory()->count(4)->create([
            'category' => Task::CATEGORY_KONTRAK_EXP,
            'status' => Task::STATUS_IN_PROGRESS,
            'user_id' => $this->salesB->id,
        ]);
        Task::factory()->create([
            'category' => Task::CATEGORY_KONTRAK_EXP,
            'status' => Task::STATUS_APPROVED,
            'user_id' => $this->salesB->id,
        ]);

        $counts = Task::sidebarBadgeCounts($this->admin);
        $this->assertSame([
            'sso_open' => 2,
            'bai' => 0,
            'baa' => 3,
            'exception' => 1,
            'kontrak_exp' => 4,
            'contract_verification' => 0,
        ], $counts);

        $response = $this->actingAs($this->admin)->get(route('tasks.index'));
        $response->assertOk();

        // Check tooltips and numbers
        $response->assertSee('title="Sedang dikerjakan: 2"', false);
        $response->assertSee('title="Sedang dikerjakan: 0"', false);
        $response->assertSee('title="Sedang dikerjakan: 3"', false);
        $response->assertSee('title="Sedang dikerjakan: 1"', false);
        $response->assertSee('title="Sedang dikerjakan: 4"', false);
    }

    public function test_sales_sees_only_own_in_progress_tasks(): void
    {
        // Sales A has 2 in_progress in SO Open and 1 in BAA
        Task::factory()->count(2)->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'status' => Task::STATUS_IN_PROGRESS,
            'user_id' => $this->salesA->id,
        ]);
        Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'status' => Task::STATUS_IN_PROGRESS,
            'user_id' => $this->salesA->id,
        ]);
        Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'status' => Task::STATUS_APPROVED,
            'user_id' => $this->salesA->id,
        ]);

        // Sales B has 3 in_progress in SO Open and 2 in BAI
        Task::factory()->count(3)->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'status' => Task::STATUS_IN_PROGRESS,
            'user_id' => $this->salesB->id,
        ]);
        Task::factory()->count(2)->create([
            'category' => Task::CATEGORY_BAI,
            'status' => Task::STATUS_IN_PROGRESS,
            'user_id' => $this->salesB->id,
        ]);

        // Check Sales A
        $countsSalesA = Task::sidebarBadgeCounts($this->salesA);
        $this->assertSame([
            'sso_open' => 2,
            'bai' => 0,
            'baa' => 1,
            'exception' => 0,
            'kontrak_exp' => 0,
            'contract_verification' => 0,
        ], $countsSalesA);

        $responseA = $this->actingAs($this->salesA)->get(route('tasks.index'));
        $responseA->assertOk();
        $responseA->assertSee('title="Sedang dikerjakan: 2"', false);
        $responseA->assertSee('title="Sedang dikerjakan: 0"', false);
        $responseA->assertSee('title="Sedang dikerjakan: 1"', false);

        // Check Sales B
        $countsSalesB = Task::sidebarBadgeCounts($this->salesB);
        $this->assertSame([
            'sso_open' => 3,
            'bai' => 2,
            'baa' => 0,
            'exception' => 0,
            'kontrak_exp' => 0,
            'contract_verification' => 0,
        ], $countsSalesB);

        $responseB = $this->actingAs($this->salesB)->get(route('tasks.index'));
        $responseB->assertOk();
        $responseB->assertSee('title="Sedang dikerjakan: 3"', false);
        $responseB->assertSee('title="Sedang dikerjakan: 2"', false);
    }

    public function test_badge_changes_when_task_is_completed_and_confirmed(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'status' => Task::STATUS_IN_PROGRESS,
            'user_id' => $this->salesA->id,
            'due_date' => now()->addDays(5),
        ]);

        // Initially in_progress count = 1
        $this->assertSame(1, Task::sidebarBadgeCounts($this->salesA)['baa']);
        $this->assertSame(1, Task::sidebarBadgeCounts($this->admin)['baa']);

        // Sales checks task as completed
        $toggleResponse = $this->actingAs($this->salesA)->postJson(route('tasks.toggle-complete', $task));
        $toggleResponse->assertOk();

        $task->refresh();
        $this->assertSame(Task::STATUS_SUBMITTED, $task->status);

        // Count should immediately become 0
        $this->assertSame(0, Task::sidebarBadgeCounts($this->salesA)['baa']);
        $this->assertSame(0, Task::sidebarBadgeCounts($this->admin)['baa']);

        // Admin confirms task in verification
        $reviewResponse = $this->actingAs($this->admin)->post(route('tasks.review', $task), [
            'action' => 'approve',
            'admin_notes' => 'Approved',
        ]);
        $reviewResponse->assertRedirect();

        $task->refresh();
        $this->assertSame(Task::STATUS_APPROVED, $task->status);

        // Count remains 0 after confirmation
        $this->assertSame(0, Task::sidebarBadgeCounts($this->salesA)['baa']);
        $this->assertSame(0, Task::sidebarBadgeCounts($this->admin)['baa']);

        $responseAfterApproved = $this->actingAs($this->salesA)->get(route('tasks.index'));
        $responseAfterApproved->assertOk();
        $responseAfterApproved->assertSee('title="Sedang dikerjakan: 0"', false);
    }

    public function test_sidebar_uses_single_aggregate_query(): void
    {
        Task::factory()->count(5)->create([
            'status' => Task::STATUS_IN_PROGRESS,
            'user_id' => $this->salesA->id,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $counts = SidebarComposer::getCategoryBadgeCounts($this->admin);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(1, $queries, 'Should execute exactly one aggregate query for all categories.');
        $this->assertStringContainsString('group by', strtolower($queries[0]['query']));
    }
}
