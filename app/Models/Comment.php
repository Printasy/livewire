<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    protected $fillable = [
        'ticket_id',
        'content',
        'type',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function isNote(): bool
    {
        return $this->type === 'note';
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'comment' => 'Comment',
            'note' => 'Interne notitie',
            default => 'Onbekend',
        };
    }

    public function typeBadgeClasses(): string
    {
        return match ($this->type) {
            'comment' => 'bg-blue-100 text-blue-700',
            'note' => 'bg-yellow-100 text-yellow-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }
}
