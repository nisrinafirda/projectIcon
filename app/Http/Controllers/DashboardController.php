<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    //
    /**
     * Display the main dashboard based on user role.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        }

        return $this->userDashboard($user);
    }

    /**
     * Dashboard view for Administrator.
     */
    protected function adminDashboard(): View
    {
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        // 1. Overall counts
        $totalTasks = Task::count();
        $totalUsers = User::where('role', 'user')->count();
        $pendingTasksCount = Task::whereIn('status', [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS])->count();
        $submittedTasksCount = Task::where('status', Task::STATUS_SUBMITTED)->count();
        $completedTasksCount = Task::where('status', Task::STATUS_APPROVED)->count();
        $rejectedTasksCount = Task::where('status', Task::STATUS_REJECTED)->count();

        // 2. Category specific stats with "deadline besok" analysis
        $categories = Task::categories();
        $categorySummary = [];

        foreach ($categories as $key => $label) {
            $catTotal = Task::byCategory($key)->count();
            $catUnfinished = Task::byCategory($key)
                ->whereIn('status', [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS, Task::STATUS_REJECTED])
                ->count();
            $catDueTomorrow = Task::byCategory($key)
                ->whereIn('status', [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS, Task::STATUS_REJECTED])
                ->whereDate('due_date', $tomorrow)
                ->count();
            $catOverdue = Task::byCategory($key)
                ->whereIn('status', [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS, Task::STATUS_REJECTED])
                ->whereDate('due_date', '<', $today)
                ->count();

            $categorySummary[$key] = [
                'label' => $label,
                'total' => $catTotal,
                'unfinished' => $catUnfinished,
                'due_tomorrow' => $catDueTomorrow,
                'overdue' => $catOverdue,
            ];
        }

        // 3. Urgent tasks (due tomorrow or overdue)
        $urgentTasks = Task::with('user')
            ->whereIn('status', [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS, Task::STATUS_REJECTED])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $tomorrow)
            ->orderBy('due_date', 'asc')
            ->take(8)
            ->get();

        // 4. Tasks awaiting admin approval
        $pendingReviews = Task::with('user')
            ->where('status', Task::STATUS_SUBMITTED)
            ->orderBy('updated_at', 'desc')
            ->take(6)
            ->get();

        // 5. Chart Data: Status Distribution
        $statusChartData = [
            'labels' => ['Menunggu', 'Sedang Dikerjakan', 'Menunggu Review', 'Selesai', 'Revisi'],
            'data' => [
                Task::where('status', Task::STATUS_PENDING)->count(),
                Task::where('status', Task::STATUS_IN_PROGRESS)->count(),
                $submittedTasksCount,
                $completedTasksCount,
                $rejectedTasksCount,
            ],
        ];

        // 6. Chart Data: Category Breakdown
        $categoryChartData = [
            'labels' => array_values($categories),
            'data' => [
                Task::byCategory(Task::CATEGORY_SSO_OPEN)->count(),
                Task::byCategory(Task::CATEGORY_BAA)->count(),
                Task::byCategory(Task::CATEGORY_BAI)->count(),
                Task::byCategory(Task::CATEGORY_EXCEPTION)->count(),
                Task::byCategory(Task::CATEGORY_KONTRAK_EXP)->count(),
            ],
        ];

        return view('dashboard.admin', compact(
            'totalTasks',
            'totalUsers',
            'pendingTasksCount',
            'submittedTasksCount',
            'completedTasksCount',
            'rejectedTasksCount',
            'categorySummary',
            'urgentTasks',
            'pendingReviews',
            'statusChartData',
            'categoryChartData'
        ));
    }

    /**
     * Dashboard view for Employee (User).
     */
    protected function userDashboard(User $user): View
    {
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        $userQuery = Task::where('user_id', $user->id);

        $totalTasks = (clone $userQuery)->count();
        $pendingCount = (clone $userQuery)->where('status', Task::STATUS_PENDING)->count();
        $inProgressCount = (clone $userQuery)->where('status', Task::STATUS_IN_PROGRESS)->count();
        $submittedCount = (clone $userQuery)->where('status', Task::STATUS_SUBMITTED)->count();
        $completedCount = (clone $userQuery)->where('status', Task::STATUS_APPROVED)->count();
        $rejectedCount = (clone $userQuery)->where('status', Task::STATUS_REJECTED)->count();

        // Unfinished total
        $unfinishedCount = $pendingCount + $inProgressCount + $rejectedCount;

        // Tasks due soon or overdue
        $dueSoonTasks = (clone $userQuery)
            ->whereIn('status', [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS, Task::STATUS_REJECTED])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $tomorrow)
            ->orderBy('due_date', 'asc')
            ->get();

        // Tasks per category for this user
        $categories = Task::categories();
        $userCategories = [];
        foreach ($categories as $key => $label) {
            $catTasks = (clone $userQuery)->where('category', $key);
            $userCategories[$key] = [
                'label' => $label,
                'total' => (clone $catTasks)->count(),
                'unfinished' => (clone $catTasks)->whereIn('status', [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS, Task::STATUS_REJECTED])->count(),
                'completed' => (clone $catTasks)->where('status', Task::STATUS_APPROVED)->count(),
            ];
        }

        // Recent active tasks
        $activeTasks = (clone $userQuery)
            ->whereIn('status', [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS, Task::STATUS_REJECTED])
            ->orderBy('due_date', 'asc')
            ->take(6)
            ->get();

        // Status Chart Data for this user
        $statusChartData = [
            'labels' => ['Menunggu', 'Sedang Dikerjakan', 'Menunggu Review', 'Selesai', 'Revisi'],
            'data' => [
                $pendingCount,
                $inProgressCount,
                $submittedCount,
                $completedCount,
                $rejectedCount,
            ],
        ];

        return view('dashboard.user', compact(
            'user',
            'totalTasks',
            'unfinishedCount',
            'pendingCount',
            'inProgressCount',
            'submittedCount',
            'completedCount',
            'rejectedCount',
            'dueSoonTasks',
            'userCategories',
            'activeTasks',
            'statusChartData'
        ));
    }
}
