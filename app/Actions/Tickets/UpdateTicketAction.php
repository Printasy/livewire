<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;

class UpdateTicketAction
{
    public function __construct(
        protected LogTicketActivityAction $logTicketActivityAction,
    ) {
    }

    public function execute(Ticket $ticket, array $data): Ticket
    {
        $oldSubject = $ticket->subject;
        $oldDescription = $ticket->description;
        $oldStatus = $ticket->status;
        $oldPriority = $ticket->priority;
        $oldWorkflow = $ticket->workflow_step;
        $oldAssigneeName = $ticket->assigneeName();

        $ticket->update([
            'subject' => $data['subject'],
            'description' => $data['description'],
            'status' => $data['status'],
            'priority' => $data['priority'],
            'workflow_step' => $data['workflow_step'],
            'assigned_user_id' => $data['assigned_user_id'] !== '' ? $data['assigned_user_id'] : null,
        ]);

        $ticket->refresh();

        if ($oldSubject !== $ticket->subject) {
            $this->logTicketActivityAction->execute(
                $ticket,
                "Onderwerp gewijzigd van '{$oldSubject}' naar '{$ticket->subject}'."
            );
        }

        if ($oldDescription !== $ticket->description) {
            $this->logTicketActivityAction->execute(
                $ticket,
                'Beschrijving bijgewerkt.'
            );
        }

        if ($oldStatus !== $ticket->status) {
            $this->logTicketActivityAction->execute(
                $ticket,
                "Status gewijzigd van {$oldStatus->value} naar {$ticket->status->value}."
            );
        }

        if ($oldPriority !== $ticket->priority) {
            $this->logTicketActivityAction->execute(
                $ticket,
                "Prioriteit gewijzigd van {$oldPriority->value} naar {$ticket->priority->value}."
            );
        }

        if ($oldWorkflow !== $ticket->workflow_step) {
            $this->logTicketActivityAction->execute(
                $ticket,
                "Workflow gewijzigd van {$oldWorkflow->value} naar {$ticket->workflow_step->value}."
            );
        }

        if ($oldAssigneeName !== $ticket->assigneeName()) {
            $this->logTicketActivityAction->execute(
                $ticket,
                "Toegewezen behandelaar gewijzigd van {$oldAssigneeName} naar {$ticket->assigneeName()}."
            );
        }

        return $ticket->fresh(['assignee']);
    }
}
