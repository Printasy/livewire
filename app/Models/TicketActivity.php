<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketActivity extends Model
{
    protected $fillable = [
        'ticket_id',
        'event',
        'label',
        'description',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function badgeClasses(): string
    {
        return match ($this->event) {
            'ticket_created' => 'bg-blue-100 text-blue-700',
            'ticket_updated' => 'bg-indigo-100 text-indigo-700',
            'comment_created' => 'bg-green-100 text-green-700',
            'comment_deleted' => 'bg-red-100 text-red-700',
            'attachment_created' => 'bg-purple-100 text-purple-700',
            'attachment_deleted' => 'bg-orange-100 text-orange-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }
}
