<?php

use App\Actions\Tickets\BulkCloseTicketsAction;
use App\Actions\Tickets\BulkDeleteTicketsAction;
use App\Actions\Tickets\ChangeTicketStatusAction;
use App\Actions\Tickets\DeleteTicketAction;
use App\Actions\Tickets\UpdateTicketAction;
use App\Models\Ticket;
use App\Models\User;
use App\Support\Tickets\TicketIndexQuery;
use App\TicketPriority;
use App\TicketStatus;
use App\TicketWorkflowStep;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')]
class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';
    #[Url]
    public string $status = '';
    #[Url]
    public string $priority = '';
    #[Url]
    public string $workflow = '';
    #[Url(as: 'assignee')]
    public string $assignedUser = '';
    #[Url(as: 'sort')]
    public string $sortField = 'created_at';
    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';
    public int $perPage = 10;
    public array $selected = [];
    public ?int $editingId = null;
    public string $editSubject = '';
    public string $editDescription = '';
    public string $editStatus = 'open';
    public string $editPriority = 'medium';
    public string $editWorkflow = 'new';
    public string $editAssignedUser = '';

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

    public function updatingWorkflow(): void
    {
        $this->resetPage();
    }

    public function updatingAssignedUser(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        $allowedFields = ['id', 'subject', 'status', 'priority', 'workflow_step', 'created_at'];
        if (!in_array($field, $allowedFields, true)) {
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
        $this->workflow = '';
        $this->assignedUser = '';
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
        $this->perPage = 10;
        $this->resetPage();
    }

    public function startEdit(int $ticketId): void
    {
        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            return;
        }
        $this->editingId = $ticket->id;
        $this->editSubject = $ticket->subject;
        $this->editDescription = $ticket->description;
        $this->editStatus = $ticket->status->value;
        $this->editPriority = $ticket->priority->value;
        $this->editWorkflow = $ticket->workflow_step->value;
        $this->editAssignedUser = $ticket->assigned_user_id ? (string)$ticket->assigned_user_id : '';
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editSubject = '';
        $this->editDescription = '';
        $this->editStatus = TicketStatus::Open->value;
        $this->editPriority = TicketPriority::Medium->value;
        $this->editWorkflow = TicketWorkflowStep::New->value;
        $this->editAssignedUser = '';
        $this->resetValidation();
    }

    public function saveInline(UpdateTicketAction $updateTicketAction, int $ticketId): void
    {
        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            return;
        }
        $validated = $this->validate(['editSubject' => 'required|string|min:3|max:255', 'editDescription' => 'required|string|min:10', 'editStatus' => 'required|in:' . implode(',', TicketStatus::values()), 'editPriority' => 'required|in:' . implode(',', TicketPriority::values()), 'editWorkflow' => 'required|in:' . implode(',', TicketWorkflowStep::values()), 'editAssignedUser' => 'nullable|exists:users,id',], ['editSubject.required' => 'Het onderwerp is verplicht.', 'editSubject.min' => 'Het onderwerp moet minstens 3 tekens bevatten.', 'editSubject.max' => 'Het onderwerp mag maximaal 255 tekens bevatten.', 'editDescription.required' => 'De beschrijving is verplicht.', 'editDescription.min' => 'De beschrijving moet minstens 10 tekens bevatten.', 'editStatus.required' => 'Kies een status.', 'editStatus.in' => 'De gekozen status is ongeldig.', 'editPriority.required' => 'Kies een prioriteit.', 'editPriority.in' => 'De gekozen prioriteit is ongeldig.', 'editWorkflow.required' => 'Kies een workflow.', 'editWorkflow.in' => 'De gekozen workflow is ongeldig.', 'editAssignedUser.exists' => 'De gekozen behandelaar bestaat niet.',]);
        $updateTicketAction->execute($ticket, ['subject' => $validated['editSubject'], 'description' => $validated['editDescription'], 'status' => $validated['editStatus'], 'priority' => $validated['editPriority'], 'workflow_step' => $validated['editWorkflow'], 'assigned_user_id' => $validated['editAssignedUser'],]);
        $this->cancelEdit();
        session()->flash('success', 'Het ticket werd inline bijgewerkt.');
    }

    public function changeStatus(ChangeTicketStatusAction $changeTicketStatusAction, int $ticketId, string $status): void
    {
        if (!in_array($status, TicketStatus::values(), true)) {
            return;
        }
        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            return;
        }
        $changeTicketStatusAction->execute($ticket, $status);
        session()->flash('success', 'De status van het ticket werd succesvol aangepast.');
    }

    public function delete(DeleteTicketAction $deleteTicketAction, int $ticketId): void
    {
        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            return;
        }
        $deleteTicketAction->execute($ticket);
        $this->selected = array_values(array_filter($this->selected, fn($id) => (int)$id !== $ticketId));
        if ($this->editingId === $ticketId) {
            $this->cancelEdit();
        }
        session()->flash('success', 'Het ticket werd succesvol verwijderd.');
        $this->resetPage();
    }

    public function bulkClose(BulkCloseTicketsAction $bulkCloseTicketsAction): void
    {
        if (empty($this->selected)) {
            return;
        }
        $count = $bulkCloseTicketsAction->execute($this->selected);
        $this->selected = [];
        session()->flash('success', "{$count} ticket(s) werden op gesloten gezet.");
    }

    public function bulkDelete(BulkDeleteTicketsAction $bulkDeleteTicketsAction): void
    {
        if (empty($this->selected)) {
            return;
        }
        $count = $bulkDeleteTicketsAction->execute($this->selected);
        $this->selected = [];
        if ($this->editingId !== null) {
            $this->cancelEdit();
        }
        session()->flash('success', "{$count} ticket(s) werden verwijderd.");
        $this->resetPage();
    }

    public function selectCurrentPage(): void
    {
        $this->selected = $this->tickets->getCollection()->pluck('id')->map(fn($id) => (string)$id)->toArray();
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
        if ($this->workflow !== '') {
            $count++;
        }
        if ($this->assignedUser !== '') {
            $count++;
        }
        return $count;
    }

    #[Computed]
    public function assignees()
    {
        return User::query()->orderBy('name')->get();
    }

    #[Computed]
    public function tickets()
    {
        $allowedFields = ['id', 'subject', 'status', 'priority', 'workflow_step', 'created_at'];
        $allowedDirections = ['asc', 'desc'];
        $allowedPerPage = [5, 10, 25, 50];
        return app(TicketIndexQuery::class)->execute(['search' => $this->search, 'status' => $this->status, 'priority' => $this->priority, 'workflow' => $this->workflow, 'assigned_user_id' => $this->assignedUser, 'sortField' => in_array($this->sortField, $allowedFields, true) ? $this->sortField : 'created_at', 'sortDirection' => in_array($this->sortDirection, $allowedDirections, true) ? $this->sortDirection : 'desc', 'perPage' => in_array($this->perPage, $allowedPerPage, true) ? $this->perPage : 10,]);
    }

    public function statusOptions(): array
    {
        return TicketStatus::options();
    }

    public function priorityOptions(): array
    {
        return TicketPriority::options();
    }

    public function workflowOptions(): array
    {
        return TicketWorkflowStep::options();
    }
};
?>
<div class="min-h-screen bg-gray-100 py-10">
    <div class="mx-auto max-w-7xl px-4">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div><h1 class="text-3xl font-bold text-gray-900"> Tickets overzicht </h1>
                <p class="mt-2 text-sm text-gray-600"> Beheer support tickets rechtstreeks vanuit één interactieve
                    Livewire werkpagina. </p></div>
            <div class="flex flex-wrap items-center gap-3">
                <button type="button" wire:click="clearFilters"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                    Filters resetten
                </button>
                <a href="{{ route('tickets.create') }}"
                   class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    Nieuw ticket </a></div>
        </div> @if (session()->has('success'))
            <div
                class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"> {{ session('success') }} </div>
        @endif
        <div class="mb-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <div class="grid gap-4 md:grid-cols-4 xl:grid-cols-6">
                <div><label for="search" class="mb-2 block text-sm font-medium text-gray-700"> Zoeken </label> <input
                        id="search" type="text" wire:model.live="search"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Zoek op onderwerp of beschrijving"></div>
                <div><label for="status" class="mb-2 block text-sm font-medium text-gray-700"> Status </label> <select
                        id="status" wire:model.live="status"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Alle statussen</option> @foreach ($this->statusOptions() as $option)
                            <option value="{{ $option['value'] }}"> {{ $option['label'] }} </option>
                        @endforeach </select></div>
                <div><label for="priority" class="mb-2 block text-sm font-medium text-gray-700"> Prioriteit </label>
                    <select id="priority" wire:model.live="priority"
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Alle prioriteiten</option> @foreach ($this->priorityOptions() as $option)
                            <option value="{{ $option['value'] }}"> {{ $option['label'] }} </option>
                        @endforeach </select></div>
                <div><label for="workflow" class="mb-2 block text-sm font-medium text-gray-700"> Workflow </label>
                    <select id="workflow" wire:model.live="workflow"
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Alle stappen</option> @foreach ($this->workflowOptions() as $option)
                            <option value="{{ $option['value'] }}"> {{ $option['label'] }} </option>
                        @endforeach </select></div>
                <div><label for="assignedUser" class="mb-2 block text-sm font-medium text-gray-700"> Toegewezen
                        aan </label> <select id="assignedUser" wire:model.live="assignedUser"
                                             class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Iedereen</option> @foreach ($this->assignees as $assignee)
                            <option value="{{ $assignee->id }}"> {{ $assignee->name }} </option>
                        @endforeach </select></div>
                <div><label for="perPage" class="mb-2 block text-sm font-medium text-gray-700"> Per pagina </label>
                    <select id="perPage" wire:model.live="perPage"
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select></div>
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-gray-600"><span
                    class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 font-medium text-gray-700"> Actieve filters: {{ $this->activeFilterCount }} </span>
                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 font-medium text-gray-700"> Geselecteerd: {{ count($selected) }} </span>
                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 font-medium text-gray-700"> Totaal op deze pagina: {{ $this->tickets->count() }} </span>
            </div>
        </div>
        <div class="mb-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div><h2 class="text-base font-semibold text-gray-900"> Bulk-acties </h2>
                    <p class="mt-1 text-sm text-gray-600"> Selecteer meerdere tickets en voer één actie uit op de hele
                        selectie. </p></div>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" wire:click="selectCurrentPage"
                            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                        Selecteer huidige pagina
                    </button>
                    <button type="button" wire:click="clearSelection"
                            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                        Wis selectie
                    </button>
                    <button type="button" wire:click="bulkClose" wire:loading.attr="disabled" wire:target="bulkClose"
                            class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50">
                        Zet selectie op gesloten
                    </button>
                    <button type="button" wire:click="bulkDelete"
                            wire:confirm="Weet je zeker dat je alle geselecteerde tickets wilt verwijderen?"
                            wire:loading.attr="disabled" wire:target="bulkDelete"
                            class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50">
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
                                ID @if ($sortField === 'id')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif </button>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <button wire:click="sortBy('subject')" class="inline-flex items-center gap-2">
                                Onderwerp @if ($sortField === 'subject')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif </button>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <button wire:click="sortBy('status')" class="inline-flex items-center gap-2">
                                Status @if ($sortField === 'status')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif </button>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <button wire:click="sortBy('priority')" class="inline-flex items-center gap-2">
                                Prioriteit @if ($sortField === 'priority')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif </button>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <button wire:click="sortBy('workflow_step')" class="inline-flex items-center gap-2">
                                Workflow @if ($sortField === 'workflow_step')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif </button>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            Toegewezen aan
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <button wire:click="sortBy('created_at')" class="inline-flex items-center gap-2"> Aangemaakt
                                op @if ($sortField === 'created_at')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif </button>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            Snelle acties
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-600">
                            Beheer
                        </th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white"> @forelse ($this->tickets as $ticket)
                        <tr wire:key="ticket-{{ $ticket->id }}" class="align-top hover:bg-gray-50">
                            <td class="px-6 py-4"><input type="checkbox" value="{{ $ticket->id }}"
                                                         wire:model.live="selected"
                                                         class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700"> #{{ $ticket->id }} </td>
                            <td class="px-6 py-4"> @if ($editingId === $ticket->id)
                                    <div class="space-y-3">
                                        <div><input type="text" wire:model="editSubject"
                                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"> @error('editSubject')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror </div>
                                        <div><textarea rows="3" wire:model="editDescription"
                                                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea> @error('editDescription')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror </div>
                                        <div class="grid gap-3 md:grid-cols-2">
                                            <div><select wire:model="editStatus"
                                                         class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"> @foreach ($this->statusOptions() as $option)
                                                        <option
                                                            value="{{ $option['value'] }}"> {{ $option['label'] }} </option>
                                                    @endforeach </select> @error('editStatus') <p
                                                    class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror</div>
                                            <div><select wire:model="editPriority"
                                                         class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"> @foreach ($this->priorityOptions() as $option)
                                                        <option
                                                            value="{{ $option['value'] }}"> {{ $option['label'] }} </option>
                                                    @endforeach </select> @error('editPriority') <p
                                                    class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror</div>
                                        </div>
                                        <div class="grid gap-3 md:grid-cols-2">
                                            <div><select wire:model="editWorkflow"
                                                         class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"> @foreach ($this->workflowOptions() as $option)
                                                        <option
                                                            value="{{ $option['value'] }}"> {{ $option['label'] }} </option>
                                                    @endforeach </select> @error('editWorkflow') <p
                                                    class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror</div>
                                            <div><select wire:model="editAssignedUser"
                                                         class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                    <option value="">Niet toegewezen
                                                    </option> @foreach ($this->assignees as $assignee)
                                                        <option
                                                            value="{{ $assignee->id }}"> {{ $assignee->name }} </option>
                                                    @endforeach </select> @error('editAssignedUser') <p
                                                    class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror</div>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <button type="button" wire:click="saveInline({{ $ticket->id }})"
                                                    wire:loading.attr="disabled" wire:target="saveInline"
                                                    class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                                                Opslaan
                                            </button>
                                            <button type="button" wire:click="cancelEdit"
                                                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                                                Annuleren
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <div class="text-sm font-semibold text-gray-900"><a
                                            href="{{ route('tickets.show', $ticket) }}"
                                            class="transition hover:text-blue-600 hover:underline"> {{ $ticket->subject }} </a>
                                    </div>
                                    <div
                                        class="mt-1 line-clamp-2 text-sm text-gray-500"> {{ $ticket->description }} </div>
                                @endif </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm"> @if ($editingId === $ticket->id)
                                    <span
                                        class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700"> Inline edit actief </span>
                                @else
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $ticket->statusBadgeClasses() }}"> {{ $ticket->statusLabel() }} </span>
                                @endif </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm"> @if ($editingId === $ticket->id)
                                    <span
                                        class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700"> Bewerken </span>
                                @else
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $ticket->priorityBadgeClasses() }}"> {{ $ticket->priorityLabel() }} </span>
                                @endif </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm"> @if ($editingId === $ticket->id)
                                    <span
                                        class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700"> Inline edit actief </span>
                                @else
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $ticket->workflowBadgeClasses() }}"> {{ $ticket->workflowLabel() }} </span>
                                @endif </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700"> @if ($editingId === $ticket->id)
                                    <span
                                        class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700"> {{ $editAssignedUser !== '' ? 'Assignee gekozen' : 'Niet toegewezen' }} </span>
                                @else
                                    {{ $ticket->assigneeName() }}
                                @endif </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600"> {{ $ticket->created_at->format('d/m/Y H:i') }} </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" wire:click="changeStatus({{ $ticket->id }}, 'open')"
                                            wire:loading.attr="disabled" wire:target="changeStatus"
                                            class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700 transition hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-50">
                                        Open
                                    </button>
                                    <button type="button" wire:click="changeStatus({{ $ticket->id }}, 'in_progress')"
                                            wire:loading.attr="disabled" wire:target="changeStatus"
                                            class="inline-flex items-center rounded-lg border border-yellow-200 bg-yellow-50 px-3 py-2 text-xs font-medium text-yellow-700 transition hover:bg-yellow-100 disabled:cursor-not-allowed disabled:opacity-50">
                                        In behandeling
                                    </button>
                                    <button type="button" wire:click="changeStatus({{ $ticket->id }}, 'closed')"
                                            wire:loading.attr="disabled" wire:target="changeStatus"
                                            class="inline-flex items-center rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs font-medium text-green-700 transition hover:bg-green-100 disabled:cursor-not-allowed disabled:opacity-50">
                                        Gesloten
                                    </button>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-3"> @if ($editingId === $ticket->id)
                                        <button type="button" wire:click="cancelEdit"
                                                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                                            Stop edit
                                        </button>
                                    @else
                                        <button type="button" wire:click="startEdit({{ $ticket->id }})"
                                                class="inline-flex items-center rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-2 text-xs font-medium text-indigo-700 shadow-sm transition hover:bg-indigo-100">
                                            Inline edit
                                        </button>
                                    @endif <a href="{{ route('tickets.show', $ticket) }}"
                                              class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                                        Openen </a>
                                    <button type="button" wire:click="delete({{ $ticket->id }})"
                                            wire:confirm="Weet je zeker dat je dit ticket wilt verwijderen?"
                                            wire:loading.attr="disabled" wire:target="delete"
                                            class="inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50">
                                        Verwijderen
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-10 text-center text-sm text-gray-500"> Geen tickets gevonden
                                voor de huidige filters.
                            </td>
                        </tr>
                    @endforelse </tbody>
                </table>
            </div>
            <div
                class="border-t border-gray-200 px-6 py-4"> {{ $this->tickets->links(data: ['scrollTo' => false]) }} </div>
        </div>
    </div>
</div>
