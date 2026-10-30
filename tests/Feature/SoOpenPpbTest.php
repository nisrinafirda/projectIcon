<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SoOpenPpbTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $salesUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@icon.co.id',
            'role' => 'admin',
        ]);

        $this->salesUser = User::factory()->create([
            'name' => 'GUMILANG ADI PRAKOSO',
            'email' => 'gumilang@icon.co.id',
            'role' => 'user',
        ]);
    }

    /**
     * Test Task model accessor getIsPpbAttribute.
     */
    public function test_is_ppb_accessor(): void
    {
        // SO Open with Sales -> is_ppb = true
        $task1 = Task::factory()->make([
            'category' => Task::CATEGORY_SSO_OPEN,
            'confirm_to' => 'Sales',
        ]);
        $this->assertTrue($task1->is_ppb);

        // Case insensitivity & trimming
        $task2 = Task::factory()->make([
            'category' => Task::CATEGORY_SSO_OPEN,
            'confirm_to' => '  sales  ',
        ]);
        $this->assertTrue($task2->is_ppb);

        $task3 = Task::factory()->make([
            'category' => Task::CATEGORY_SSO_OPEN,
            'confirm_to' => 'SALES',
        ]);
        $this->assertTrue($task3->is_ppb);

        // PDL or empty -> is_ppb = false
        $task4 = Task::factory()->make([
            'category' => Task::CATEGORY_SSO_OPEN,
            'confirm_to' => 'PDL',
        ]);
        $this->assertFalse($task4->is_ppb);

        $task5 = Task::factory()->make([
            'category' => Task::CATEGORY_SSO_OPEN,
            'confirm_to' => null,
        ]);
        $this->assertFalse($task5->is_ppb);

        // Other category with Sales -> is_ppb = false
        $task6 = Task::factory()->make([
            'category' => Task::CATEGORY_BAA,
            'confirm_to' => 'Sales',
        ]);
        $this->assertFalse($task6->is_ppb);
    }

    /**
     * Test importing SO Open file Project_Monitoring_PPB_2026_1_SO_Open.csv.
     */
    public function test_import_so_open_csv_with_confirm_to_and_empty_rows_skipped(): void
    {
        $filePath = storage_path('app/imports/Project_Monitoring_PPB_2026_1_SO_Open.csv');
        $this->assertFileExists($filePath);

        $file = new UploadedFile($filePath, 'Project_Monitoring_PPB_2026_1_SO_Open.csv', 'text/csv', null, true);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => Task::CATEGORY_SSO_OPEN,
        ]);

        $response->assertRedirect(route('tasks.category', Task::CATEGORY_SSO_OPEN));
        $response->assertSessionHas('success');

        // Exactly 44 tasks should be imported from the file
        $this->assertEquals(44, Task::where('category', Task::CATEGORY_SSO_OPEN)->count());

        // Exactly 6 tasks should have confirm_to = Sales (is_ppb = true)
        $ppbTasks = Task::where('category', Task::CATEGORY_SSO_OPEN)->where('confirm_to', 'Sales')->get();
        $this->assertCount(6, $ppbTasks);
        foreach ($ppbTasks as $ppbTask) {
            $this->assertTrue($ppbTask->is_ppb);
            // Must belong to the sales user Gumilang Adi Prakoso
            $this->assertEquals($this->salesUser->id, $ppbTask->user_id);
            $this->assertEquals('GUMILANG ADI PRAKOSO', $ppbTask->sales_name);
        }

        // Test re-import without duplicates (Rule 7)
        $fileReimport = new UploadedFile($filePath, 'Project_Monitoring_PPB_2026_1_SO_Open.csv', 'text/csv', null, true);
        $reimportResponse = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $fileReimport,
            'duplicate_action' => 'skip',
            'target_category' => Task::CATEGORY_SSO_OPEN,
        ]);

        $reimportResponse->assertRedirect(route('tasks.category', Task::CATEGORY_SSO_OPEN));
        $this->assertEquals(44, Task::where('category', Task::CATEGORY_SSO_OPEN)->count(), 'Re-importing should not create duplicates');
    }

    /**
     * Test badge PPB display on SO Open index page and customer SID page.
     */
    public function test_ppb_badge_displayed_on_views(): void
    {
        $filePath = storage_path('app/imports/Project_Monitoring_PPB_2026_1_SO_Open.csv');
        $file = new UploadedFile($filePath, 'Project_Monitoring_PPB_2026_1_SO_Open.csv', 'text/csv', null, true);

        $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => Task::CATEGORY_SSO_OPEN,
        ]);

        // 1. Check outer page (tasks/index) for SO Open
        $indexResponse = $this->actingAs($this->admin)->get(route('tasks.category', Task::CATEGORY_SSO_OPEN));
        $indexResponse->assertOk();

        // Customer with PPB task should show badge PPB
        $indexResponse->assertSee('BENDAHARA DESA SUGIHAN KEC. JATIROGO');
        $indexResponse->assertSee('DESA KEDUNGJAMBANGAN KECAMATAN BANGILAN');
        $indexResponse->assertSee('PPB');

        // 2. Check SID detail page for customer with PPB
        $customerSlug = 'bendahara-desa-sugihan-kec-jatirogo';
        $customerDetailResponse = $this->actingAs($this->admin)->get(route('tasks.category.customer', [
            'category' => Task::CATEGORY_SSO_OPEN,
            'customer' => $customerSlug,
        ]));
        $customerDetailResponse->assertOk();
        $customerDetailResponse->assertSee('PPB');
        $customerDetailResponse->assertSee('H141601002415');
    }
}
