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

        // Dynamic metrics for category & dashboard cards (shown on all menus for all roles)
        $baseMetricsQuery = Task::query();
        if ($category) {
            $baseMetricsQuery->where('category', $category);
        }
        if (! $user->isAdmin()) {
            $baseMetricsQuery->where('user_id', $user->id);
        }

        $totalCount = (clone $baseMetricsQuery)->count();
        $doneCount = (clone $baseMetricsQuery)->where('status', Task::STATUS_APPROVED)->count();
        $processCount = (clone $baseMetricsQuery)->whereIn('status', [Task::STATUS_IN_PROGRESS, Task::STATUS_SUBMITTED, Task::STATUS_REJECTED])->count();
        $donePercent = $totalCount > 0 ? round(($doneCount / $totalCount) * 100, 1) : 0;
        $processPercent = $totalCount > 0 ? round(($processCount / $totalCount) * 100, 1) : 0;

        $plnCount = (clone $baseMetricsQuery)->where('kategori_segmen', Task::SEGMEN_PLN)->count();
        $publikCount = (clone $baseMetricsQuery)->where('kategori_segmen', Task::SEGMEN_PUBLIK)->count();

        // Financial & growth estimates tailored per category
        $categoryFinances = [
            'sso_open' => [
                'total_nilai' => 'Rp 5,47 M',
                'nilai_sub' => 'PDL: Rp 5,28 M | PPB: Rp 191,6 Jt',
                'net_delta' => '+Rp 5,23 M',
                'unit' => 'SO',
                'kp' => [
                    'surabaya' => ['nilai' => 'Rp 4,56 Miliar', 'porsi' => '211 PDL / 228 PPB'],
                    'malang' => ['nilai' => 'Rp 376 Juta', 'porsi' => '6 PDL / 45 PPB'],
                    'madiun' => ['nilai' => 'Rp 171,8 Juta', 'porsi' => '8 PDL / 1 PPB'],
                    'jember' => ['nilai' => 'Rp 359,4 Juta', 'porsi' => '6 PDL / 1 PPB'],
                ],
            ],
            'baa' => [
                'total_nilai' => 'Rp 3,82 M',
                'nilai_sub' => 'PLN: Rp 2,15 M | Publik: Rp 1,67 M',
                'net_delta' => '+Rp 3,15 M',
                'unit' => 'BAA',
                'kp' => [
                    'surabaya' => ['nilai' => 'Rp 2,90 Miliar', 'porsi' => '142 PDL / 115 PPB'],
                    'malang' => ['nilai' => 'Rp 420 Juta', 'porsi' => '12 PDL / 28 PPB'],
                    'madiun' => ['nilai' => 'Rp 280 Juta', 'porsi' => '10 PDL / 14 PPB'],
                    'jember' => ['nilai' => 'Rp 220 Juta', 'porsi' => '8 PDL / 12 PPB'],
                ],
            ],
            'bai' => [
                'total_nilai' => 'Rp 2,45 M',
                'nilai_sub' => 'PLN: Rp 1,55 M | Publik: Rp 900 Jt',
                'net_delta' => '+Rp 1,98 M',
                'unit' => 'BAI',
                'kp' => [
                    'surabaya' => ['nilai' => 'Rp 1,80 Miliar', 'porsi' => '95 PDL / 82 PPB'],
                    'malang' => ['nilai' => 'Rp 310 Juta', 'porsi' => '8 PDL / 16 PPB'],
                    'madiun' => ['nilai' => 'Rp 190 Juta', 'porsi' => '5 PDL / 11 PPB'],
                    'jember' => ['nilai' => 'Rp 150 Juta', 'porsi' => '4 PDL / 9 PPB'],
                ],
            ],
            'exception' => [
                'total_nilai' => 'Rp 1,12 M',
                'nilai_sub' => 'PLN: Rp 820 Jt | Publik: Rp 300 Jt',
                'net_delta' => '+Rp 850 Jt',
                'unit' => 'EXC',
                'kp' => [
                    'surabaya' => ['nilai' => 'Rp 750 Juta', 'porsi' => '42 PDL / 35 PPB'],
                    'malang' => ['nilai' => 'Rp 150 Juta', 'porsi' => '4 PDL / 8 PPB'],
                    'madiun' => ['nilai' => 'Rp 120 Juta', 'porsi' => '3 PDL / 6 PPB'],
                    'jember' => ['nilai' => 'Rp 100 Juta', 'porsi' => '2 PDL / 5 PPB'],
                ],
            ],
            'kontrak_exp' => [
                'total_nilai' => 'Rp 4,60 M',
                'nilai_sub' => 'PLN: Rp 2,80 M | Publik: Rp 1,80 M',
                'net_delta' => '+Rp 4,10 M',
                'unit' => 'KTR',
                'kp' => [
                    'surabaya' => ['nilai' => 'Rp 3,20 Miliar', 'porsi' => '160 PDL / 130 PPB'],
                    'malang' => ['nilai' => 'Rp 650 Juta', 'porsi' => '15 PDL / 32 PPB'],
                    'madiun' => ['nilai' => 'Rp 450 Juta', 'porsi' => '12 PDL / 18 PPB'],
                    'jember' => ['nilai' => 'Rp 300 Juta', 'porsi' => '9 PDL / 14 PPB'],
                ],
            ],
            'default' => [
                'total_nilai' => 'Rp 17,46 M',
                'nilai_sub' => 'PLN: Rp 10,28 M | Publik: Rp 7,18 M',
                'net_delta' => '+Rp 15,31 M',
                'unit' => 'Tugas',
                'kp' => [
                    'surabaya' => ['nilai' => 'Rp 13,21 Miliar', 'porsi' => '650 PDL / 580 PPB'],
                    'malang' => ['nilai' => 'Rp 1,90 Miliar', 'porsi' => '45 PDL / 129 PPB'],
                    'madiun' => ['nilai' => 'Rp 1,21 Miliar', 'porsi' => '38 PDL / 50 PPB'],
                    'jember' => ['nilai' => 'Rp 1,13 Miliar', 'porsi' => '29 PDL / 41 PPB'],
                ],
            ],
        ];

        $financeConfig = $categoryFinances[$category ?? 'default'] ?? $categoryFinances['default'];
        $unit = $financeConfig['unit'] ?? 'Data';

        $kpStats = [];
        foreach (Task::kpList() as $kpKey => $kpName) {
            $kpQuery = (clone $baseMetricsQuery)->where('kp', $kpKey);
            $kpTotal = (clone $kpQuery)->count();
            $kpDone = (clone $kpQuery)->where('status', Task::STATUS_APPROVED)->count();
            $kpProcess = (clone $kpQuery)->whereIn('status', [Task::STATUS_IN_PROGRESS, Task::STATUS_SUBMITTED, Task::STATUS_REJECTED])->count();
            $kpPercent = $kpTotal > 0 ? round(($kpDone / $kpTotal) * 100) : 0;
            $kpPln = (clone $kpQuery)->where('kategori_segmen', Task::SEGMEN_PLN)->count();
            $kpPublik = (clone $kpQuery)->where('kategori_segmen', Task::SEGMEN_PUBLIK)->count();

            $kpFinance = $financeConfig['kp'][$kpKey] ?? ['nilai' => 'Rp 100 Juta', 'porsi' => '50 PLN / 50 Publik'];

            $kpStats[$kpKey] = [
                'name' => $kpName,
                'total' => $kpTotal,
                'done' => $kpDone,
                'process' => $kpProcess,
                'percent' => $kpPercent,
                'pln' => $kpPln,
                'publik' => $kpPublik,
                'nilai' => $kpFinance['nilai'],
                'porsi' => $kpFinance['porsi'],
            ];
        }

        $categoryMetrics = [
            'total' => $totalCount,
            'done' => $doneCount,
            'done_percent' => $donePercent,
            'process' => $processCount,
            'process_percent' => $processPercent,
            'pln_count' => $plnCount,
            'publik_count' => $publikCount,
            'total_nilai' => $financeConfig['total_nilai'],
            'nilai_sub' => $financeConfig['nilai_sub'],
            'net_delta' => $financeConfig['net_delta'],
            'unit' => $unit,
            'kp_stats' => $kpStats,
        ];

        $soMetrics = $categoryMetrics;
        $kpList = Task::kpList();
        $kategoriSegmenList = Task::kategoriSegmenList();

        return view('tasks.index', compact(
            'tasks',
            'category',
            'categories',
            'employees',
            'currentCategoryLabel',
            'categoryMetrics',
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
        $validated['status'] = Task::STATUS_IN_PROGRESS;

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
     * Quickly toggle task completion via checkbox (role user or admin).
     */
    public function toggleComplete(Request $request, Task $task): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        if (! $user->isAdmin() && $task->user_id !== $user->id) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk tugas ini.'], 403);
            }
            abort(403, 'Anda tidak memiliki hak akses untuk tugas ini.');
        }

        // Admin: toggle between approved ↔ in_progress directly
        if ($user->isAdmin()) {
            $wasApproved = $task->status === Task::STATUS_APPROVED;

            if ($wasApproved) {
                $task->update([
                    'status' => Task::STATUS_IN_PROGRESS,
                    'completed_at' => null,
                ]);
                $isCompleted = false;
                $message = 'Tugas kembali ditandai sebagai Sedang Dikerjakan.';
            } else {
                $task->update([
                    'status' => Task::STATUS_APPROVED,
                    'completed_at' => now(),
                    'reviewed_at' => now(),
                    'admin_notes' => $request->input('admin_notes') ?: 'Disetujui oleh Administrator.',
                ]);
                $isCompleted = true;
                $message = 'Tugas berhasil disetujui dan ditandai selesai!';
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'task_id' => $task->id,
                    'status' => $task->status,
                    'status_label' => $task->status_label,
                    'is_completed' => $isCompleted,
                    'message' => $message,
                ]);
            }

            return back()->with('success', $message);
        }

        // User: if already submitted/approved, revert to in_progress
        if (in_array($task->status, [Task::STATUS_SUBMITTED, Task::STATUS_APPROVED])) {
            $task->update([
                'status' => Task::STATUS_IN_PROGRESS,
                'completed_at' => null,
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'task_id' => $task->id,
                    'status' => $task->status,
                    'status_label' => $task->status_label,
                    'is_completed' => false,
                    'message' => 'Tugas kembali ditandai sebagai Sedang Dikerjakan.',
                ]);
            }

            return back()->with('success', 'Tugas kembali ditandai sebagai Sedang Dikerjakan.');
        }

        // User: overdue tasks MUST provide comments
        if ($task->is_overdue && ! $request->input('comments')) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'requires_reason' => true,
                    'message' => 'Tugas ini sudah melewati deadline! Silakan isi komentar alasan keterlambatan.',
                ], 422);
            }

            return back()->with('error', 'Tugas terlambat! Komentar alasan keterlambatan wajib diisi.');
        }

        // User: submit for admin review (status → submitted)
        $updateData = [
            'status' => Task::STATUS_SUBMITTED,
            'completed_at' => now(),
        ];

        if ($request->input('comments')) {
            $updateData['comments'] = $request->input('comments');
        }

        $task->update($updateData);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'task_id' => $task->id,
                'status' => $task->status,
                'status_label' => $task->status_label,
                'is_completed' => false,
                'is_submitted' => true,
                'message' => 'Tugas berhasil dikirim untuk persetujuan Admin.',
            ]);
        }

        return back()->with('success', 'Tugas berhasil dikirim untuk persetujuan Admin.');
    }

    /**
     * Save a comment on a task without changing its status.
     * Allows employees to explain why overdue tasks are late.
     */
    public function saveComment(Request $request, Task $task): JsonResponse
    {
        $user = Auth::user();

        if (! $user->isAdmin() && $task->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk tugas ini.'], 403);
        }

        $request->validate([
            'comments' => ['required', 'string', 'max:1000'],
        ]);

        $task->update(['comments' => $request->input('comments')]);

        return response()->json([
            'success' => true,
            'message' => 'Komentar berhasil disimpan.',
            'comments' => $task->comments,
        ]);
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

        $rules = [
            'submission_notes' => ['required', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,zip,doc,docx,xls,xlsx', 'max:10240'],
        ];

        if ($task->is_overdue) {
            $rules['late_reason'] = ['required', 'string', 'max:1000'];
        }

        $request->validate($rules, [
            'late_reason.required' => 'Karena tugas terlambat, alasan keterlambatan wajib diisi.',
        ]);

        $updateData = [
            'status' => Task::STATUS_SUBMITTED,
            'submission_notes' => $request->input('submission_notes'),
            'completed_at' => now(),
        ];

        if ($task->is_overdue) {
            $updateData['late_reason'] = $request->input('late_reason');
        }

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
