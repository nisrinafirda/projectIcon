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
        // User::factory(10)->create();
        // 1. Create Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@icon.co.id'],
            [
                'nip' => 'ADM001',
                'name' => 'Admin ICON',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'department' => 'Operasional Pusat',
                'phone' => '081234567890',
                'email_verified_at' => now(),
            ]
        );

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        // 2. Create Regular Users (Karyawan)
        $user1 = User::firstOrCreate(
            ['email' => 'ahmad@icon.co.id'],
            [
                'nip' => 'NIP101',
                'name' => 'Ahmad Pratama',
                'password' => Hash::make('password'),
                'role' => 'user',
                'department' => 'Service Delivery',
                'phone' => '081298765432',
                'email_verified_at' => now(),
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'siti@icon.co.id'],
            [
                'nip' => 'NIP102',
                'name' => 'Siti Rahma',
                'password' => Hash::make('password'),
                'role' => 'user',
                'department' => 'Provisioning & BAA/BAI',
                'phone' => '081345678901',
                'email_verified_at' => now(),
            ]
        );

        $user3 = User::firstOrCreate(
            ['email' => 'budi@icon.co.id'],
            [
                'nip' => 'NIP103',
                'name' => 'Budi Santoso',
                'password' => Hash::make('password'),
                'role' => 'user',
                'department' => 'Network Operation Center',
                'phone' => '081456789012',
                'email_verified_at' => now(),
            ]
        );

        $tomorrow = Carbon::tomorrow();
        $inTwoDays = Carbon::today()->addDays(2);
        $nextWeek = Carbon::today()->addDays(7);
        $yesterday = Carbon::yesterday();

        // 3. Create Sample Tasks
        // BAA: 3 belum selesai, deadline besok (sesuai contoh user)
        Task::firstOrCreate(
            ['category' => Task::CATEGORY_BAA, 'document_number' => 'BAA-2026-001'],
            [
                'user_id' => $user1->id,
                'assigned_by' => $admin->id,
                'title' => 'Penyelesaian Dokumen BAA PT Telco Mandiri',
                'description' => 'Verifikasi kelengkapan tanda tangan berita acara aktivasi link 1Gbps',
                'customer_name' => 'PT Telco Mandiri',
                'service_type' => 'Metronet 1Gbps',
                'priority' => 'high',
                'status' => Task::STATUS_PENDING,
                'start_date' => Carbon::today(),
                'due_date' => $tomorrow,
            ]
        );

        Task::firstOrCreate(
            ['category' => Task::CATEGORY_BAA, 'document_number' => 'BAA-2026-002'],
            [
                'user_id' => $user1->id,
                'assigned_by' => $admin->id,
                'title' => 'BAA Aktivasi Cabang Bank Sejahtera',
                'description' => 'Upload scan dokumen BAA resmi dari regional 3',
                'customer_name' => 'Bank Sejahtera Tbk',
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
                'user_id' => $user2->id,
                'assigned_by' => $admin->id,
                'title' => 'Finalisasi BAA Migrasi Fiber Optik Kawasan Industri',
                'description' => 'Konfirmasi tanda tangan kedua pihak untuk migrasi backbone',
                'customer_name' => 'Kawasan Industri Nusantara',
                'service_type' => 'Dark Fiber',
                'priority' => 'high',
                'status' => Task::STATUS_PENDING,
                'start_date' => Carbon::today()->subDays(1),
                'due_date' => $tomorrow,
            ]
        );

        // BAI: 2 tugas belum selesai, deadline besok (sesuai contoh user)
        Task::firstOrCreate(
            ['category' => Task::CATEGORY_BAI, 'document_number' => 'BAI-2026-011'],
            [
                'user_id' => $user1->id,
                'assigned_by' => $admin->id,
                'title' => 'Berita Acara Instalasi OLT Baru Site Cikarang',
                'description' => 'Lengkapi checklist fisik dan foto instalasi rak server',
                'customer_name' => 'Internal PLN Icon Plus Site Cikarang',
                'service_type' => 'Infrastructure OLT',
                'priority' => 'medium',
                'status' => Task::STATUS_PENDING,
                'start_date' => Carbon::today(),
                'due_date' => $tomorrow,
            ]
        );

        Task::firstOrCreate(
            ['category' => Task::CATEGORY_BAI, 'document_number' => 'BAI-2026-012'],
            [
                'user_id' => $user3->id,
                'assigned_by' => $admin->id,
                'title' => 'BAI Perangkat Router Core POP Surabaya',
                'description' => 'Validasi serial number perangkat dan pengetesan redudansi',
                'customer_name' => 'POP Surabaya Gubeng',
                'service_type' => 'Core Router Upgrade',
                'priority' => 'high',
                'status' => Task::STATUS_IN_PROGRESS,
                'start_date' => Carbon::today()->subDays(1),
                'due_date' => $tomorrow,
            ]
        );

        // SSO Open
        Task::firstOrCreate(
            ['category' => Task::CATEGORY_SSO_OPEN, 'document_number' => 'SSO-2026-8801'],
            [
                'user_id' => $user1->id,
                'assigned_by' => $admin->id,
                'title' => 'Penanganan Tiket SSO Open Customer Enterprise 01',
                'description' => 'Layanan down parsial jalur backhaul, investigasi link flapping',
                'customer_name' => 'PT Mitra Global Data',
                'service_type' => 'Dedicated Internet 500Mbps',
                'priority' => 'urgent',
                'status' => Task::STATUS_IN_PROGRESS,
                'start_date' => Carbon::today()->subDays(1),
                'due_date' => $inTwoDays,
            ]
        );

        Task::firstOrCreate(
            ['category' => Task::CATEGORY_SSO_OPEN, 'document_number' => 'SSO-2026-8802'],
            [
                'user_id' => $user2->id,
                'assigned_by' => $admin->id,
                'title' => 'SSO Open Request Bandwidth on Demand',
                'description' => 'Aktivasi penambahan bandwidth sementara event nasional',
                'customer_name' => 'Kementerian Kominfo',
                'service_type' => 'Bandwidth on Demand',
                'priority' => 'medium',
                'status' => Task::STATUS_PENDING,
                'start_date' => Carbon::today(),
                'due_date' => $nextWeek,
            ]
        );

        Task::firstOrCreate(
            ['category' => Task::CATEGORY_SSO_OPEN, 'document_number' => 'SSO-2026-8800'],
            [
                'user_id' => $user1->id,
                'assigned_by' => $admin->id,
                'title' => 'SSO Open Konfigurasi VLAN Pelanggan Retail',
                'description' => 'Mapping VLAN tagging untuk 15 titik cabang',
                'customer_name' => 'Retail Mart Nusantara',
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

        // EXCEPTION
        Task::firstOrCreate(
            ['category' => Task::CATEGORY_EXCEPTION, 'document_number' => 'EXC-2026-041'],
            [
                'user_id' => $user1->id,
                'assigned_by' => $admin->id,
                'title' => 'Exception Approval SLA Downtime Akibat Force Majeure Banjir',
                'description' => 'Penyusunan laporan kronologi gangguan dan koordinasi tim lapangan',
                'customer_name' => 'PLN UID Jawa Barat',
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
                'user_id' => $user1->id,
                'assigned_by' => $admin->id,
                'title' => 'Perpanjangan Kontrak Sewa Fiber Optik Wilayah Timur',
                'description' => 'Kirim draf adendum perpanjangan masa berlaku kontrak 12 bulan',
                'customer_name' => 'PT Trans Pasifik Solusindo',
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
                'user_id' => $user2->id,
                'assigned_by' => $admin->id,
                'title' => 'Evaluasi Renewal Layanan Cloud ICON+',
                'description' => 'Review utilisasi storage dan kalkulasi proposal harga perpanjangan',
                'customer_name' => 'Dinas Perhubungan',
                'service_type' => 'Cloud VPS Enterprise',
                'priority' => 'medium',
                'status' => Task::STATUS_PENDING,
                'start_date' => Carbon::today(),
                'due_date' => $nextWeek,
            ]
        );
    }
}
