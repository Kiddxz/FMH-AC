<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use LogicException;

/**
 * One activity log entry: who did what, in which module, and when (capstone NFR-REQ023 / NFR-REQ024).
 * Rows are only added, never edited or deleted, so the log can be trusted.
 */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;   // the table has created_at only

    // Actions that may point to a security problem; the viewer shows them in red
    public const WARNING_ACTIONS = ['login_failed', 'login_blocked', 'login_throttled', 'voided', 'deleted', 'deactivated'];

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'description',
        'subject_type',
        'subject_id',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    // The log is append-only: saving changes to an old row or deleting one is refused
    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Activity log entries cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Activity log entries cannot be deleted.'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    // Quick way to write a log entry from anywhere:
    // ActivityLog::record('updated', 'Appointments', 'Confirmed appointment APP-000001', $appointment);
    // $userId is only needed when nobody is logged in yet (e.g. a failed login of a known account).
    public static function record(string $action, string $module, string $description, ?Model $subject = null, ?int $userId = null): self
    {
        return static::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'module' => $module,
            'description' => Str::limit($description, 250),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
