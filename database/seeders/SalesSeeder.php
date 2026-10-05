<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SalesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rawNames = [
            'GANJAR SANTOSO GHIFFARI',
            'SENNA PUTRA',
            'YESY TIARA ANGELINA',
            'DEIDRA DESAN SYUKRI',
            'FEBRINA LOURENTYA NOVIT',
            'GUMILANG ADI PRAKOSO',
            'YENI PRIMAHAPSARI',
            'YUSTIKA AINI ARROCHMAH',
            'ZAHROTUL JANNAH',
            'ALYA FADHIYAN',
            'DIVIA CAHAYA SALSA DIVA',
            'FIRMANDA KHOLIQ (AKTIF)',
            'JANITRA EGA HIKMAWAN',
            'JOHN MARI ERVIAN SAPUTRA',
            'MOHAMAD HABIB',
            'YANUR M. YUSUF',
        ];

        $usedUsernames = [];

        foreach ($rawNames as $rawName) {
            // 1. Clean name: remove content in parentheses like "(AKTIF)", strip extra spaces, convert to Title Case
            $cleanName = preg_replace('/\s*\([^)]*\)/', '', $rawName);
            $cleanName = preg_replace('/\s+/', ' ', trim($cleanName));
            $titleName = Str::title(strtolower($cleanName));

            // 2. Generate username: first word + last word (lowercase, separated by dot)
            $words = explode(' ', $cleanName);
            $firstWord = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $words[0] ?? ''));
            $lastWord = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', end($words) ?: ''));

            if ($firstWord && $lastWord && count($words) > 1) {
                $baseUsername = "{$firstWord}.{$lastWord}";
            } else {
                $baseUsername = $firstWord ?: 'sales';
            }

            // Handle duplicate usernames by appending 2, 3, etc.
            $username = $baseUsername;
            $counter = 2;
            while (in_array($username, $usedUsernames, true)) {
                $username = "{$baseUsername}{$counter}";
                $counter++;
            }
            $usedUsernames[] = $username;

            // 3. Generate password: first word (lowercase) + "123"
            $passwordPlain = "{$firstWord}123";

            // 4. Find existing user by name or username (e.g. Mohamad Habib)
            $existingUser = User::whereRaw('LOWER(name) = ?', [strtolower($titleName)])
                ->orWhere('username', $username)
                ->orWhere('username', strtolower($baseUsername))
                ->orWhere('username', 'Mohamad.habib')
                ->first();

            if ($existingUser) {
                // Update existing user, retain existing NIP if any
                $existingUser->update([
                    'name' => $titleName,
                    'username' => $username,
                    'password' => Hash::make($passwordPlain),
                    'role' => 'user',
                    'department' => 'Sales',
                    'email' => null,
                    // Keep existing NIP (e.g. NIP001 for Habib)
                    'nip' => $existingUser->nip,
                ]);
            } else {
                // Create new sales user with null email and null nip (nip is nullable in schema)
                User::create([
                    'name' => $titleName,
                    'username' => $username,
                    'password' => Hash::make($passwordPlain),
                    'role' => 'user',
                    'department' => 'Sales',
                    'email' => null,
                    'nip' => null,
                ]);
            }
        }
    }
}
