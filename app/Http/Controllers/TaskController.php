<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

        $baseQuery = Task::query();

        // Scope to user's tasks if not admin
        if (! $user->isAdmin()) {
            $baseQuery->where('user_id', $user->id);
        } elseif ($request->filled('user_id')) {
            $baseQuery->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('sales_name')) {
            $baseQuery->where('sales_name', $request->input('sales_name'));
        }

        if ($category) {
            $baseQuery->where('category', $category);
        } elseif ($request->filled('category')) {
            $baseQuery->where('category', $request->input('category'));
        }

        if ($request->filled('status')) {
            $baseQuery->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $baseQuery->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('service_type', 'like', "%{$search}%")
                    ->orWhere('sales_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kp')) {
            $baseQuery->where('kp', $request->input('kp'));
        }

        if ($request->filled('kategori_segmen')) {
            $baseQuery->where('kategori_segmen', $request->input('kategori_segmen'));
        }

        if ($request->filled('month') && is_numeric($request->input('month'))) {
            $baseQuery->whereMonth('due_date', (int) $request->input('month'));
        }

        if ($request->filled('year') && is_numeric($request->input('year'))) {
            $baseQuery->whereYear('due_date', (int) $request->input('year'));
        }

        // Total task count matching the current filters
        $totalTaskCount = (clone $baseQuery)->count();

        // Per-page validation: 25, 50, 100, 200, 500 (default: 25)
        $perPage = (int) $request->input('per_page', 25);
        if (! in_array($perPage, [25, 50, 100, 200, 500], true)) {
            $perPage = 25;
        }

        // Paginate by customer groups with aggregation
        $customerGroups = (clone $baseQuery)
            ->selectRaw('TRIM(customer_name) as customer_key, MIN(customer_name) as display_name, COUNT(*) as task_count, MIN(due_date) as nearest_deadline, SUM(harga_baru) as total_harga_baru, SUM(rp_exception) as total_rp_exception')
            ->groupByRaw('TRIM(customer_name)')
            ->orderByRaw('MIN(customer_name) ASC')
            ->paginate($perPage)
            ->withQueryString();

        // Load tasks for customers on the current page with eager loading (separate page for exception)
        $customerKeys = $customerGroups->pluck('customer_key')->all();
        $groupedTasks = collect();
        $customerKpMap = collect();
        $customerSalesMap = collect();
        $customerContractMap = collect();

        if (! empty($customerKeys)) {
            $customerMetaQuery = (clone $baseQuery)
                ->whereIn(DB::raw('TRIM(customer_name)'), $customerKeys);

            if ($category === 'kontrak_exp') {
                $customerMeta = $customerMetaQuery
                    ->selectRaw('TRIM(customer_name) as customer_key, kp, sales_name, document_number, description')
                    ->get();

                $customerContractMap = $customerMeta->groupBy('customer_key')
                    ->map(fn ($items) => $items->map(function ($t) {
                        if ($t->description && preg_match('/No\.?\s*Kontrak\s*:\s*([^\r\n]+)/i', $t->description, $matches)) {
                            return trim($matches[1]);
                        }

                        return $t->document_number;
                    })->filter()->unique()->values());
            } else {
                $customerMeta = $customerMetaQuery
                    ->selectRaw('TRIM(customer_name) as customer_key, kp, sales_name')
                    ->distinct()
                    ->get();
            }

            $customerKpMap = $customerMeta->groupBy('customer_key')
                ->map(fn ($items) => $items->pluck('kp')->filter()->unique()->values());

            $customerSalesMap = $customerMeta->groupBy('customer_key')
                ->map(fn ($items) => $items->pluck('sales_name')->filter()->unique()->values());
        }

        // Total Rp Exception per customer across ALL tasks (NOT filtered by status, per requirements)
        $customerRpTotals = collect();
        if ($category === 'exception' && ! empty($customerKeys)) {
            $customerTotalsQuery = Task::where('category', 'exception');
            if (! $user->isAdmin()) {
                $customerTotalsQuery->where('user_id', $user->id);
            } elseif ($request->filled('user_id')) {
                $customerTotalsQuery->where('user_id', $request->integer('user_id'));
            }
            if ($request->filled('sales_name')) {
                $customerTotalsQuery->where('sales_name', $request->input('sales_name'));
            }
            if ($request->filled('kp')) {
                $customerTotalsQuery->where('kp', $request->input('kp'));
            }
            if ($request->filled('kategori_segmen')) {
                $customerTotalsQuery->where('kategori_segmen', $request->input('kategori_segmen'));
            }
            if ($request->filled('search')) {
                $search = $request->input('search');
                $customerTotalsQuery->where(function ($q) use ($search): void {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('service_type', 'like', "%{$search}%")
                        ->orWhere('sales_name', 'like', "%{$search}%");
                });
            }

            if ($request->filled('month') && is_numeric($request->input('month'))) {
                $customerTotalsQuery->whereMonth('due_date', (int) $request->input('month'));
            }

            if ($request->filled('year') && is_numeric($request->input('year'))) {
                $customerTotalsQuery->whereYear('due_date', (int) $request->input('year'));
            }

            $customerRpTotals = $customerTotalsQuery
                ->whereIn(DB::raw('TRIM(customer_name)'), $customerKeys)
                ->selectRaw('TRIM(customer_name) as customer_key, SUM(rp_exception) as total_rp')
                ->groupByRaw('TRIM(customer_name)')
                ->pluck('total_rp', 'customer_key');
        }

        // Sales list for admin filter dropdown
        $salesList = Task::whereNotNull('sales_name')
            ->where('sales_name', '!=', '')
            ->distinct()
            ->orderBy('sales_name')
            ->pluck('sales_name');

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

        if ($category === 'exception') {
            $totalRpException = (float) (clone $baseMetricsQuery)->sum('rp_exception');
            $closeRpException = (float) (clone $baseMetricsQuery)->where('status', Task::STATUS_APPROVED)->sum('rp_exception');
            $closeRpPercent = $totalRpException > 0 ? round(($closeRpException / $totalRpException) * 100, 1) : 0;

            $totalNilai = $this->formatShortRupiah($totalRpException);
            $nilaiSub = number_format($totalCount, 0, ',', '.').' SID';
            $netDelta = $this->formatShortRupiah($closeRpException);
            $netDeltaSub = number_format($closeRpPercent, 1, ',', '.').'% dari total Rp Exception';
        } elseif ($category === 'sso_open') {
            $totalHargaBaru = (float) (clone $baseMetricsQuery)->sum('harga_baru');
            $totalNilai = $this->formatShortRupiah($totalHargaBaru);

            $pdlHarga = (float) (clone $baseMetricsQuery)->where(function ($q): void {
                $q->where('document_number', 'like', 'A%')->orWhere('title', 'like', '%[PDL]%');
            })->sum('harga_baru');
            $ppbHarga = (float) (clone $baseMetricsQuery)->where(function ($q): void {
                $q->where('document_number', 'like', 'B%')->orWhere('title', 'like', '%[PPB]%');
            })->sum('harga_baru');

            $nilaiSub = 'PDL: '.$this->formatShortRupiah($pdlHarga).' | PPB: '.$this->formatShortRupiah($ppbHarga);
            $netDelta = null;
            $netDeltaSub = null;
        } else {
            $totalNilai = $financeConfig['total_nilai'];
            $nilaiSub = $financeConfig['nilai_sub'];
            $netDelta = $financeConfig['net_delta'];
            $netDeltaSub = 'Pertumbuhan Nilai Proyek';
        }

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

            if ($category === 'exception') {
                $kpRpException = (float) (clone $kpQuery)->sum('rp_exception');
                $kpNilai = $this->formatShortRupiah($kpRpException);
            } elseif ($category === 'sso_open') {
                $kpTotalHargaBaru = (float) (clone $kpQuery)->sum('harga_baru');
                $kpNilai = $this->formatShortRupiah($kpTotalHargaBaru);
            } else {
                $kpNilai = $kpFinance['nilai'];
            }

            if ($category === 'sso_open' && $kpTotal > 0) {
                $kpPdl = (clone $kpQuery)->where(function ($q): void {
                    $q->where('document_number', 'like', 'A%')
                        ->orWhere('title', 'like', '%[PDL]%');
                })->count();
                $kpPpb = (clone $kpQuery)->where(function ($q): void {
                    $q->where('document_number', 'like', 'B%')
                        ->orWhere('title', 'like', '%[PPB]%');
                })->count();
                $kpPorsi = "{$kpPdl} PDL / {$kpPpb} PPB";
            } else {
                $kpPorsi = $kpFinance['porsi'];
            }

            $kpStats[$kpKey] = [
                'name' => $kpName,
                'total' => $kpTotal,
                'done' => $kpDone,
                'process' => $kpProcess,
                'percent' => $kpPercent,
                'pln' => $kpPln,
                'publik' => $kpPublik,
                'nilai' => $kpNilai,
                'porsi' => $kpPorsi,
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
            'total_nilai' => $totalNilai,
            'nilai_sub' => $nilaiSub,
            'net_delta' => $netDelta,
            'net_delta_sub' => $netDeltaSub,
            'unit' => $unit,
            'kp_stats' => $kpStats,
        ];

        $soMetrics = $categoryMetrics;
        $kpList = Task::kpList();
        $kategoriSegmenList = Task::kategoriSegmenList();

        return view('tasks.index', compact(
            'customerGroups',
            'groupedTasks',
            'customerKpMap',
            'customerSalesMap',
            'customerContractMap',
            'customerRpTotals',
            'totalTaskCount',
            'salesList',
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
            'priority' => ['nullable', 'string', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
        ], [
            'document_number.unique' => 'Nomor dokumen ini sudah terdaftar pada kategori tersebut. Mohon gunakan nomor unik.',
        ]);

        $validated['priority'] = $validated['priority'] ?? 'medium';
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

        $task->load(['user', 'assigner', 'mutation']);

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
            'priority' => ['nullable', 'string', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
        ], [
            'document_number.unique' => 'Nomor dokumen ini sudah digunakan pada tugas lain dalam kategori yang sama.',
        ]);

        $validated['priority'] = $validated['priority'] ?? ($task->priority ?: 'medium');
        $task->update($validated);

        if ($request->filled('return_to')) {
            return redirect($request->input('return_to'))
                ->with('success', 'Data tugas berhasil diperbarui.');
        }

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
     * Quickly toggle task completion via checkbox (role user only for own tasks).
     */
    public function toggleComplete(Request $request, Task $task): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        // Admin cannot toggle via this endpoint (admin uses Verifikasi menu)
        if ($user->isAdmin()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Admin hanya dapat memverifikasi tugas melalui menu Verifikasi.'], 403);
            }
            abort(403, 'Admin hanya dapat memverifikasi tugas melalui menu Verifikasi.');
        }

        // Non-admin can only toggle tasks assigned to them
        if ($task->user_id !== $user->id) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk tugas ini.'], 403);
            }
            abort(403, 'Anda tidak memiliki hak akses untuk tugas ini.');
        }

        // If task is already approved by admin, it is locked
        if ($task->status === Task::STATUS_APPROVED) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Tugas telah dikonfirmasi oleh Admin dan tidak dapat diubah.'], 403);
            }
            abort(403, 'Tugas telah dikonfirmasi oleh Admin dan tidak dapat diubah.');
        }

        // User unchecking a submitted task (canceling completion submission)
        if ($task->status === Task::STATUS_SUBMITTED || $request->filled('uncheck')) {
            $task->update([
                'status' => Task::STATUS_IN_PROGRESS,
                'completed_at' => null,
                'done_marked_at' => null,
                'done_marked_by' => null,
            ]);

            $message = 'Tanda selesai dibatalkan. Status tugas kembali menjadi Sedang Dikerjakan.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'task_id' => $task->id,
                    'status' => $task->status,
                    'status_label' => $task->status_label,
                    'is_completed' => false,
                    'message' => $message,
                ]);
            }

            return back()->with('success', $message);
        }

        // User marking task as completed:
        // Overdue validation: must have non-empty comments
        if ($task->is_overdue) {
            $effectiveComment = trim((string) ($request->input('comments') ?: ($request->input('late_reason') ?: $task->comments)));
            if ($effectiveComment === '') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'requires_comment' => true,
                        'requires_reason' => true,
                        'message' => 'Tugas ini terlambat, isi komentar alasan keterlambatan dulu',
                        'errors' => [
                            'comments' => ['Tugas ini terlambat, isi komentar alasan keterlambatan dulu.'],
                            'late_reason' => ['Tugas ini terlambat, isi komentar alasan keterlambatan dulu.'],
                        ],
                    ], 422);
                }

                return back()->withErrors([
                    'comments' => 'Tugas ini terlambat, isi komentar alasan keterlambatan dulu.',
                    'late_reason' => 'Tugas ini terlambat, isi komentar alasan keterlambatan dulu.',
                ])->with('error', 'Tugas ini terlambat, isi komentar alasan keterlambatan dulu.');
            }

            $updateData = [
                'status' => Task::STATUS_SUBMITTED,
                'completed_at' => now(),
                'done_marked_at' => now(),
                'done_marked_by' => $user->id,
                'was_late' => true,
                'late_reason' => $effectiveComment,
            ];

            if ($request->filled('comments')) {
                $updateData['comments'] = $effectiveComment;
            } elseif (! $task->comments) {
                $updateData['comments'] = $effectiveComment;
            }
        } else {
            // Task is not overdue
            $updateData = [
                'status' => Task::STATUS_SUBMITTED,
                'completed_at' => now(),
                'done_marked_at' => now(),
                'done_marked_by' => $user->id,
                'was_late' => false,
            ];

            // If user provided comments, update comments. Otherwise preserve existing comments.
            if ($request->filled('comments') && trim((string) $request->input('comments')) !== '') {
                $updateData['comments'] = trim((string) $request->input('comments'));
            }
        }

        $task->update($updateData);
        $message = 'Tugas berhasil ditandai selesai! Menunggu konfirmasi dari Administrator.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'task_id' => $task->id,
                'status' => $task->status,
                'status_label' => $task->status_label,
                'is_completed' => false,
                'is_submitted' => true,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Save a comment on a task without changing its status.
     * Allows employees to explain why overdue tasks are late or add general notes.
     */
    public function saveComment(Request $request, Task $task): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        if (! $user->isAdmin() && $task->user_id !== $user->id) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk tugas ini.'], 403);
            }
            abort(403, 'Anda tidak memiliki hak akses untuk tugas ini.');
        }

        $validated = $request->validate([
            'comments' => ['required', 'string', 'max:1000'],
        ]);

        $task->update(['comments' => $validated['comments']]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Komentar berhasil disimpan.',
                'comments' => $task->comments,
            ]);
        }

        return back()->with('success', 'Komentar berhasil disimpan.');
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
        $query = Task::with(['user', 'assigner', 'mutation'])
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
                'confirmed_at' => now(),
                'confirmed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            $message = 'Tugas telah dikonfirmasi (Approved) dan ditandai selesai!';
        } else {
            $task->update([
                'status' => Task::STATUS_REJECTED,
                'admin_notes' => $request->input('admin_notes') ?: 'Tugas dikembalikan oleh Administrator untuk perbaikan.',
                'completed_at' => null,
                'done_marked_at' => null,
                'done_marked_by' => null,
                'reviewed_at' => now(),
            ]);

            $message = 'Tugas berhasil dikembalikan ke karyawan untuk revisi.';
        }

        if ($request->filled('return_to')) {
            return redirect($request->input('return_to'))->with('success', $message);
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

    /**
     * Format Rupiah value into human readable short format (M / Jt / Rb).
     */
    private function formatShortRupiah(float $value): string
    {
        if ($value >= 1_000_000_000) {
            return 'Rp '.number_format($value / 1_000_000_000, 2, ',', '.').' M';
        }
        if ($value >= 1_000_000) {
            return 'Rp '.number_format($value / 1_000_000, 1, ',', '.').' Jt';
        }
        if ($value >= 1_000) {
            return 'Rp '.number_format($value / 1_000, 0, ',', '.').' Rb';
        }

        return 'Rp '.number_format($value, 0, ',', '.');
    }

    /**
     * Display customer detail with all SIDs (for Semua Tugas or Exception).
     */
    public function customerDetail(Request $request, string $customer, ?string $category = null): View
    {
        $user = Auth::user();
        $customerName = Task::findCustomerBySlug($customer, $category);

        if ($customerName === null) {
            abort(404, 'Data pelanggan tidak ditemukan.');
        }

        $customerSlug = $customerName === '' ? 'tanpa-nama-pelanggan' : Str::slug($customerName);

        $baseCustomerQuery = Task::query();
        if ($category) {
            $baseCustomerQuery->where('category', $category);
        }
        if ($customerName === '') {
            $baseCustomerQuery->where(function ($q): void {
                $q->whereNull('customer_name')->orWhereRaw("TRIM(customer_name) = ''");
            });
        } else {
            $baseCustomerQuery->where(DB::raw('TRIM(customer_name)'), $customerName);
        }

        // Scope to user's tasks if not admin
        if (! $user->isAdmin()) {
            $baseCustomerQuery->where('user_id', $user->id);
            if ((clone $baseCustomerQuery)->count() === 0) {
                abort(404, 'Data pelanggan tidak ditemukan.');
            }
        }

        // 1. Total Rp Exception for this customer (if exception or has rp_exception)
        $totalCustomerRp = (float) (clone $baseCustomerQuery)->sum('rp_exception');

        // 2. Total Harga Baru for this customer
        $totalCustomerHargaBaru = (float) (clone $baseCustomerQuery)->sum('harga_baru');

        // 3. Total SID / document count across all statuses
        $totalAllSidCount = (clone $baseCustomerQuery)->count();

        // 4. Customer metadata: KP labels & Sales names
        $allCustomerTasks = (clone $baseCustomerQuery)->with('user')->get();

        $kpList = Task::kpList();
        $kpLabels = $allCustomerTasks->pluck('kp')->filter()->unique()->map(fn ($k) => $kpList[$k] ?? $k)->values();
        $salesNames = $allCustomerTasks->map(fn ($t) => $t->sales_name ?: optional($t->user)->name)->filter()->unique()->values();
        $nearestDeadline = $allCustomerTasks->whereNotNull('due_date')->min('due_date');

        // 5. Query for table tasks with filters
        $query = clone $baseCustomerQuery;

        if ($user->isAdmin() && $request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('deadline_filter')) {
            $deadline = $request->query('deadline_filter');
            if ($deadline === 'tomorrow') {
                $query->whereDate('due_date', now()->addDay());
            } elseif ($deadline === 'today') {
                $query->whereDate('due_date', now());
            } elseif ($deadline === 'overdue') {
                $query->whereDate('due_date', '<', now())
                    ->where('status', '!=', Task::STATUS_APPROVED);
            }
        }

        if ($request->filled('kp')) {
            $query->where('kp', $request->query('kp'));
        }

        if ($request->filled('kategori_segmen')) {
            $query->where('kategori_segmen', $request->query('kategori_segmen'));
        }

        if ($request->filled('sales_name')) {
            $query->where('sales_name', $request->query('sales_name'));
        }

        if ($request->filled('month') && is_numeric($request->query('month'))) {
            $query->whereMonth('due_date', (int) $request->query('month'));
        }

        if ($request->filled('year') && is_numeric($request->query('year'))) {
            $query->whereYear('due_date', (int) $request->query('year'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function ($q) use ($search): void {
                $q->where('document_number', 'like', "%{$search}%")
                    ->orWhere('service_type', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('sales_name', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->query('per_page', 25);
        if (! in_array($perPage, [25, 50, 100, 200, 500], true)) {
            $perPage = 25;
        }

        $tasks = $query->with(['user', 'assigner', 'mutation'])
            ->orderBy('due_date', 'asc')
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $categories = Task::categories();
        $kategoriSegmenList = Task::kategoriSegmenList();
        $currentCategoryLabel = $category ? ($categories[$category] ?? ucfirst($category)) : 'Semua Tugas';

        $viewName = $category === 'exception' ? 'tasks.exception_customer' : 'tasks.customer';

        return view($viewName, compact(
            'customerName',
            'customerSlug',
            'totalCustomerRp',
            'totalCustomerHargaBaru',
            'totalAllSidCount',
            'kpLabels',
            'salesNames',
            'nearestDeadline',
            'tasks',
            'kpList',
            'category',
            'categories',
            'kategoriSegmenList',
            'currentCategoryLabel'
        ));
    }

    /**
     * Display the detail page for a specific customer in Exception category.
     */
    public function exceptionCustomerDetail(Request $request, string $customer): View
    {
        return $this->customerDetail($request, $customer, Task::CATEGORY_EXCEPTION);
    }

    /**
     * Display the detail page for a specific customer in a specific category (SO Open, BAA, BAI, Kontrak Exp).
     */
    public function categoryCustomerDetail(Request $request, string $category, string $customer): View
    {
        return $this->customerDetail($request, $customer, $category);
    }
}
