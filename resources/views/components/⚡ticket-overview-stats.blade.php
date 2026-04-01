<?php

use App\Models\Ticket;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public Ticket $ticket;

    #[Computed]
    public function stats(): array
    {
        $commentsCount = $this->ticket->comments()
            ->where('type', 'comment')
            ->count();

        $notesCount = $this->ticket->comments()
            ->where('type', 'note')
            ->count();

        $attachmentsCount = $this->ticket->attachments()
            ->count();

        return [
            'comments' => $commentsCount,
            'notes' => $notesCount,
            'attachments' => $attachmentsCount,
            'total' => $commentsCount + $notesCount + $attachmentsCount,
        ];
    }

    #[On('comment-created')]
    #[On('comment-deleted')]
    #[On('attachment-created')]
    #[On('attachment-deleted')]
    public function refreshStats(): void
    {
        unset($this->stats);
        $this->ticket->refresh();
    }
};
?>

@php
    $stats = $this->stats;
@endphp

<div class="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
    <div class="mb-4">
        <h2 class="text-lg font-semibold text-gray-900">
            Ticketstatistieken
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            Deze cijfers verversen automatisch wanneer comments, notities of bestanden wijzigen.
        </p>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-xl bg-blue-50 p-4 ring-1 ring-blue-100">
            <div class="text-xs font-semibold uppercase tracking-wide text-blue-700">
                Comments
            </div>
            <div class="mt-2 text-3xl font-bold text-blue-900">
                {{ $stats['comments'] }}
            </div>
        </div>

        <div class="rounded-xl bg-yellow-50 p-4 ring-1 ring-yellow-100">
            <div class="text-xs font-semibold uppercase tracking-wide text-yellow-700">
                Interne notities
            </div>
            <div class="mt-2 text-3xl font-bold text-yellow-900">
                {{ $stats['notes'] }}
            </div>
        </div>

        <div class="rounded-xl bg-purple-50 p-4 ring-1 ring-purple-100">
            <div class="text-xs font-semibold uppercase tracking-wide text-purple-700">
                Bestanden
            </div>
            <div class="mt-2 text-3xl font-bold text-purple-900">
                {{ $stats['attachments'] }}
            </div>
        </div>

        <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-gray-200">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-700">
                Totaal items
            </div>
            <div class="mt-2 text-3xl font-bold text-gray-900">
                {{ $stats['total'] }}
            </div>
        </div>
    </div>
</div>
