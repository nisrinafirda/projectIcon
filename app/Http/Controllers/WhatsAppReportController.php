<?php

namespace App\Http\Controllers;

use App\Models\ReportSetting;
use App\Services\WhatsAppReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WhatsAppReportController extends Controller
{
    /**
     * Display or fetch the WhatsApp monitoring update report (admin only).
     */
    public function show(Request $request, WhatsAppReportService $reportService): View|JsonResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Akses khusus administrator ICON.');
        }

        $reportText = $reportService->generateReport();
        $settings = [
            'opportunity_publik' => (float) ReportSetting::getValue('opportunity_publik', 0),
            'revenue_as_is_publik' => (float) ReportSetting::getValue('revenue_as_is_publik', 0),
            'opportunity_pln' => (float) ReportSetting::getValue('opportunity_pln', 0),
            'revenue_as_is_pln' => (float) ReportSetting::getValue('revenue_as_is_pln', 0),
        ];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'report' => $reportText,
                'settings' => $settings,
            ]);
        }

        return view('reports.whatsapp', [
            'reportText' => $reportText,
            'settings' => $settings,
        ]);
    }

    /**
     * Update the opportunity and revenue as-is figures for Publik & PLN (admin only).
     */
    public function updateSettings(Request $request, WhatsAppReportService $reportService): JsonResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Akses khusus administrator ICON.');
        }

        $validated = $request->validate([
            'opportunity_publik' => ['nullable', 'numeric', 'min:0'],
            'revenue_as_is_publik' => ['nullable', 'numeric', 'min:0'],
            'opportunity_pln' => ['nullable', 'numeric', 'min:0'],
            'revenue_as_is_pln' => ['nullable', 'numeric', 'min:0'],
        ]);

        ReportSetting::setValue('opportunity_publik', $validated['opportunity_publik'] ?? 0);
        ReportSetting::setValue('revenue_as_is_publik', $validated['revenue_as_is_publik'] ?? 0);
        ReportSetting::setValue('opportunity_pln', $validated['opportunity_pln'] ?? 0);
        ReportSetting::setValue('revenue_as_is_pln', $validated['revenue_as_is_pln'] ?? 0);

        $reportText = $reportService->generateReport();
        $settings = [
            'opportunity_publik' => (float) ReportSetting::getValue('opportunity_publik', 0),
            'revenue_as_is_publik' => (float) ReportSetting::getValue('revenue_as_is_publik', 0),
            'opportunity_pln' => (float) ReportSetting::getValue('opportunity_pln', 0),
            'revenue_as_is_pln' => (float) ReportSetting::getValue('revenue_as_is_pln', 0),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Angka laporan berhasil disimpan dan laporan diperbarui.',
            'report' => $reportText,
            'settings' => $settings,
        ]);
    }
}
