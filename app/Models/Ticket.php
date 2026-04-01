<?php

namespace App\Models;

use App\Actions\Tickets\LogTicketActivityAction;
use App\TicketPriority;
use App\TicketStatus;
use App\TicketWorkflowStep;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $fillable = [
        'subject',
        'description',
        'status',
        'priority',
        'assigned_user_id',
        'workflow_step',
        'attachment_path',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'workflow_step' => TicketWorkflowStep::class,
        ];
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function statusLabel(): string
    {
        return $this->status->label();
    }

    public function statusBadgeClasses(): string
    {
        return $this->status->badgeClasses();
    }

    public function priorityLabel(): string
    {
        return $this->priority->label();
    }

    public function priorityBadgeClasses(): string
    {
        return $this->priority->badgeClasses();
    }

    public function workflowLabel(): string
    {
        return $this->workflow_step->label();
    }

    public function workflowBadgeClasses(): string
    {
        return $this->workflow_step->badgeClasses();
    }

    public function assigneeName(): string
    {
        return $this->assignee?->name ?? 'Niet toegewezen';
    }

    public function isOpen(): bool
    {
        return $this->status === TicketStatus::Open;
    }

    public function isClosed(): bool
    {
        return $this->status === TicketStatus::Closed;
    }

    public function logActivity(
        string $eventOrDescription,
        ?string $label = null,
        ?string $description = null,
    ): void {
        if ($label === null && $description === null) {
            app(LogTicketActivityAction::class)->execute(
                $this,
                $eventOrDescription,
                'ticket_event',
                'Ticket activiteit',
            );

            return;
        }

        app(LogTicketActivityAction::class)->execute(
            $this,
            $description ?? '',
            $eventOrDescription,
            $label ?? 'Ticket activiteit',
        );
    }
}
