<?php

use App\Models\Ticket;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $priority = '';

    #[Url(as: 'sort')]
    public string $sortField = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    public int $perPage = 10;
    public array $selected = [];
    public ?int $editingId = null;

    public string $editSubject = '';
    public string $editStatus = 'open';
    public string $editPriority = 'medium';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingPriority(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        $allowedFields = ['id', 'subject', 'status', 'priority', 'created_at'];

        if (! in_array($field, $allowedFields, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->priority = '';
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
        $this->perPage = 10;

        $this->resetPage();
    }

    public function startEdit(int $ticketId): void
    {
        $ticket = Ticket::find($ticketId);

        if (! $ticket) {
            return;
        }

        $this->editingId = $ticket->id;
        $this->editSubject = $ticket->subject;
        $this->editStatus = $ticket->status;
        $this->editPriority = $ticket->priority;
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editSubject = '';
        $this->editStatus = 'open';
        $this->editPriority = 'medium';
    }

    public function saveInline(int $ticketId): void
    {
        $ticket = Ticket::find($ticketId);

        if (! $ticket) {
            return;
        }

        $validated = $this->validate(
            [
                'editSubject' => 'required|min:3|max:255',
                'editStatus' => 'required|in:open,in_progress,closed',
                'editPriority' => 'required|in:low,medium,high',
            ],
            [
                'editSubject.required' => 'Het onderwerp is verplicht.',
                'editSubject.min' => 'Het onderwerp moet minstens 3 tekens bevatten.',
                'editSubject.max' => 'Het onderwerp mag maximaal 255 tekens bevatten.',
                'editStatus.required' => 'Kies een status.',
                'editStatus.in' => 'De gekozen status is ongeldig.',
                'editPriority.required' => 'Kies een prioriteit.',
                'editPriority.in' => 'De gekozen prioriteit is ongeldig.',
            ]
        );

        $ticket->update([
            'subject' => $validated['editSubject'],
            'status' => $validated['editStatus'],
            'priority' => $validated['editPriority'],
        ]);

        $this->cancelEdit();

        session()->flash('success', 'Het ticket werd inline bijgewerkt.');
    }

    public function changeStatus(int $ticketId, string $status): void
    {
        $allowedStatuses = ['open', 'in_progress', 'closed'];

        if (! in_array($status, $allowedStatuses, true)) {
            return;
        }

        $ticket = Ticket::find($ticketId);

        if (! $ticket) {
            return;
        }

        $ticket->update([
            'status' => $status,
        ]);

        session()->flash('success', 'De status van het ticket werd succesvol aangepast.');
    }

    public function delete(int $ticketId): void
    {
        $ticket = Ticket::find($ticketId);

        if (! $ticket) {
            return;
        }

        $ticket->delete();

        $this->selected = array_values(array_filter(
            $this->selected,
            fn ($id) => (int) $id !== $ticketId
        ));

        if ($this->editingId === $ticketId) {
            $this->cancelEdit();
        }

        session()->flash('success', 'Het ticket werd succesvol verwijderd.');

        $this->resetPage();
    }

    public function bulkClose(): void
    {
        if (empty($this->selected)) {
            return;
        }

        Ticket::query()
            ->whereIn('id', $this->selected)
            ->update([
                'status' => 'closed',
            ]);

        $count = count($this->selected);
        $this->selected = [];

        session()->flash('success', "{$count} ticket(s) werden op gesloten gezet.");
    }

    public function bulkDelete(): void
    {
        if (empty($this->selected)) {
            return;
        }

        Ticket::query()
            ->whereIn('id', $this->selected)
            ->delete();

        $count = count($this->selected);
        $this->selected = [];

        if ($this->editingId !== null) {
            $this->cancelEdit();
        }

        session()->flash('success', "{$count} ticket(s) werden verwijderd.");

        $this->resetPage();
    }

    public function selectCurrentPage(): void
    {
        $this->selected = $this->tickets->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    #[Computed]
    public function activeFilterCount(): int
    {
        $count = 0;

        if ($this->search !== '') {
            $count++;
        }

        if ($this->status !== '') {
            $count++;
        }

        if ($this->priority !== '') {
            $count++;
        }

        return $count;
    }

    #[Computed]
    public function tickets()
    {
        return Ticket::query()
            ->when($this->search !== '', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('subject', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status !== '', function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->priority !== '', function ($query) {
                $query->where('priority', $this->priority);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }
};
?>

<div class="min-h-screen bg-gray-100 py-10">
    <div class="mx-auto max-w-7xl px-4">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    Tickets overzicht
                </h1>
                <p class="mt-2 text-sm text-gray-600">
                    Beheer support tickets rechtstreeks vanuit één interactieve Livewire werkpagina.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    wire:click="clearFilters"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                >
                    Filters resetten
                </button>

                <a
                    href="{{ route('tickets.create') }}"
                    class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                >
                    Nieuw ticket
                </a>
            </div>
        </div>

        @if (session()->has('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    <label for="search" class="mb-2 block text-sm font-medium text-gray-700">
                        Zoeken
                    </label>
                    <input
                        id="search"
                        type="text"
                        wire:model.live="search"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Zoek op onderwerp of beschrijving"
                    >
                </div>

                <div>
                    <label for="status" class="mb-2 block text-sm font-medium text-gray-700">
                        Status
                    </label>
                    <select
                        id="status"
                        wire:model.live="status"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Alle statussen</option>
                        <option value="open">Open</option>
                        <option value="in_progress">In behandeling</option>
                        <option value="closed">Gesloten</option>
                    </select>
                </div>

                <div>
                    <label for="priority" class="mb-2 block text-sm font-medium text-gray-700">
                        Prioriteit
                    </label>
                    <select
                        id="priority"
                        wire:model.live="priority"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Alle prioriteiten</option>
                        <option value="low">Laag</option>
                        <option value="medium">Normaal</option>
                        <option value="high">Hoog</option>
                    </select>
                </div>

                <div>
                    <label for="perPage" class="mb-2 block text-sm font-medium text-gray-700">
                        Per pagina
                    </label>
                    <select
                        id="perPage"
                        wire:model.live="perPage"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-gray-600">
                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 font-medium text-gray-700">
                    Actieve filters: {{ $this->activeFilterCount }}
                </span>
                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 font-medium text-gray-700">
                    Geselecteerd: {{ count($selected) }}
                </span>
                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 font-medium text-gray-700">
                    Totaal op deze pagina: {{ $this->tickets->count() }}
                </span>
            </div>
        </div>

        <div class="mb-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">
                        Bulk-acties
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Selecteer meerdere tickets en voer één actie uit op de hele selectie.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        wire:click="selectCurrentPage"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                    >
                        Selecteer huidige pagina
                    </button>

                    <button
                        type="button"
                        wire:click="clearSelection"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                    >
                        Wis selectie
                    </button>

                    <button
                        type="button"
                        wire:click="bulkClose"
                        wire:loading.attr="disabled"
                        wire:target="bulkClose"
                        class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Zet selectie op gesloten
                    </button>

                    <button
                        type="button"
                        wire:click="bulkDelete"
                        wire:confirm="Weet je zeker dat je alle geselecteerde tickets wilt verwijderen?"
                        wire:loading.attr="disabled"
                        wire:target="bulkDelete"
                        class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Verwijder selectie
                    </button>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            Selectie
                        </th>

                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <button wire:click="sortBy('id')" class="inline-flex items-center gap-2">
                                ID
                                @if ($sortField === 'id')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>

                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <button wire:click="sortBy('subject')" class="inline-flex items-center gap-2">
                                Onderwerp
                                @if ($sortField === 'subject')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>

                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <button wire:click="sortBy('status')" class="inline-flex items-center gap-2">
                                Status
                                @if ($sortField === 'status')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>

                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <button wire:click="sortBy('priority')" class="inline-flex items-center gap-2">
                                Prioriteit
                                @if ($sortField === 'priority')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>

                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <button wire:click="sortBy('created_at')" class="inline-flex items-center gap-2">
                                Aangemaakt op
                                @if ($sortField === 'created_at')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>

                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            Snelle acties
                        </th>

                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-600">
                            Beheer
                        </th>
                    </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($this->tickets as $ticket)
                        <tr wire:key="ticket-{{ $ticket->id }}" class="align-top hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <input
                                    type="checkbox"
                                    value="{{ $ticket->id }}"
                                    wire:model.live="selected"
                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                >
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                #{{ $ticket->id }}
                            </td>

                            <td class="px-6 py-4">
                                @if ($editingId === $ticket->id)
                                    <div class="space-y-3">
                                        <div>
                                            <input
                                                type="text"
                                                wire:model="editSubject"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            >
                                            @error('editSubject')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="grid gap-3 md:grid-cols-2">
                                            <div>
                                                <select
                                                    wire:model="editStatus"
                                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                >
                                                    <option value="open">Open</option>
                                                    <option value="in_progress">In behandeling</option>
                                                    <option value="closed">Gesloten</option>
                                                </select>
                                                @error('editStatus')
                                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div>
                                                <select
                                                    wire:model="editPriority"
                                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                >
                                                    <option value="low">Laag</option>
                                                    <option value="medium">Normaal</option>
                                                    <option value="high">Hoog</option>
                                                </select>
                                                @error('editPriority')
                                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-3">
                                            <button
                                                type="button"
                                                wire:click="saveInline({{ $ticket->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="saveInline({{ $ticket->id }})"
                                                class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                Opslaan
                                            </button>

                                            <button
                                                type="button"
                                                wire:click="cancelEdit"
                                                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                                            >
                                                Annuleren
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <div class="text-sm font-semibold text-gray-900">
                                        <a
                                            href="{{ route('tickets.show', $ticket) }}"
                                            class="transition hover:text-blue-600 hover:underline"
                                        >
                                            {{ $ticket->subject }}
                                        </a>
                                    </div>
                                    <div class="mt-1 line-clamp-2 text-sm text-gray-500">
                                        {{ $ticket->description }}
                                    </div>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                @if ($editingId === $ticket->id)
                                    <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                            Inline edit actief
                                        </span>
                                @else
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $ticket->statusBadgeClasses() }}">
                                            {{ $ticket->statusLabel() }}
                                        </span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                @if ($editingId === $ticket->id)
                                    <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                            Bewerken
                                        </span>
                                @else
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $ticket->priorityBadgeClasses() }}">
                                            {{ $ticket->priorityLabel() }}
                                        </span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                {{ $ticket->created_at->format('d/m/Y H:i') }}
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        wire:click="changeStatus({{ $ticket->id }}, 'open')"
                                        wire:loading.attr="disabled"
                                        wire:target="changeStatus({{ $ticket->id }}, 'open')"
                                        class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700 transition hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        Open
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="changeStatus({{ $ticket->id }}, 'in_progress')"
                                        wire:loading.attr="disabled"
                                        wire:target="changeStatus({{ $ticket->id }}, 'in_progress')"
                                        class="inline-flex items-center rounded-lg border border-yellow-200 bg-yellow-50 px-3 py-2 text-xs font-medium text-yellow-700 transition hover:bg-yellow-100 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        In behandeling
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="changeStatus({{ $ticket->id }}, 'closed')"
                                        wire:loading.attr="disabled"
                                        wire:target="changeStatus({{ $ticket->id }}, 'closed')"
                                        class="inline-flex items-center rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs font-medium text-green-700 transition hover:bg-green-100 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        Gesloten
                                    </button>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-3">
                                    @if ($editingId === $ticket->id)
                                        <button
                                            type="button"
                                            wire:click="cancelEdit"
                                            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                                        >
                                            Stop edit
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="startEdit({{ $ticket->id }})"
                                            class="inline-flex items-center rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-2 text-xs font-medium text-indigo-700 shadow-sm transition hover:bg-indigo-100"
                                        >
                                            Inline edit
                                        </button>
                                    @endif

                                    <a
                                        href="{{ route('tickets.show', $ticket) }}"
                                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                                    >
                                        Openen
                                    </a>

                                    <button
                                        type="button"
                                        wire:click="delete({{ $ticket->id }})"
                                        wire:confirm="Weet je zeker dat je dit ticket wilt verwijderen?"
                                        wire:loading.attr="disabled"
                                        wire:target="delete({{ $ticket->id }})"
                                        class="inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        Verwijderen
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-sm text-gray-500">
                                Geen tickets gevonden voor de huidige filters.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-6 py-4">
                {{ $this->tickets->links(data: ['scrollTo' => false]) }}
            </div>
        </div>
    </div>
</div>
