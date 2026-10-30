<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs with daily grouping and filters.
     */
    public function index(Request $request): View
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $query = ActivityLog::query()->withCount('details');

        // Role restriction: Sales/User only sees their own logs, Admin sees all
        if (! $currentUser->isAdmin()) {
            $query->where('user_id', $currentUser->id);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhereHas('details', function ($detailQuery) use ($search): void {
                        $detailQuery->where('customer_name', 'like', "%{$search}%")
                            ->orWhere('sid', 'like', "%{$search}%");
                    });
            });
        }

        // Date & Preset Filter Logic
        $preset = $request->input('preset');
        $hasCustomDate = $request->filled('date') || $request->filled('start_date') || $request->filled('end_date') || $request->filled('year');
        $hasOtherFilters = $request->filled('search') || $request->filled('category') || $request->filled('action') || $request->filled('user_id');

        $activePreset = 'today';

        if ($preset === 'yesterday') {
            $activePreset = 'yesterday';
            $query->whereDate('created_at', Carbon::yesterday('Asia/Jakarta')->toDateString());
        } elseif ($preset === 'all') {
            $activePreset = 'all';
        } elseif ($preset === 'today') {
            $activePreset = 'today';
            $query->whereDate('created_at', Carbon::today('Asia/Jakarta')->toDateString());
        } elseif ($hasCustomDate) {
            $activePreset = 'custom';
            if ($request->filled('date')) {
                $query->whereDate('created_at', $request->input('date'));
            } else {
                if ($request->filled('start_date')) {
                    $query->whereDate('created_at', '>=', $request->input('start_date'));
                }
                if ($request->filled('end_date')) {
                    $query->whereDate('created_at', '<=', $request->input('end_date'));
                }
            }
            if ($request->filled('year')) {
                $query->whereYear('created_at', (int) $request->input('year'));
            }
        } elseif ($hasOtherFilters) {
            // When other filters are used without a date or preset, show all matching records
            $activePreset = 'all';
        } else {
            // Default when first loading the page: Hari ini
            $activePreset = 'today';
            $query->whereDate('created_at', Carbon::today('Asia/Jakarta')->toDateString());
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50)->withQueryString();

        // Group the paginated logs by day
        $groupedLogs = $logs->getCollection()->groupBy(function (ActivityLog $log) {
            $tz = 'Asia/Jakarta';
            $date = $log->created_at->timezone($tz);
            $now = Carbon::now($tz);

            if ($date->isSameDay($now)) {
                return 'Hari ini';
            }
            if ($date->isSameDay($now->copy()->subDay())) {
                return 'Kemarin';
            }

            return $date->locale('id')->translatedFormat('l, d F Y');
        });

        $users = $currentUser->isAdmin() ? User::orderBy('name')->get(['id', 'name', 'role']) : collect();
        $categories = Task::categories();
        $actions = ActivityLogger::actionLabels();

        $currentYear = (int) now('Asia/Jakarta')->format('Y');
        $years = range($currentYear, max(2024, $currentYear - 3));

        return view('activity-logs.index', compact(
            'logs',
            'groupedLogs',
            'users',
            'categories',
            'actions',
            'years',
            'activePreset'
        ));
    }

    /**
     * Fetch paginated details for an import activity log.
     */
    public function details(Request $request, ActivityLog $activityLog): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if (! $currentUser->isAdmin() && $activityLog->user_id !== $currentUser->id) {
            abort(403);
        }

        $offset = max(0, $request->integer('offset', 0));
        $limit = min(100, max(1, $request->integer('limit', 50)));

        $total = $activityLog->details()->count();

        $details = $activityLog->details()
            ->orderBy('id', 'asc')
            ->skip($offset)
            ->take($limit)
            ->get();

        $nextOffset = $offset + $details->count();
        $hasMore = $nextOffset < $total;
        $remaining = max(0, $total - $nextOffset);

        return response()->json([
            'details' => $details,
            'offset' => $offset,
            'limit' => $limit,
            'total' => $total,
            'has_more' => $hasMore,
            'remaining' => $remaining,
            'next_offset' => $nextOffset,
        ]);
    }
}
