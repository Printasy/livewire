<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\TicketStatus;

class ChangeTicketStatusAction
{
    public function __construct(
        protected LogTicketActivityAction $logTicketActivityAction,
    ) {
    }

    public function execute(Ticket $ticket, string $status): Ticket
    {
        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => TicketStatus::from($status),
        ]);

        $ticket->refresh();

        if ($oldStatus !== $ticket->status) {
            $this->logTicketActivityAction->execute(
                $ticket,
                "Status snel gewijzigd van {$oldStatus->value} naar {$ticket->status->value}."
            );
        }

        return $ticket->fresh(['assignee']);
    }
}
