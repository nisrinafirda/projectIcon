<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Task;

$results = Task::where('category', 'contract_verification')
    ->selectRaw('kp, status, count(*) as cnt')
    ->groupBy('kp', 'status')
    ->orderBy('kp')
    ->orderBy('status')
    ->get();

echo "=== KP / Status Distribution ===\n";
foreach ($results as $r) {
    echo sprintf("%-12s | %-15s | %d\n", $r->kp, $r->status, $r->cnt);
}

echo "\n=== Jember On Process Sample (non-approved) ===\n";
$jemberSample = Task::where('category', 'contract_verification')
    ->where('kp', 'jember')
    ->where('status', '!=', 'approved')
    ->select('customer_name', 'document_number', 'sales_name', 'verification_note', 'status', 'kp')
    ->limit(15)
    ->get();

foreach ($jemberSample as $t) {
    echo sprintf("Name: %s | Doc: %s | Sales: %s | Note: %s | Status: %s\n",
        $t->customer_name ?? '-',
        $t->document_number ?? '-',
        $t->sales_name ?? '-',
        $t->verification_note ?? '-',
        $t->status
    );
}

echo "\n=== Total per KP ===\n";
$totals = Task::where('category', 'contract_verification')
    ->selectRaw('kp, count(*) as total, sum(case when status = "approved" then 1 else 0 end) as done, sum(case when status != "approved" then 1 else 0 end) as on_process')
    ->groupBy('kp')
    ->orderBy('kp')
    ->get();
foreach ($totals as $t) {
    echo sprintf("%-12s | Total: %d | On Process: %d | Selesai: %d\n", $t->kp, $t->total, $t->on_process, $t->done);
}
