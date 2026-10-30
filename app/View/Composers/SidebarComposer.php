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
     * Get aggregate in-progress task counts grouped by category.
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
        ];

        if (! $user) {
            return $defaultCounts;
        }

        $aggregated = Task::query()
            ->where('status', Task::STATUS_IN_PROGRESS)
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

        return $defaultCounts;
    }
}
