<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Monitoring Tugas ICON</title>
    <style>
        @page {
            margin: 15mm;
            size: a4 landscape;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .brand-title {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
            margin: 0;
        }
        .brand-sub {
            font-size: 10px;
            color: #64748b;
            margin: 2px 0 0 0;
        }
        .meta-text {
            text-align: right;
            font-size: 9px;
            color: #64748b;
        }
        .summary-box {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 15px;
            font-size: 9px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        table.data-table th {
            background-color: #1e40af;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            font-size: 9px;
            border: 1px solid #1e3a8a;
        }
        table.data-table td {
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            font-size: 9px;
            vertical-align: top;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
        }
        .badge-sso { background-color: #cffafe; color: #155e75; }
        .badge-baa { background-color: #fef3c7; color: #92400e; }
        .badge-bai { background-color: #d1fae5; color: #065f46; }
        .badge-exc { background-color: #ffe4e6; color: #9f1239; }
        .badge-ktr { background-color: #ede9fe; color: #5b21b6; }

        .status-pending { color: #475569; }
        .status-progress { color: #1d4ed8; font-weight: bold; }
        .status-submitted { color: #7e22ce; font-weight: bold; }
        .status-approved { color: #047857; font-weight: bold; }
        .status-rejected { color: #b91c1c; font-weight: bold; }

        .footer {
            margin-top: 20px;
            text-align: right;
            font-size: 8px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td>
                <div class="brand-title">PORTAL MONITORING TUGAS — ICON</div>
                <div class="brand-sub">Rekapitulasi Operasional (SSO Open, BAA, BAI, Exception, Kontrak Exp)</div>
            </td>
            <td class="meta-text">
                <div>Dicetak Oleh: <strong>{{ $user->name }}</strong></div>
                <div>Waktu Cetak: {{ $generatedAt }}</div>
                @if($category)<div>Filter Kategori: <strong>{{ strtoupper($category) }}</strong></div>@endif
            </td>
        </tr>
    </table>

    <div class="summary-box">
        <strong>Ringkasan Eksekutif:</strong> Menampilkan total <strong>{{ $tasks->count() }}</strong> data tugas.
        Selesai: <strong>{{ $tasks->where('status', 'approved')->count() }}</strong> |
        Menunggu Review Admin: <strong>{{ $tasks->where('status', 'submitted')->count() }}</strong> |
        Sedang Berjalan / Pending: <strong>{{ $tasks->whereIn('status', ['pending', 'in_progress'])->count() }}</strong>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px; text-align: center;">No</th>
                <th style="width: 65px;">Kategori</th>
                <th style="width: 90px;">ID PA</th>
                <th>Layanan</th>
                <th style="width: 100px;">Karyawan PIC</th>
                <th style="width: 55px;">Prioritas</th>
                <th style="width: 90px;">Status</th>
                <th style="width: 70px;">Deadline</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tasks as $index => $task)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>
                    <span class="badge
                        {{ $task->category === 'sso_open' ? 'badge-sso' : '' }}
                        {{ $task->category === 'baa' ? 'badge-baa' : '' }}
                        {{ $task->category === 'bai' ? 'badge-bai' : '' }}
                        {{ $task->category === 'exception' ? 'badge-exc' : '' }}
                        {{ $task->category === 'kontrak_exp' ? 'badge-ktr' : '' }}">
                        {{ $task->category_label }}
                    </span>
                </td>
                <td style="font-family: monospace; font-weight: bold;">{{ $task->document_number }}</td>
                <td>
                    <strong>{{ $task->service_type ?: $task->title }}</strong>
                    @if($task->customer_name)
                        <div style="font-size: 8px; color: #64748b; margin-top: 2px;">
                            🏢 {{ $task->customer_name }}
                        </div>
                    @endif
                </td>
                <td>{{ $task->user ? $task->user->name : '-' }}</td>
                <td style="text-transform: uppercase;">{{ $task->priority }}</td>
                <td>
                    <span class="
                        {{ $task->status === 'pending' ? 'status-pending' : '' }}
                        {{ $task->status === 'in_progress' ? 'status-progress' : '' }}
                        {{ $task->status === 'submitted' ? 'status-submitted' : '' }}
                        {{ $task->status === 'approved' ? 'status-approved' : '' }}
                        {{ $task->status === 'rejected' ? 'status-rejected' : '' }}">
                        {{ $task->status_label }}
                    </span>
                </td>
                <td>{{ $task->due_date ? $task->due_date->format('d/m/Y') : '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; color: #94a3b8; padding: 20px;">
                    Tidak ada data tugas yang tersedia.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dokumen ini dihasilkan secara otomatis oleh Sistem Portal ICON Monitoring Tugas Karyawan.
    </div>
</body>
</html>

