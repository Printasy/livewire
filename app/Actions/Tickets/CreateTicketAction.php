<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\TicketWorkflowStep;

class CreateTicketAction
{
    public function __construct(
        protected LogTicketActivityAction $logTicketActivityAction,
    ) {
    }

    public function execute(array $data): Ticket
    {
        $ticket = Ticket::create([
            'subject' => $data['subject'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'status' => $data['status'],
            'assigned_user_id' => null,
            'workflow_step' => TicketWorkflowStep::New,
        ]);

        $this->logTicketActivityAction->execute(
            $ticket,
            'Ticket aangemaakt.'
        );

        return $ticket->fresh(['assignee']);
    }
}
