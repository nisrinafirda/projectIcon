<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class ActivityLogger
{
    public const ACTION_TASK_COMPLETED_CHECKED = 'task_completed_checked';

    public const ACTION_TASK_UNCHECKED = 'task_unchecked';

    public const ACTION_TASK_VERIFIED = 'task_verified';

    public const ACTION_TASK_REJECTED = 'task_rejected';

    public const ACTION_COMMENT_UPDATED = 'comment_updated';

    public const ACTION_STATUS_CHANGED = 'status_changed';

    public const ACTION_TASK_CREATED = 'task_created';

    public const ACTION_TASK_DELETED = 'task_deleted';

    public const ACTION_IMPORT_DONE = 'import_done';

    public const ACTION_EXPORT_DONE = 'export_done';

    /**
     * Log an activity safely without interrupting the calling process.
     *
     * @param  array<string, mixed>  $extra
     */
    public static function log(
        string $action,
        string $description,
        ?Task $task = null,
        ?User $user = null,
        ?string $oldValue = null,
        ?string $newValue = null,
        array $extra = []
    ): ?ActivityLog {
        try {
            $user = $user ?? Auth::user();

            return ActivityLog::create([
                'user_id' => $user?->id ?? ($extra['user_id'] ?? null),
                'user_name' => $user?->name ?? ($extra['user_name'] ?? 'Sistem'),
                'user_role' => $user?->role ?? ($extra['user_role'] ?? 'system'),
                'action' => $action,
                'task_id' => $task?->id ?? ($extra['task_id'] ?? null),
                'category' => $task?->category ?? ($extra['category'] ?? null),
                'customer_name' => $task?->customer_name ?? ($extra['customer_name'] ?? null),
                'document_number' => $task?->document_number ?? ($extra['document_number'] ?? null),
                'description' => $description,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('Gagal mencatat riwayat aktivitas: '.$e->getMessage(), [
                'action' => $action,
                'task_id' => $task?->id ?? ($extra['task_id'] ?? null),
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * Map actions to human-readable Indonesian labels.
     *
     * @return array<string, string>
     */
    public static function actionLabels(): array
    {
        return [
            self::ACTION_TASK_COMPLETED_CHECKED => 'Centang Selesai',
            self::ACTION_TASK_UNCHECKED => 'Batal Selesai',
            self::ACTION_TASK_VERIFIED => 'Verifikasi Disetujui',
            self::ACTION_TASK_REJECTED => 'Verifikasi Ditolak',
            self::ACTION_COMMENT_UPDATED => 'Ubah Komentar',
            self::ACTION_STATUS_CHANGED => 'Ubah Status',
            self::ACTION_TASK_CREATED => 'Tugas Baru',
            self::ACTION_TASK_DELETED => 'Hapus Tugas',
            self::ACTION_IMPORT_DONE => 'Import Data',
            self::ACTION_EXPORT_DONE => 'Export Data',
        ];
    }
}
