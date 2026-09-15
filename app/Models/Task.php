<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'assigned_by',
    'category',
    'document_number',
    'title',
    'description',
    'customer_name',
    'service_type',
    'priority',
    'status',
    'start_date',
    'due_date',
    'completed_at',
    'submission_notes',
    'submission_attachment',
    'admin_notes',
    'reviewed_at',
])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    public const CATEGORY_SSO_OPEN = 'sso_open';

    public const CATEGORY_BAA = 'baa';

    public const CATEGORY_BAI = 'bai';

    public const CATEGORY_EXCEPTION = 'exception';

    public const CATEGORY_KONTRAK_EXP = 'kontrak_exp';

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Categories list with Indonesian labels.
     *
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return [
            self::CATEGORY_SSO_OPEN => 'SSO Open',
            self::CATEGORY_BAA => 'BAA',
            self::CATEGORY_BAI => 'BAI',
            self::CATEGORY_EXCEPTION => 'EXCEPTION',
            self::CATEGORY_KONTRAK_EXP => 'Kontrak Exp',
        ];
    }

    /**
     * Statuses list with Indonesian labels.
     *
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Menunggu Dikerjakan',
            self::STATUS_IN_PROGRESS => 'Sedang Dikerjakan',
            self::STATUS_SUBMITTED => 'Menunggu Verifikasi Admin',
            self::STATUS_APPROVED => 'Selesai (Disetujui)',
            self::STATUS_REJECTED => 'Ditolak (Perlu Revisi)',
        ];
    }

    /**
     * Category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::categories()[$this->category] ?? ucfirst($this->category);
    }

    /**
     * Status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::statuses()[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Check if task is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        if ($this->status === self::STATUS_APPROVED) {
            return false;
        }

        if (! $this->due_date) {
            return false;
        }

        return $this->due_date->isPast() && ! $this->due_date->isToday();
    }

    /**
     * Check if task is due today or tomorrow.
     */
    public function getIsDueSoonAttribute(): bool
    {
        if ($this->status === self::STATUS_APPROVED || ! $this->due_date) {
            return false;
        }

        $diff = Carbon::today()->diffInDays($this->due_date, false);

        return $diff >= 0 && $diff <= 1;
    }

    /**
     * Relation to the assigned user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relation to the admin who created or assigned the task.
     */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Scope query by category.
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope query by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope query for specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope tasks that are overdue.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_APPROVED)
            ->whereNotNull('due_date')
            ->where('due_date', '<', Carbon::today());
    }

    /**
     * Scope tasks due soon (within specified days, default 1 day / tomorrow).
     */
    public function scopeDueSoon(Builder $query, int $days = 1): Builder
    {
        $today = Carbon::today();
        $target = Carbon::today()->addDays($days);

        return $query->where('status', '!=', self::STATUS_APPROVED)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$today, $target]);
    }
}
