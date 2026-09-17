<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskController extends Controller
{
    //
    /**
     * Display a listing of tasks.
     */
    public function index(Request $request, ?string $category = null): View
    {
        $user = Auth::user();
        $categories = Task::categories();

        if ($category && ! array_key_exists($category, $categories)) {
            abort(404, 'Kategori tugas tidak valid.');
        }

        $query = Task::with(['user', 'assigner']);

        // Scope to user's tasks if not admin
        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($category) {
            $query->where('category', $category);
        } elseif ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('service_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kp')) {
            $query->where('kp', $request->input('kp'));
        }

        if ($request->filled('kategori_segmen')) {
            $query->where('kategori_segmen', $request->input('kategori_segmen'));
        }

        if ($request->filled('deadline_filter')) {
            if ($request->input('deadline_filter') === 'tomorrow') {
                $query->whereDate('due_date', now()->addDay());
            } elseif ($request->input('deadline_filter') === 'today') {
                $query->whereDate('due_date', now());
            } elseif ($request->input('deadline_filter') === 'overdue') {
                $query->whereDate('due_date', '<', now())
                    ->where('status', '!=', Task::STATUS_APPROVED);
            }
        }

        $tasks = $query->orderBy('due_date', 'asc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $employees = $user->isAdmin() ? User::where('role', 'user')->orderBy('name')->get() : collect();

        $currentCategoryLabel = $category ? ($categories[$category] ?? $category) : 'Semua Tugas';

        // Specific metrics for SO Open page (matches reference card design)
        $soMetrics = null;
        if ($category === 'sso_open') {
            $baseSoQuery = Task::where('category', 'sso_open');
            if (! $user->isAdmin()) {
                $baseSoQuery->where('user_id', $user->id);
            }

            $totalSo = (clone $baseSoQuery)->count();
            $doneSo = (clone $baseSoQuery)->where('status', Task::STATUS_APPROVED)->count();
            $processSo = (clone $baseSoQuery)->whereIn('status', [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS, Task::STATUS_SUBMITTED, Task::STATUS_REJECTED])->count();
            $donePercent = $totalSo > 0 ? round(($doneSo / $totalSo) * 100, 1) : 0;
            $processPercent = $totalSo > 0 ? round(($processSo / $totalSo) * 100, 1) : 0;

            $kpStats = [];
            foreach (Task::kpList() as $kpKey => $kpName) {
                $kpQuery = (clone $baseSoQuery)->where('kp', $kpKey);
                $kpTotal = (clone $kpQuery)->count();
                $kpDone = (clone $kpQuery)->where('status', Task::STATUS_APPROVED)->count();
                $kpProcess = (clone $kpQuery)->whereIn('status', [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS, Task::STATUS_SUBMITTED, Task::STATUS_REJECTED])->count();
                $kpPercent = $kpTotal > 0 ? round(($kpDone / $kpTotal) * 100) : 0;

                $kpStats[$kpKey] = [
                    'name' => $kpName,
                    'total' => $kpTotal,
                    'done' => $kpDone,
                    'process' => $kpProcess,
                    'percent' => $kpPercent,
                ];
            }

            $soMetrics = [
                'total' => $totalSo,
                'done' => $doneSo,
                'done_percent' => $donePercent,
                'process' => $processSo,
                'process_percent' => $processPercent,
                'kp_stats' => $kpStats,
            ];
        }

        $kpList = Task::kpList();
        $kategoriSegmenList = Task::kategoriSegmenList();

        return view('tasks.index', compact(
            'tasks',
            'category',
            'categories',
            'employees',
            'currentCategoryLabel',
            'soMetrics',
            'kpList',
            'kategoriSegmenList'
        ));
    }

    /**
     * Show the form for creating a new task.
     */
    public function create(): View
    {
        $categories = Task::categories();
        $kpList = Task::kpList();
        $kategoriSegmenList = Task::kategoriSegmenList();
        $employees = User::where('role', 'user')->orderBy('name')->get();

        return view('tasks.create', compact('categories', 'kpList', 'kategoriSegmenList', 'employees'));
    }

    /**
     * Store a newly created task.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', Rule::in(array_keys(Task::categories()))],
            'kp' => ['nullable', 'string', Rule::in(array_keys(Task::kpList()))],
            'kategori_segmen' => ['nullable', 'string', Rule::in(array_keys(Task::kategoriSegmenList()))],
            'document_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tasks')->where(function ($query) use ($request) {
                    return $query->where('category', $request->input('category'));
                }),
            ],
            'title' => ['required', 'string', 'max:255'],
            'user_id' => ['required', 'exists:users,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'service_type' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', 'string', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
        ], [
            'document_number.unique' => 'Nomor dokumen ini sudah terdaftar pada kategori tersebut. Mohon gunakan nomor unik.',
        ]);

        $validated['assigned_by'] = Auth::id();
        $validated['status'] = Task::STATUS_PENDING;

        $task = Task::create($validated);

        return redirect()->route('tasks.show', $task)
            ->with('success', "Tugas '{$task->title}' berhasil dibuat dan ditugaskan.");
    }

    /**
     * Display the specified task.
     */
    public function show(Task $task): View
    {
        $user = Auth::user();

        // Non-admin can only see tasks assigned to them
        if (! $user->isAdmin() && $task->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke tugas ini.');
        }

        $task->load(['user', 'assigner']);

        return view('tasks.show', compact('task'));
    }

    /**
     * Show the form for editing the specified task.
     */
    public function edit(Task $task): View
    {
        $categories = Task::categories();
        $kpList = Task::kpList();
        $kategoriSegmenList = Task::kategoriSegmenList();
        $employees = User::where('role', 'user')->orderBy('name')->get();

        return view('tasks.edit', compact('task', 'categories', 'kpList', 'kategoriSegmenList', 'employees'));
    }

    /**
     * Update the specified task.
     */
    public function update(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', Rule::in(array_keys(Task::categories()))],
            'kp' => ['nullable', 'string', Rule::in(array_keys(Task::kpList()))],
            'kategori_segmen' => ['nullable', 'string', Rule::in(array_keys(Task::kategoriSegmenList()))],
            'document_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tasks')->where(function ($query) use ($request) {
                    return $query->where('category', $request->input('category'));
                })->ignore($task->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'user_id' => ['required', 'exists:users,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'service_type' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', 'string', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
        ], [
            'document_number.unique' => 'Nomor dokumen ini sudah digunakan pada tugas lain dalam kategori yang sama.',
        ]);

        $task->update($validated);

        return redirect()->route('tasks.show', $task)
            ->with('success', 'Data tugas berhasil diperbarui.');
    }

    /**
     * Remove the specified task.
     */
    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return redirect()->route('tasks.index')
            ->with('success', 'Tugas berhasil dihapus.');
    }

    /**
     * User updates progress / start working on task.
     */
    public function startWork(Task $task): RedirectResponse
    {
        $user = Auth::user();

        if ($task->user_id !== $user->id && ! $user->isAdmin()) {
            abort(403);
        }

        if ($task->status === Task::STATUS_PENDING) {
            $task->update(['status' => Task::STATUS_IN_PROGRESS]);
        }

        return back()->with('success', 'Status tugas diperbarui: Sedang Dikerjakan.');
    }

    /**
     * User submits the task for admin review.
     */
    public function submit(Request $request, Task $task): RedirectResponse
    {
        $user = Auth::user();

        if ($task->user_id !== $user->id && ! $user->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'submission_notes' => ['required', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,zip,doc,docx,xls,xlsx', 'max:10240'],
        ]);

        $updateData = [
            'status' => Task::STATUS_SUBMITTED,
            'submission_notes' => $request->input('submission_notes'),
            'completed_at' => now(),
        ];

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('submissions', 'public');
            $updateData['submission_attachment'] = $path;
        }

        $task->update($updateData);

        return redirect()->route('tasks.show', $task)
            ->with('success', 'Tugas berhasil diserahkan! Menunggu konfirmasi dan verifikasi dari Administrator.');
    }

    /**
     * Admin view for tasks waiting for verification.
     */
    public function verifikasi(Request $request): View
    {
        $query = Task::with(['user', 'assigner'])
            ->where('status', Task::STATUS_SUBMITTED);

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('kp')) {
            $query->where('kp', $request->input('kp'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $pendingTasks = $query->orderBy('updated_at', 'desc')->paginate(15)->withQueryString();
        $categories = Task::categories();
        $kpList = Task::kpList();

        return view('tasks.verifikasi', compact('pendingTasks', 'categories', 'kpList'));
    }

    /**
     * Admin approves or rejects the submitted task.
     */
    public function review(Request $request, Task $task): RedirectResponse
    {
        $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($request->input('action') === 'approve') {
            $task->update([
                'status' => Task::STATUS_APPROVED,
                'admin_notes' => $request->input('admin_notes') ?: 'Disetujui oleh Administrator.',
                'reviewed_at' => now(),
            ]);

            $message = 'Tugas telah disetujui (Approved) dan ditandai selesai!';
        } else {
            $task->update([
                'status' => Task::STATUS_REJECTED,
                'admin_notes' => $request->input('admin_notes') ?: 'Tugas ditolak dan perlu direvisi.',
                'reviewed_at' => now(),
            ]);

            $message = 'Tugas ditolak (Rejected) dan dikembalikan ke karyawan untuk revisi.';
        }

        return redirect()->route('tasks.show', $task)->with('success', $message);
    }

    /**
     * API to check if document number already exists in a category.
     */
    public function checkDuplicate(Request $request): JsonResponse
    {
        $category = $request->query('category');
        $documentNumber = $request->query('document_number');
        $excludeId = $request->query('exclude_id');

        if (! $category || ! $documentNumber) {
            return response()->json(['exists' => false]);
        }

        $query = Task::where('category', $category)
            ->where('document_number', $documentNumber);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $existing = $query->first(['id', 'title', 'document_number', 'user_id']);

        if ($existing) {
            return response()->json([
                'exists' => true,
                'message' => "Perhatian: Nomor dokumen '{$documentNumber}' sudah pernah terdaftar pada tugas '{$existing->title}'!",
            ]);
        }

        return response()->json([
            'exists' => false,
            'message' => 'Nomor dokumen tersedia (belum pernah digunakan).',
        ]);
    }
}
