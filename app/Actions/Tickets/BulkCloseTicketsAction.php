<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\TicketStatus;

class BulkCloseTicketsAction
{
    public function execute(array $ticketIds): int
    {
        if (empty($ticketIds)) {
            return 0;
        }

        return Ticket::query()
            ->whereIn('id', $ticketIds)
            ->update([
                'status' => TicketStatus::Closed,
            ]);
    }
}
