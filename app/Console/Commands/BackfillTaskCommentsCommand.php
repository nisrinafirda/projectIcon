<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('tasks:backfill-comments {--dry-run : Jalankan simulasi tanpa mengubah database}')]
#[Description('Mengisi tasks.comments dari deskripsi kendala untuk tugas yang komentarnya masih kosong')]
class BackfillTaskCommentsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $categories = [
            Task::CATEGORY_SSO_OPEN,
            Task::CATEGORY_BAI,
            Task::CATEGORY_BAA,
            Task::CATEGORY_EXCEPTION,
            Task::CATEGORY_KONTRAK_EXP,
        ];

        $categoryLabels = Task::categories();

        $this->info($isDryRun ? '=== [DRY RUN] SIMULASI BACKFILL KOMENTAR TUGAS ===' : '=== BACKFILL KOMENTAR TUGAS DARI DESKRIPSI KENDALA ===');

        // Find candidate tasks with empty/null comments
        $tasksWithoutComments = Task::where(function ($q): void {
            $q->whereNull('comments')->orWhereRaw("TRIM(comments) = ''");
        })->get();

        $candidatesByCat = [];
        foreach ($categories as $cat) {
            $candidatesByCat[$cat] = [];
        }

        foreach ($tasksWithoutComments as $task) {
            $cat = $task->category;
            if (! in_array($cat, $categories, true)) {
                continue;
            }

            $kendala = Task::extractKendalaFromDescription($task->description);
            if ($kendala !== null && $kendala !== '') {
                $candidatesByCat[$cat][] = [
                    'task' => $task,
                    'kendala' => $kendala,
                ];
            }
        }

        // Display summary table
        $tableRows = [];
        $totalAllTasks = 0;
        $totalAlreadyHasComments = 0;
        $totalWillBackfill = 0;

        foreach ($categories as $cat) {
            $totalInCat = Task::where('category', $cat)->count();
            $hasCommentCount = Task::where('category', $cat)
                ->whereNotNull('comments')
                ->where('comments', '!=', '')
                ->count();
            $willBackfillCount = count($candidatesByCat[$cat]);

            $totalAllTasks += $totalInCat;
            $totalAlreadyHasComments += $hasCommentCount;
            $totalWillBackfill += $willBackfillCount;

            $lbl = $categoryLabels[$cat] ?? strtoupper($cat);
            $note = '';
            if ($cat === Task::CATEGORY_BAI && $willBackfillCount === 0) {
                $note = ' (Detail BAI tidak memiliki teks kendala, dilewati)';
            }

            $tableRows[] = [
                $lbl.$note,
                $totalInCat,
                $hasCommentCount,
                $willBackfillCount,
            ];
        }

        $tableRows[] = [
            'TOTAL',
            $totalAllTasks,
            $totalAlreadyHasComments,
            $totalWillBackfill,
        ];

        $this->table(
            ['Kategori', 'Total Tugas di DB', 'Sudah Punya Komentar', 'Akan Diisi Komentar (Backfill)'],
            $tableRows
        );

        if ($totalWillBackfill === 0) {
            $this->info('Tidak ada tugas berkendala yang perlu di-backfill komentarnya.');

            return self::SUCCESS;
        }

        if ($isDryRun) {
            $this->warn("Mode --dry-run aktif. Total {$totalWillBackfill} tugas dapat diisi komentar, tetapi tidak ada perubahan yang disimpan ke database.");
            $this->info('Jalankan "php artisan tasks:backfill-comments" tanpa --dry-run untuk menerapkan perubahan.');

            return self::SUCCESS;
        }

        // Apply backfill
        $adminUser = User::where('role', 'admin')->first();
        $adminId = $adminUser?->id ?: 1;
        $now = now();
        $updatedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($candidatesByCat as $cat => $items) {
                foreach ($items as $item) {
                    /** @var Task $task */
                    $task = $item['task'];
                    $task->update([
                        'comments' => $item['kendala'],
                        'comment_updated_by' => $adminId,
                        'comment_updated_at' => $now,
                    ]);
                    $updatedCount++;
                }
            }

            ActivityLogger::log(
                ActivityLogger::ACTION_IMPORT_DONE,
                "Backfill komentar tugas: {$updatedCount} tugas berhasil diisi komentar dari kendala detail.",
                null,
                $adminUser,
                null,
                null,
                ['updated_count' => $updatedCount]
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Gagal melakukan backfill komentar: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("✓ Sukses! Berhasil mengisi komentar untuk {$updatedCount} tugas di database.");

        return self::SUCCESS;
    }
}
