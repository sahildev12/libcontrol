<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SupportTicketAttachment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'support_ticket_id',
        'path',
        'original_name',
        'mime',
        'size',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
