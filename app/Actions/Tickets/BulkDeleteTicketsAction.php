<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;

class BulkDeleteTicketsAction
{
    public function execute(array $ticketIds): int
    {
        if (empty($ticketIds)) {
            return 0;
        }

        return Ticket::query()
            ->whereIn('id', $ticketIds)
            ->delete();
    }
}
