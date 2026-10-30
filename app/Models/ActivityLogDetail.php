<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLogDetail extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'activity_log_id',
        'task_id',
        'customer_name',
        'sid',
        'change_type',
        'field',
        'field_label',
        'old_value',
        'new_value',
    ];

    public function activityLog(): BelongsTo
    {
        return $this->belongsTo(ActivityLog::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
