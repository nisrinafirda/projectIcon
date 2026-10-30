<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncKontrakIdPelangganCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_it_matches_and_updates_id_pelanggan_for_kontrak_exp_from_exception(): void
    {
        // Exception task that has id_pelanggan
        Task::create([
            'category' => Task::CATEGORY_EXCEPTION,
            'document_number' => '140000001951 / 2026-03',
            'id_pelanggan' => 'PEL-99901',
            'customer_name' => 'PT Semen Gresik',
            'title' => 'Task Exception',
            'status' => Task::STATUS_APPROVED,
            'user_id' => $this->user->id,
            'comments' => 'Komentar awal',
        ]);

        // Kontrak Exp task with matching idPelangganProduk (140000001951)
        $kontrakTask = Task::create([
            'category' => Task::CATEGORY_KONTRAK_EXP,
            'document_number' => '140000001951',
            'id_pelanggan' => null,
            'customer_name' => 'PT Semen Gresik',
            'title' => 'Task Kontrak Exp',
            'status' => Task::STATUS_IN_PROGRESS,
            'user_id' => $this->user->id,
            'comments' => 'Komentar penting jangan diubah',
            'confirmed_at' => null,
        ]);

        $this->artisan('tasks:sync-kontrak-id-pelanggan', [
            '--exception-csv' => 'non_existent_file.csv', // Skip csv auto-backfill in test
        ])
            ->expectsOutputToContain('Baris Terisi')
            ->assertSuccessful();

        $kontrakTask->refresh();
        $this->assertSame('PEL-99901', $kontrakTask->id_pelanggan);
        $this->assertSame(Task::STATUS_IN_PROGRESS, $kontrakTask->status);
        $this->assertSame('Komentar penting jangan diubah', $kontrakTask->comments);
        $this->assertNull($kontrakTask->confirmed_at);
    }

    public function test_it_reports_unmatched_tasks_and_shows_samples(): void
    {
        Task::create([
            'category' => Task::CATEGORY_KONTRAK_EXP,
            'document_number' => '999999999999',
            'id_pelanggan' => null,
            'customer_name' => 'PT Unmatched Customer',
            'service_type' => 'METRONET',
            'title' => 'Task Kontrak Exp Unmatched',
            'status' => Task::STATUS_PENDING,
            'user_id' => $this->user->id,
        ]);

        $this->artisan('tasks:sync-kontrak-id-pelanggan', [
            '--exception-csv' => 'non_existent_file.csv',
        ])
            ->expectsOutputToContain('Tidak Ketemu')
            ->expectsOutputToContain('999999999999')
            ->assertSuccessful();
    }

    public function test_dry_run_does_not_modify_database(): void
    {
        Task::create([
            'category' => Task::CATEGORY_EXCEPTION,
            'document_number' => '140000001951 / 2026-03',
            'id_pelanggan' => 'PEL-99901',
            'customer_name' => 'PT Semen Gresik',
            'title' => 'Task Exception',
            'status' => Task::STATUS_APPROVED,
            'user_id' => $this->user->id,
        ]);

        $kontrakTask = Task::create([
            'category' => Task::CATEGORY_KONTRAK_EXP,
            'document_number' => '140000001951',
            'id_pelanggan' => null,
            'customer_name' => 'PT Semen Gresik',
            'title' => 'Task Kontrak Exp',
            'status' => Task::STATUS_IN_PROGRESS,
            'user_id' => $this->user->id,
        ]);

        $this->artisan('tasks:sync-kontrak-id-pelanggan', [
            '--dry-run' => true,
            '--exception-csv' => 'non_existent_file.csv',
        ])->assertSuccessful();

        $kontrakTask->refresh();
        $this->assertNull($kontrakTask->id_pelanggan);
    }
}
