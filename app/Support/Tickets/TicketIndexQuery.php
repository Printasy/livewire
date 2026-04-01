<?php

namespace App\Support\Tickets;

use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TicketIndexQuery
{
    public function execute(array $filters): LengthAwarePaginator
    {
        $search = $filters['search'] ?? '';
        $status = $filters['status'] ?? '';
        $priority = $filters['priority'] ?? '';
        $workflow = $filters['workflow'] ?? '';
        $assignedUserId = $filters['assigned_user_id'] ?? '';
        $sortField = $filters['sortField'] ?? 'created_at';
        $sortDirection = $filters['sortDirection'] ?? 'desc';
        $perPage = $filters['perPage'] ?? 10;

        return Ticket::query()
            ->with('assignee')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('subject', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($priority !== '', function ($query) use ($priority) {
                $query->where('priority', $priority);
            })
            ->when($workflow !== '', function ($query) use ($workflow) {
                $query->where('workflow_step', $workflow);
            })
            ->when($assignedUserId !== '', function ($query) use ($assignedUserId) {
                $query->where('assigned_user_id', $assignedUserId);
            })
            ->orderBy($sortField, $sortDirection)
            ->paginate($perPage);
    }
}
