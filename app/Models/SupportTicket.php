<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'deployment_license_key_hash',
        'deployment_domain',
        'library_code',
        'library_name',
        'subject',
        'message',
        'category',
        'status',
        'priority',
        'reporter_user_id',
        'reporter_name',
        'reporter_email',
        'remote_id',
        'synced_at',
        'read_at',
        'admin_notes',
        'client_update_pending',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
            'read_at' => 'datetime',
            'client_update_pending' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SupportTicket $ticket) {
            if (blank($ticket->uuid)) {
                $ticket->uuid = (string) Str::uuid();
            }
        });
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupportTicketAttachment::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CLOSED => 'Closed',
            default => 'Open',
        };
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            'billing' => 'Billing',
            'technical' => 'Technical',
            'feature' => 'Feature request',
            default => 'General',
        };
    }

    public function priorityLabel(): string
    {
        return match ($this->priority) {
            'low' => 'Low',
            'high' => 'High',
            'urgent' => 'Urgent',
            default => 'Normal',
        };
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markAsRead(): void
    {
        if ($this->read_at !== null) {
            return;
        }

        $this->forceFill(['read_at' => now()])->save();
    }
}
