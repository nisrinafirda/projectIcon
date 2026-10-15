<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'sales_name',
    'assigned_by',
    'category',
    'document_number',
    'title',
    'description',
    'customer_name',
    'kp',
    'kategori_segmen',
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
    'comments',
    'late_reason',
    'was_late',
    'done_marked_at',
    'done_marked_by',
    'confirmed_at',
    'confirmed_by',
    'rp_exception',
    'harga_baru',
    'mutation_id',
])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Task $task) {
            if ($task->category !== self::CATEGORY_EXCEPTION && $task->document_number) {
                $huruf = strtoupper(substr(trim($task->document_number), 0, 1));
                if (ctype_alpha($huruf)) {
                    $task->mutation_id = Mutation::where('kode', $huruf)->value('id');

                    return;
                }
            }

            $task->mutation_id = null;
        });
    }

    public function mutation(): BelongsTo
    {
        return $this->belongsTo(Mutation::class);
    }

    public const CATEGORY_SSO_OPEN = 'sso_open';

    public const CATEGORY_SO_OPEN = 'sso_open';

    public const CATEGORY_BAA = 'baa';

    public const CATEGORY_BAI = 'bai';

    public const CATEGORY_EXCEPTION = 'exception';

    public const CATEGORY_KONTRAK_EXP = 'kontrak_exp';

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const KP_SURABAYA = 'surabaya';

    public const KP_MALANG = 'malang';

    public const KP_MADIUN = 'madiun';

    public const KP_JEMBER = 'jember';

    public const SEGMEN_PLN = 'pln';

    public const SEGMEN_PUBLIK = 'publik';

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
            'done_marked_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'was_late' => 'boolean',
            'rp_exception' => 'decimal:2',
            'harga_baru' => 'decimal:2',
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
            self::CATEGORY_SSO_OPEN => 'SO Open',
            self::CATEGORY_BAA => 'BAA',
            self::CATEGORY_BAI => 'BAI',
            self::CATEGORY_EXCEPTION => 'EXCEPTION',
            self::CATEGORY_KONTRAK_EXP => 'Kontrak Exp',
        ];
    }

    /**
     * List of Kantor Perwakilan (KP).
     *
     * @return array<string, string>
     */
    public static function kpList(): array
    {
        return [
            self::KP_SURABAYA => 'Surabaya',
            self::KP_MALANG => 'Malang',
            self::KP_MADIUN => 'Madiun',
            self::KP_JEMBER => 'Jember',
        ];
    }

    /**
     * List of Kategori Segmen Pelanggan (PLN vs Publik).
     *
     * @return array<string, string>
     */
    public static function kategoriSegmenList(): array
    {
        return [
            self::SEGMEN_PLN => 'PLN',
            self::SEGMEN_PUBLIK => 'Publik',
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
     * Nomor Kontrak (extracted from description if available).
     */
    public function getNomorKontrakAttribute(): ?string
    {
        if ($this->description && preg_match('/No\.?\s*Kontrak\s*:\s*([^\r\n]+)/i', $this->description, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * KP label.
     */
    public function getKpLabelAttribute(): string
    {
        return self::kpList()[$this->kp] ?? ($this->kp ? ucfirst($this->kp) : '-');
    }

    /**
     * Kategori Segmen label.
     */
    public function getKategoriSegmenLabelAttribute(): string
    {
        return self::kategoriSegmenList()[$this->kategori_segmen] ?? ($this->kategori_segmen ? strtoupper($this->kategori_segmen) : '-');
    }

    /**
     * Status label.
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->status === self::STATUS_PENDING) {
            return self::statuses()[self::STATUS_IN_PROGRESS];
        }

        return self::statuses()[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get SO subtype (PDL or PPB) when task is in SO Open category.
     */
    public function getSoTypeAttribute(): ?string
    {
        if ($this->category !== self::CATEGORY_SSO_OPEN) {
            return null;
        }

        $doc = strtoupper(trim((string) $this->document_number));
        $title = (string) $this->title;
        $desc = strtolower((string) $this->description);

        if (str_contains($title, '[PPB]') || str_starts_with($doc, 'B') || str_contains($desc, 'tipe so: ppb') || str_contains($desc, 'confirm to: ppb') || str_contains($desc, 'pull out')) {
            return 'PPB';
        }

        if (str_contains($title, '[PDL]') || str_starts_with($doc, 'A') || str_contains($desc, 'tipe so: pdl') || str_contains($desc, 'confirm to: pdl')) {
            return 'PDL';
        }

        return null;
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
     * Relation to the user who marked the task completed.
     */
    public function doneMarkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_marked_by');
    }

    /**
     * Relation to the admin who confirmed the task.
     */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Scope query by category.
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope query by KP.
     */
    public function scopeByKp(Builder $query, string $kp): Builder
    {
        return $query->where('kp', $kp);
    }

    /**
     * Scope query by kategori segmen.
     */
    public function scopeByKategoriSegmen(Builder $query, string $segmen): Builder
    {
        return $query->where('kategori_segmen', $segmen);
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

    /**
     * Find original customer name by URL slug and optional category.
     */
    public static function findCustomerBySlug(string $slug, ?string $category = null): ?string
    {
        $query = self::query();
        if ($category) {
            $query->where('category', $category);
        }

        $customers = $query
            ->whereNotNull('customer_name')
            ->where('customer_name', '!=', '')
            ->selectRaw('TRIM(customer_name) as name')
            ->distinct()
            ->pluck('name');

        foreach ($customers as $name) {
            if (Str::slug($name) === $slug) {
                return $name;
            }
        }

        $decoded = urldecode($slug);
        foreach ($customers as $name) {
            if (strcasecmp($name, $decoded) === 0 || strcasecmp($name, $slug) === 0) {
                return $name;
            }
        }

        if ($slug === 'tanpa-nama-pelanggan') {
            $hasNullOrEmpty = (clone $query)->where(function ($q): void {
                $q->whereNull('customer_name')->orWhereRaw("TRIM(customer_name) = ''");
            })->exists();
            if ($hasNullOrEmpty) {
                return '';
            }
        }

        return null;
    }

    /**
     * Find original customer name for Exception category by URL slug.
     */
    public static function findExceptionCustomerBySlug(string $slug): ?string
    {
        return self::findCustomerBySlug($slug, self::CATEGORY_EXCEPTION);
    }

    /**
     * Get 12 Indonesian calendar months as [1 => 'Januari', ..., 12 => 'Desember'].
     *
     * @return array<int, string>
     */
    public static function listMonths(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }
}
