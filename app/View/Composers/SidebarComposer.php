<?php

namespace App\View\Composers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class SidebarComposer
{
    /**
     * Bind category in-progress task counts to the view.
     */
    public function compose(View $view): void
    {
        $view->with('sidebarBadgeCounts', self::getCategoryBadgeCounts());
    }

    /**
     * Get aggregate task counts grouped by category.
     * - Kategori lain: jumlah tugas in_progress.
     * - Verifikasi Kontrak: jumlah On Process (semua yang belum approved).
     *
     * @return array<string, int>
     */
    public static function getCategoryBadgeCounts(?User $user = null): array
    {
        $user = $user ?? auth()->user();

        /** @var array<string, int> $defaultCounts */
        $defaultCounts = [
            Task::CATEGORY_SSO_OPEN => 0,
            Task::CATEGORY_BAI => 0,
            Task::CATEGORY_BAA => 0,
            Task::CATEGORY_EXCEPTION => 0,
            Task::CATEGORY_KONTRAK_EXP => 0,
            Task::CATEGORY_CONTRACT_VERIFICATION => 0,
        ];

        if (! $user) {
            return $defaultCounts;
        }

        $aggregated = Task::query()
            ->where('status', Task::STATUS_IN_PROGRESS)
            ->where('category', '!=', Task::CATEGORY_CONTRACT_VERIFICATION)
            ->when(! $user->isAdmin(), function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id);
            })
            ->selectRaw('category, COUNT(*) as aggregate_count')
            ->groupBy('category')
            ->pluck('aggregate_count', 'category')
            ->all();

        foreach ($aggregated as $category => $count) {
            $catKey = $category === 'so_open' ? Task::CATEGORY_SSO_OPEN : $category;
            if (array_key_exists($catKey, $defaultCounts)) {
                $defaultCounts[$catKey] += (int) $count;
            }
        }

        $defaultCounts[Task::CATEGORY_CONTRACT_VERIFICATION] = self::countContractVerificationOnProcess($user);

        return $defaultCounts;
    }

    /**
     * Hitung Verifikasi Kontrak On Process: semua tugas yang statusnya BUKAN approved.
     * Admin menghitung semua KP. Sales menghitung KP miliknya (kalau KP user belum
     * tersimpan, jatuh ke tugas yang ditugaskan ke user itu).
     */
    public static function countContractVerificationOnProcess(User $user): int
    {
        $query = Task::query()
            ->where('category', Task::CATEGORY_CONTRACT_VERIFICATION)
            ->where('status', '!=', Task::STATUS_APPROVED);

        if (! $user->isAdmin()) {
            $userKp = strtolower(trim((string) ($user->kp ?? '')));

            if ($userKp !== '') {
                $query->whereRaw('LOWER(kp) = ?', [$userKp]);
            } else {
                $query->where('user_id', $user->id);
            }
        }

        return $query->count();
    }
}