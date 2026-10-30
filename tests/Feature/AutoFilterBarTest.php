<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoFilterBarTest extends TestCase
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
            'nip' => 'NIP202',
        ]);
    }

    public function test_dropdown_filter_parameters_apply_correctly_and_reset_page_to_1(): void
    {
        // Create 26 tasks in KP Surabaya, and 2 in KP Malang
        for ($i = 1; $i <= 26; $i++) {
            Task::factory()->create([
                'category' => Task::CATEGORY_SSO_OPEN,
                'customer_name' => sprintf('Customer S %02d', $i),
                'kp' => Task::KP_SURABAYA,
                'status' => Task::STATUS_IN_PROGRESS,
                'user_id' => $this->user->id,
            ]);
        }

        Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'customer_name' => 'Customer M 01',
            'kp' => Task::KP_MALANG,
            'status' => Task::STATUS_IN_PROGRESS,
            'user_id' => $this->user->id,
        ]);

        // Requesting page 2 of unfiltered category
        $page2Response = $this->actingAs($this->admin)->get(route('tasks.category', [
            'category' => 'sso_open',
            'per_page' => 25,
            'page' => 2,
        ]));
        $page2Response->assertOk();
        $page2Response->assertSee('Customer S 26');

        // When dropdown KP is changed to Malang, page parameter is omitted (reset to 1)
        $filterResponse = $this->actingAs($this->admin)->get(route('tasks.category', [
            'category' => 'sso_open',
            'kp' => Task::KP_MALANG,
        ]));
        $filterResponse->assertOk();
        $filterResponse->assertSee('Customer M 01');
        $filterResponse->assertDontSee('Customer S 01');
        $filterResponse->assertSee('Menampilkan 1 - 1 dari 1 data', false);
    }

    public function test_status_and_deadline_and_category_dropdown_filters(): void
    {
        Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'customer_name' => 'PT Active Open',
            'status' => Task::STATUS_IN_PROGRESS,
            'kategori_segmen' => Task::SEGMEN_PLN,
            'due_date' => Carbon::now()->addDays(5),
            'user_id' => $this->user->id,
        ]);

        Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'customer_name' => 'PT Approved Done',
            'status' => Task::STATUS_APPROVED,
            'kategori_segmen' => Task::SEGMEN_PUBLIK,
            'due_date' => Carbon::now()->subDays(2),
            'user_id' => $this->user->id,
        ]);

        // Filter status = in_progress
        $respStatus = $this->actingAs($this->admin)->get(route('tasks.category', [
            'category' => 'sso_open',
            'status' => 'in_progress',
        ]));
        $respStatus->assertOk();
        $respStatus->assertSee('PT Active Open');
        $respStatus->assertDontSee('PT Approved Done');

        // Filter status = approved
        $respApproved = $this->actingAs($this->admin)->get(route('tasks.category', [
            'category' => 'sso_open',
            'status' => 'approved',
        ]));
        $respApproved->assertOk();
        $respApproved->assertSee('PT Approved Done');
        $respApproved->assertDontSee('PT Active Open');

        // Filter kategori segmen = pln
        $respSegmen = $this->actingAs($this->admin)->get(route('tasks.category', [
            'category' => 'sso_open',
            'kategori_segmen' => 'pln',
        ]));
        $respSegmen->assertOk();
        $respSegmen->assertSee('PT Active Open');
        $respSegmen->assertDontSee('PT Approved Done');

        // Filter deadline on customer detail view
        $customerTaskOverdue = Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'customer_name' => 'PT Customer Detail Test',
            'status' => Task::STATUS_IN_PROGRESS,
            'service_type' => 'Service Overdue Test',
            'due_date' => Carbon::now()->subDays(3),
            'user_id' => $this->user->id,
        ]);

        $customerTaskFuture = Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'customer_name' => 'PT Customer Detail Test',
            'status' => Task::STATUS_IN_PROGRESS,
            'service_type' => 'Service Future Test',
            'due_date' => Carbon::now()->addDays(10),
            'user_id' => $this->user->id,
        ]);

        $respOverdue = $this->actingAs($this->admin)->get(route('tasks.category.customer', [
            'category' => 'sso_open',
            'customer' => 'pt-customer-detail-test',
            'deadline_filter' => 'overdue',
        ]));
        $respOverdue->assertOk();
        $respOverdue->assertSee('Service Overdue Test');
        $respOverdue->assertDontSee('Service Future Test');
    }

    public function test_reset_url_clears_all_filters(): void
    {
        Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'customer_name' => 'PT Reset Test 1',
            'status' => Task::STATUS_IN_PROGRESS,
            'user_id' => $this->user->id,
        ]);

        Task::factory()->create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'customer_name' => 'PT Reset Test 2',
            'status' => Task::STATUS_APPROVED,
            'user_id' => $this->user->id,
        ]);

        // Clean reset route returns all tasks for that category
        $response = $this->actingAs($this->admin)->get(route('tasks.category', 'sso_open'));
        $response->assertOk();
        $response->assertSee('PT Reset Test 1');
        $response->assertSee('PT Reset Test 2');
    }

    public function test_view_renders_auto_filter_attributes_and_containers(): void
    {
        $response = $this->actingAs($this->admin)->get(route('tasks.category', 'sso_open'));
        $response->assertOk();

        // Auto-filter form attributes
        $response->assertSee('data-auto-filter="true"', false);
        $response->assertSee('<noscript>', false);
        $response->assertSee('Reset Filter', false);

        // Targeted containers for AJAX replacement
        $response->assertSee('id="task-metrics-container"', false);
        $response->assertSee('id="task-table-container"', false);
        $response->assertSee('id="task-total-badge"', false);
        $response->assertSee('search-spinner', false);

        // Auto-filter script is included
        $response->assertSee('initAutoFilter', false);
    }

    public function test_search_param_is_rendered_and_autofocused(): void
    {
        Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'customer_name' => 'PT Mandiri BAA',
            'service_type' => 'Metronet 100M',
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('tasks.category', [
            'category' => 'baa',
            'search' => 'Mandiri',
        ]));

        $response->assertOk();
        $response->assertSee('value="Mandiri"', false);
        $response->assertSee('PT Mandiri BAA');
    }

    public function test_customer_detail_and_verifikasi_views_have_auto_filter(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_BAA,
            'customer_name' => 'PT Customer Detail',
            'status' => Task::STATUS_SUBMITTED,
            'user_id' => $this->user->id,
        ]);

        // Customer detail view
        $customerResp = $this->actingAs($this->admin)->get(route('tasks.category.customer', [
            'category' => 'baa',
            'customer' => 'pt-customer-detail',
        ]));
        $customerResp->assertOk();
        $customerResp->assertSee('data-auto-filter="true"', false);
        $customerResp->assertSee('id="task-table-container"', false);

        // Verifikasi view
        $verifResp = $this->actingAs($this->admin)->get(route('admin.verifikasi'));
        $verifResp->assertOk();
        $verifResp->assertSee('data-auto-filter="true"', false);
        $verifResp->assertSee('id="task-table-container"', false);
    }
}
