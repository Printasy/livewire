<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $fillable = [
        'subject',
        'description',
        'status',
        'priority',
        'attachment_path',
    ];

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'open' => 'Open',
            'in_progress' => 'In behandeling',
            'closed' => 'Gesloten',
            default => 'Onbekend',
        };
    }

    public function statusBadgeClasses(): string
    {
        return match ($this->status) {
            'open' => 'bg-blue-100 text-blue-700',
            'in_progress' => 'bg-yellow-100 text-yellow-700',
            'closed' => 'bg-green-100 text-green-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    public function priorityLabel(): string
    {
        return match ($this->priority) {
            'low' => 'Laag',
            'medium' => 'Normaal',
            'high' => 'Hoog',
            default => 'Onbekend',
        };
    }

    public function priorityBadgeClasses(): string
    {
        return match ($this->priority) {
            'low' => 'bg-gray-100 text-gray-700',
            'medium' => 'bg-orange-100 text-orange-700',
            'high' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
