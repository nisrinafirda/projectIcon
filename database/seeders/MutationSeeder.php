<?php

namespace Database\Seeders;

use App\Models\Mutation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MutationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            'A' => 'Aktivasi',
            'B' => 'Pull Out',
            'C' => 'Ubah Telp/Email',
            'D' => 'Downgrade',
            'E' => 'Isolir',
            'F' => 'Unisolir',
            'G' => 'POSTPAID to PREPAID',
            'H' => 'Perubahan Harga',
            'J' => 'Upgrade On Demand',
            'K' => 'Bandwidth On Demand',
            'L' => 'Change Config',
            'M' => 'Migrasi',
            'R' => 'Relokasi',
            'T' => 'Gangguan',
            'U' => 'Upgrade',
            'Z' => 'Pembaruan kontrak / Retender',
        ];

        // Reset mutation for exception tasks or non-alphabet ID PA
        DB::table('tasks')
            ->where('category', 'exception')
            ->orWhereIn('mutation_id', Mutation::whereIn('kode', ['1', '2'])->pluck('id'))
            ->update(['mutation_id' => null]);

        Mutation::whereIn('kode', ['1', '2'])->delete();

        foreach ($data as $kode => $nama) {
            Mutation::updateOrCreate(['kode' => $kode], ['nama' => $nama]);
        }

        // Backfill tasks with mutation_id based on first letter of document_number (excluding exception)
        DB::table('tasks')
            ->join('mutations', DB::raw('UPPER(SUBSTRING(TRIM(tasks.document_number), 1, 1))'), '=', 'mutations.kode')
            ->where('tasks.category', '!=', 'exception')
            ->whereNotNull('tasks.document_number')
            ->where('tasks.document_number', '!=', '')
            ->update(['tasks.mutation_id' => DB::raw('mutations.id')]);
    }
}
