<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Admin via updateOrCreate with password from .env/config
        $admin = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'username' => 'admin',
                'nip' => 'ADM001',
                'name' => 'Admin ICON',
                'password' => Hash::make(config('auth.admin_password', env('ADMIN_PASSWORD', 'admin123'))),
                'role' => 'admin',
                'department' => 'Operasional Pusat',
                'phone' => '081234567890',
                'email_verified_at' => now(),
            ]
        );

        // 2. Create Regular User (Karyawan) via updateOrCreate with password from .env/config
        $employee = User::updateOrCreate(
            ['email' => 'karyawan@gmail.com'],
            [
                'username' => 'karyawan.icon',
                'nip' => 'NIP101',
                'name' => 'Karyawan ICON',
                'password' => Hash::make(config('auth.user_demo_password', env('USER_DEMO_PASSWORD', 'user123'))),
                'role' => 'user',
                'department' => 'Service Delivery',
                'phone' => '081298765432',
                'email_verified_at' => now(),
            ]
        );

        // 3. Remove obsolete dummy accounts if they exist
        User::whereIn('email', [
            'admin@icon.co.id',
            'test@example.com',
            'ahmad@icon.co.id',
            'siti@icon.co.id',
            'budi@icon.co.id',
        ])->delete();

        $tomorrow = Carbon::tomorrow();
        $inTwoDays = Carbon::today()->addDays(2);
        $nextWeek = Carbon::today()->addDays(7);
        $yesterday = Carbon::yesterday();

        // 3. Create Sample Tasks
        // BAA: 3 belum selesai, deadline besok (sesuai contoh user)
        Task::firstOrCreate(
            ['category' => Task::CATEGORY_BAA, 'document_number' => 'BAA-2026-001'],
            [
                'user_id' => $employee->id,
                'assigned_by' => $admin->id,
                'title' => 'Penyelesaian Dokumen BAA PT Telco Mandiri',
                'description' => 'Verifikasi kelengkapan tanda tangan berita acara aktivasi link 1Gbps',
                'customer_name' => 'PT Telco Mandiri',
                'kp' => Task::KP_SURABAYA,
                'kategori_segmen' => Task::SEGMEN_PUBLIK,
                'service_type' => 'Metronet 1Gbps',
                'priority' => 'high',
                'status' => Task::STATUS_IN_PROGRESS,
                'start_date' => Carbon::today(),
                'due_date' => $tomorrow,
            ]
        );

        Task::firstOrCreate(
            ['category' => Task::CATEGORY_BAA, 'document_number' => 'BAA-2026-002'],
            [
                'user_id' => $employee->id,
                'assigned_by' => $admin->id,
                'title' => 'BAA Aktivasi Cabang Bank Sejahtera',
                'description' => 'Upload scan dokumen BAA resmi dari regional 3',
                'customer_name' => 'Bank Sejahtera Tbk',
                'kp' => Task::KP_MALANG,
                'kategori_segmen' => Task::SEGMEN_PUBLIK,
                'service_type' => 'IP VPN 100Mbps',
                'priority' => 'urgent',
                'status' => Task::STATUS_IN_PROGRESS,
                'start_date' => Carbon::today()->subDays(2),
                'due_date' => $tomorrow,
            ]
        );

        Task::firstOrCreate(
            ['category' => Task::CATEGORY_BAA, 'document_number' => 'BAA-2026-003'],
            [
                'user_id' => $employee->id,
                'assigned_by' => $admin->id,
                'title' => 'Finalisasi BAA Migrasi Fiber Optik Kawasan Industri',
                'description' => 'Konfirmasi tanda tangan kedua pihak untuk migrasi backbone',
                'customer_name' => 'Kawasan Industri Nusantara',
                'kp' => Task::KP_SURABAYA,
                'kategori_segmen' => Task::SEGMEN_PUBLIK,
                'service_type' => 'Dark Fiber',
                'priority' => 'high',
                'status' => Task::STATUS_IN_PROGRESS,
                'start_date' => Carbon::today()->subDays(1),
                'due_date' => $tomorrow,
            ]
        );

        // BAI: 2 tugas belum selesai, deadline besok (sesuai contoh user)
        Task::firstOrCreate(
            ['category' => Task::CATEGORY_BAI, 'document_number' => 'BAI-2026-011'],
            [
                'user_id' => $employee->id,
                'assigned_by' => $admin->id,
                'title' => 'Berita Acara Instalasi OLT Baru Site Cikarang',
                'description' => 'Lengkapi checklist fisik dan foto instalasi rak server',
                'customer_name' => 'Internal PLN Icon Plus Site Cikarang',
                'kp' => Task::KP_MADIUN,
                'kategori_segmen' => Task::SEGMEN_PLN,
                'service_type' => 'Infrastructure OLT',
                'priority' => 'medium',
                'status' => Task::STATUS_IN_PROGRESS,
                'start_date' => Carbon::today(),
                'due_date' => $tomorrow,
            ]
        );

        Task::firstOrCreate(
            ['category' => Task::CATEGORY_BAI, 'document_number' => 'BAI-2026-012'],
            [
                'user_id' => $employee->id,
                'assigned_by' => $admin->id,
                'title' => 'BAI Perangkat Router Core POP Surabaya',
                'description' => 'Validasi serial number perangkat dan pengetesan redudansi',
                'customer_name' => 'POP Surabaya Gubeng',
                'kp' => Task::KP_SURABAYA,
                'kategori_segmen' => Task::SEGMEN_PLN,
                'service_type' => 'Core Router Upgrade',
                'priority' => 'high',
                'status' => Task::STATUS_IN_PROGRESS,
                'start_date' => Carbon::today()->subDays(1),
                'due_date' => $tomorrow,
            ]
        );

        // SO Open (renamed from SSO Open)
        Task::firstOrCreate(
            ['category' => Task::CATEGORY_SO_OPEN, 'document_number' => 'SO-2026-8801'],
            [
                'user_id' => $employee->id,
                'assigned_by' => $admin->id,
                'title' => 'Penanganan Tiket SO Open Customer Enterprise 01',
                'description' => 'Layanan down parsial jalur backhaul, investigasi link flapping',
                'customer_name' => 'PT Mitra Global Data',
                'kp' => Task::KP_SURABAYA,
                'kategori_segmen' => Task::SEGMEN_PUBLIK,
                'service_type' => 'Dedicated Internet 500Mbps',
                'priority' => 'urgent',
                'status' => Task::STATUS_IN_PROGRESS,
                'start_date' => Carbon::today()->subDays(1),
                'due_date' => $inTwoDays,
            ]
        );

        Task::firstOrCreate(
            ['category' => Task::CATEGORY_SO_OPEN, 'document_number' => 'SO-2026-8802'],
            [
                'user_id' => $employee->id,
                'assigned_by' => $admin->id,
                'title' => 'SO Open Request Bandwidth on Demand',
                'description' => 'Aktivasi penambahan bandwidth sementara event nasional',
                'customer_name' => 'Kementerian Kominfo',
                'kp' => Task::KP_MALANG,
                'kategori_segmen' => Task::SEGMEN_PUBLIK,
                'service_type' => 'Bandwidth on Demand',
                'priority' => 'medium',
                'status' => Task::STATUS_IN_PROGRESS,
                'start_date' => Carbon::today(),
                'due_date' => $nextWeek,
            ]
        );

        Task::firstOrCreate(
            ['category' => Task::CATEGORY_SO_OPEN, 'document_number' => 'SO-2026-8800'],
            [
                'user_id' => $employee->id,
                'assigned_by' => $admin->id,
                'title' => 'SO Open Konfigurasi VLAN Pelanggan Retail',
                'description' => 'Mapping VLAN tagging untuk 15 titik cabang',
                'customer_name' => 'Retail Mart Nusantara',
                'kp' => Task::KP_SURABAYA,
                'kategori_segmen' => Task::SEGMEN_PUBLIK,
                'service_type' => 'IP VPN',
                'priority' => 'low',
                'status' => Task::STATUS_APPROVED,
                'start_date' => Carbon::today()->subDays(5),
                'due_date' => $yesterday,
                'completed_at' => Carbon::yesterday(),
                'submission_notes' => 'Konfigurasi telah aktif dan diuji ping stabil.',
                'admin_notes' => 'Disetujui. Dokumentasi lengkap.',
                'reviewed_at' => Carbon::today(),
            ]
        );

        Task::firstOrCreate(
            ['category' => Task::CATEGORY_SO_OPEN, 'document_number' => 'SO-2026-8803'],
            [
                'user_id' => $employee->id,
                'assigned_by' => $admin->id,
                'title' => 'SO Open Aktivasi Link SCADA Gardu Induk Madiun',
                'description' => 'Pemasangan router dan integrasi telemetri PLN',
                'customer_name' => 'PLN UP3 Madiun',
                'kp' => Task::KP_MADIUN,
                'kategori_segmen' => Task::SEGMEN_PLN,
                'service_type' => 'SCADA Telecommunication',
                'priority' => 'high',
                'status' => Task::STATUS_IN_PROGRESS,
                'start_date' => Carbon::today()->subDays(2),
                'due_date' => $tomorrow,
            ]
        );

        Task::firstOrCreate(
            ['category' => Task::CATEGORY_SO_OPEN, 'document_number' => 'SO-2026-8804'],
            [
                'user_id' => $employee->id,
                'assigned_by' => $admin->id,
                'title' => 'SO Open Migrasi Jaringan Fiber Kantor Jember',
                'description' => 'Penyambungan kabel FO dan uji redaman core',
                'customer_name' => 'PLN UP3 Jember',
                'kp' => Task::KP_JEMBER,
                'kategori_segmen' => Task::SEGMEN_PLN,
                'service_type' => 'Metro Ethernet',
                'priority' => 'medium',
                'status' => Task::STATUS_APPROVED,
                'start_date' => Carbon::today()->subDays(4),
                'due_date' => $yesterday,
                'completed_at' => Carbon::yesterday(),
                'submission_notes' => 'Splicing selesai, loss di bawah 0.2 dB.',
                'admin_notes' => 'Disetujui dan operasional.',
                'reviewed_at' => Carbon::today(),
            ]
        );

        // EXCEPTION
        Task::firstOrCreate(
            ['category' => Task::CATEGORY_EXCEPTION, 'document_number' => 'EXC-2026-041'],
            [
                'user_id' => $employee->id,
                'assigned_by' => $admin->id,
                'title' => 'Exception Approval SLA Downtime Akibat Force Majeure Banjir',
                'description' => 'Penyusunan laporan kronologi gangguan dan koordinasi tim lapangan',
                'customer_name' => 'PLN UID Jawa Timur',
                'kp' => Task::KP_SURABAYA,
                'kategori_segmen' => Task::SEGMEN_PLN,
                'service_type' => 'SCADA Telecommunication',
                'priority' => 'urgent',
                'status' => Task::STATUS_SUBMITTED,
                'start_date' => Carbon::today()->subDays(3),
                'due_date' => $inTwoDays,
                'submission_notes' => 'Surat keterangan banjir dari BPBD dan log pemulihan telah dilampirkan.',
            ]
        );

        // KONTRAK EXP
        Task::firstOrCreate(
            ['category' => Task::CATEGORY_KONTRAK_EXP, 'document_number' => 'KTR-2026-5501'],
            [
                'user_id' => $employee->id,
                'assigned_by' => $admin->id,
                'title' => 'Perpanjangan Kontrak Sewa Fiber Optik Wilayah Timur',
                'description' => 'Kirim draf adendum perpanjangan masa berlaku kontrak 12 bulan',
                'customer_name' => 'PT Trans Pasifik Solusindo',
                'kp' => Task::KP_JEMBER,
                'kategori_segmen' => Task::SEGMEN_PUBLIK,
                'service_type' => 'Leased Line',
                'priority' => 'high',
                'status' => Task::STATUS_IN_PROGRESS,
                'start_date' => Carbon::today()->subDays(7),
                'due_date' => $nextWeek,
            ]
        );

        Task::firstOrCreate(
            ['category' => Task::CATEGORY_KONTRAK_EXP, 'document_number' => 'KTR-2026-5502'],
            [
                'user_id' => $employee->id,
                'assigned_by' => $admin->id,
                'title' => 'Evaluasi Renewal Layanan Cloud ICON+',
                'description' => 'Review utilisasi storage dan kalkulasi proposal harga perpanjangan',
                'customer_name' => 'Dinas Perhubungan Malang',
                'kp' => Task::KP_MALANG,
                'kategori_segmen' => Task::SEGMEN_PUBLIK,
                'service_type' => 'Cloud VPS Enterprise',
                'priority' => 'medium',
                'status' => Task::STATUS_IN_PROGRESS,
                'start_date' => Carbon::today(),
                'due_date' => $nextWeek,
            ]
        );

        // Tambahan Dummy Antrean Verifikasi (Status: SUBMITTED) dari berbagai Kategori & KP
        Task::firstOrCreate(
            ['category' => Task::CATEGORY_BAA, 'document_number' => 'BAA-2026-009'],
            [
                'user_id' => $employee->id,
                'assigned_by' => $admin->id,
                'title' => 'Penyelesaian Dokumen BAA Integrasi Metering AMR',
                'description' => 'Verifikasi dokumen serah terima integrasi AMR pelanggan industri',
                'customer_name' => 'PLN UP3 Malang',
                'kp' => Task::KP_MALANG,
                'kategori_segmen' => Task::SEGMEN_PLN,
                'service_type' => 'AMR Metering',
                'priority' => 'high',
                'status' => Task::STATUS_SUBMITTED,
                'start_date' => Carbon::today()->subDays(4),
                'due_date' => $inTwoDays,
                'submission_notes' => 'Dokumen BAA sudah ditandatangani basah oleh Manajer Bagian Transaksi Energi dan sudah di-scan lengkap.',
            ]
        );

        Task::firstOrCreate(
            ['category' => Task::CATEGORY_BAI, 'document_number' => 'BAI-2026-088'],
            [
                'user_id' => $employee->id,
                'assigned_by' => $admin->id,
                'title' => 'Uji Terima BAI Penarikan Fiber Optik Segmen Jember - Lumajang',
                'description' => 'Pemeriksaan hasil OTDR dan kelayakan sambungan core fiber optik backbone',
                'customer_name' => 'Dinas Kominfo Jawa Timur',
                'kp' => Task::KP_JEMBER,
                'kategori_segmen' => Task::SEGMEN_PUBLIK,
                'service_type' => 'Dark Fiber / Backbone',
                'priority' => 'urgent',
                'status' => Task::STATUS_SUBMITTED,
                'start_date' => Carbon::today()->subDays(6),
                'due_date' => $tomorrow,
                'submission_notes' => 'Laporan acceptance test beserta hasil OTDR trace terlampir, semua core memenuhi standar loss < 0.22 dB/km.',
            ]
        );

        Task::firstOrCreate(
            ['category' => Task::CATEGORY_SO_OPEN, 'document_number' => 'SO-2026-902'],
            [
                'user_id' => $employee->id,
                'assigned_by' => $admin->id,
                'title' => 'Aktivasi Penambahan Kapasitas Bandwidth IP Transit',
                'description' => 'Konfigurasi router edge dan update alokasi bandwidth 500 Mbps',
                'customer_name' => 'Universitas Merdeka Madiun',
                'kp' => Task::KP_MADIUN,
                'kategori_segmen' => Task::SEGMEN_PUBLIK,
                'service_type' => 'IP Transit',
                'priority' => 'medium',
                'status' => Task::STATUS_SUBMITTED,
                'start_date' => Carbon::today()->subDays(2),
                'due_date' => Carbon::today()->addDays(3),
                'submission_notes' => 'MRTG dan live traffic test 24 jam menunjukkan koneksi stabil tanpa packet loss. Mohon approval aktivasi.',
            ]
        );
    }
}
