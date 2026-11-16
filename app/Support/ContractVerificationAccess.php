<?php

namespace App\Support;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ContractVerificationAccess
{
    /** @var array<int, list<string>> */
    private static array $kpCache = [];

    private static ?bool $hasKpColumn = null;

    /**
     * KP yang boleh dilihat user. null = semua KP (admin).
     * Sumber utama: kolom users.kp (JSON array, mis. ["surabaya","malang"], atau teks "surabaya, malang").
     * Kalau kosong, sementara diturunkan dari KP pada tugas milik user itu.
     *
     * @return list<string>|null
     */
    public static function kpsFor(User $user): ?array
    {
        if ($user->isAdmin()) {
            return null;
        }

        if (isset(self::$kpCache[$user->id])) {
            return self::$kpCache[$user->id];
        }

        $kps = [];
        self::$hasKpColumn ??= Schema::hasColumn('users', 'kp');
        if (self::$hasKpColumn) {
            $kps = self::normalizeKpValue($user->kp ?? null);
        }

        if (empty($kps)) {
            $kps = Task::query()
                ->where('user_id', $user->id)
                ->whereNotNull('kp')
                ->where('kp', '!=', '')
                ->distinct()
                ->pluck('kp')
                ->map(fn ($k) => strtolower(trim((string) $k)))
                ->unique()
                ->values()
                ->all();
        }

        return self::$kpCache[$user->id] = $kps;
    }

    /**
     * Ubah nilai kolom kp (array / JSON / teks dipisah koma) menjadi daftar KP huruf kecil.
     *
     * @return list<string>
     */
    public static function normalizeKpValue(mixed $value): array
    {
        if ($value instanceof Collection) {
            $value = $value->all();
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            $decoded = json_decode($trimmed, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $value = $decoded;
            } elseif (json_last_error() === JSON_ERROR_NONE && is_string($decoded)) {
                $value = [$decoded];
            } else {
                $value = preg_split('/[,;\/|]+/', $trimmed) ?: [];
            }
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn ($k) => strtolower(trim((string) $k)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Batasi query Verifikasi Kontrak sesuai KP user.
     * Kalau KP user tidak bisa ditentukan, jatuh ke tugas milik user itu sendiri.
     */
    public static function scope(Builder $query, User $user): Builder
    {
        $kps = self::kpsFor($user);

        if ($kps === null) {
            return $query;
        }

        if (empty($kps)) {
            return $query->where('user_id', $user->id);
        }

        return $query->whereIn(DB::raw('LOWER(kp)'), $kps);
    }

    public static function canView(Task $task, User $user): bool
    {
        $kps = self::kpsFor($user);

        if ($kps === null) {
            return true;
        }

        if ($task->user_id === $user->id) {
            return true;
        }

        return in_array(strtolower(trim((string) $task->kp)), $kps, true);
    }
}