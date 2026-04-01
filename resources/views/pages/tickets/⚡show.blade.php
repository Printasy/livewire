<?php use App\Models\Ticket;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app')]
class extends Component {
    public Ticket $ticket;
    public string $subject = '';
    public string $description = '';
    public string $status = 'open';
    public string $priority = 'medium';
    public string $workflow_step = 'new';
    public ?string $assigned_user_id = null;

    public function mount(Ticket $ticket): void
    {
        $this->ticket = $ticket;
        $this->subject = $ticket->subject;
        $this->description = $ticket->description;
        $this->status = $ticket->status instanceof \BackedEnum ? $ticket->status->value : $ticket->status;
        $this->priority = $ticket->priority instanceof \BackedEnum ? $ticket->priority->value : $ticket->priority;
        $this->workflow_step = $ticket->workflow_step instanceof \BackedEnum ? $ticket->workflow_step->value : $ticket->workflow_step;
        $this->assigned_user_id = $ticket->assigned_user_id ? (string)$ticket->assigned_user_id : null;
    }

    #[Computed]
    public function assignees()
    {
        return User::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        if ($this->assigned_user_id === '') {
            $this->assigned_user_id = null;
        }
        $validated = $this->validate(['subject' => 'required|string|min:3|max:255', 'description' => 'required|string|min:10', 'status' => 'required|in:open,in_progress,closed', 'priority' => 'required|in:low,medium,high', 'workflow_step' => 'required|in:new,triage,investigating,waiting_customer,resolved', 'assigned_user_id' => 'nullable|exists:users,id',], ['subject.required' => 'Het onderwerp is verplicht.', 'subject.min' => 'Het onderwerp moet minstens 3 tekens bevatten.', 'subject.max' => 'Het onderwerp mag maximaal 255 tekens bevatten.', 'description.required' => 'De beschrijving is verplicht.', 'description.min' => 'De beschrijving moet minstens 10 tekens bevatten.', 'status.required' => 'Kies een status.', 'status.in' => 'De gekozen status is ongeldig.', 'priority.required' => 'Kies een prioriteit.', 'priority.in' => 'De gekozen prioriteit is ongeldig.', 'workflow_step.required' => 'Kies een workflowstap.', 'workflow_step.in' => 'De gekozen workflowstap is ongeldig.', 'assigned_user_id.exists' => 'De gekozen behandelaar bestaat niet.',]);
        $oldStatus = $this->ticket->status instanceof \BackedEnum ? $this->ticket->status->value : $this->ticket->status;
        $oldPriority = $this->ticket->priority instanceof \BackedEnum ? $this->ticket->priority->value : $this->ticket->priority;
        $oldWorkflow = $this->ticket->workflow_step instanceof \BackedEnum ? $this->ticket->workflow_step->value : $this->ticket->workflow_step;
        $oldAssigneeName = $this->ticket->assigneeName();
        $this->ticket->update(['subject' => $validated['subject'], 'description' => $validated['description'], 'status' => $validated['status'], 'priority' => $validated['priority'], 'workflow_step' => $validated['workflow_step'], 'assigned_user_id' => $validated['assigned_user_id'] ?: null,]);
        $this->ticket->refresh();
        $newStatus = $this->ticket->status instanceof \BackedEnum ? $this->ticket->status->value : $this->ticket->status;
        $newPriority = $this->ticket->priority instanceof \BackedEnum ? $this->ticket->priority->value : $this->ticket->priority;
        $newWorkflow = $this->ticket->workflow_step instanceof \BackedEnum ? $this->ticket->workflow_step->value : $this->ticket->workflow_step;
        if ($oldStatus !== $newStatus) {
            $this->ticket->logActivity("Status gewijzigd van {$oldStatus} naar {$newStatus}.");
        }
        if ($oldPriority !== $newPriority) {
            $this->ticket->logActivity("Prioriteit gewijzigd van {$oldPriority} naar {$newPriority}.");
        }
        if ($oldWorkflow !== $newWorkflow) {
            $this->ticket->logActivity("Workflow gewijzigd van {$oldWorkflow} naar {$newWorkflow}.");
        }
        if ($oldAssigneeName !== $this->ticket->assigneeName()) {
            $this->ticket->logActivity("Toegewezen behandelaar gewijzigd van {$oldAssigneeName} naar {$this->ticket->assigneeName()}.");
        }
        $this->dispatch('ticket-activity-created');
        session()->flash('success', 'Het ticket werd succesvol bijgewerkt.');
    }

    public function delete(): mixed
    {
        $this->ticket->delete();
        session()->flash('success', 'Het ticket werd succesvol verwijderd.');
        return $this->redirect(route('tickets.index'));
    }
}; ?>
<div class="min-h-screen bg-gray-100 py-10">
    <div class="mx-auto max-w-4xl px-4">
        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div><h1 class="text-3xl font-bold text-gray-900"> Ticket #{{ $ticket->id }} </h1>
                <p class="mt-1 text-sm text-gray-600"> Bewerk dit support ticket via een Livewire detailpagina. </p>
            </div>
            <div class="flex items-center gap-3"><a href="{{ route('tickets.index') }}"
                                                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                    Terug naar overzicht </a>
                <button type="button" wire:click="delete"
                        wire:confirm="Weet je zeker dat je dit ticket wilt verwijderen?" wire:loading.attr="disabled"
                        wire:target="delete"
                        class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50">
                    Verwijderen
                </button>
            </div>
        </div> @if (session()->has('success'))
            <div
                class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"> {{ session('success') }} </div>
        @endif
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <form wire:submit="save" class="space-y-6">
                <div><label for="subject" class="mb-2 block text-sm font-medium text-gray-700"> Onderwerp </label>
                    <input id="subject" type="text" wire:model="subject"
                           class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Bijv. Login lukt niet"> @error('subject') <p
                        class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror </div>
                <div><label for="description" class="mb-2 block text-sm font-medium text-gray-700">
                        Beschrijving </label> <textarea id="description" rows="6" wire:model="description"
                                                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                        placeholder="Beschrijf het probleem zo duidelijk mogelijk..."></textarea> @error('description')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror </div>
                <div class="grid gap-6 md:grid-cols-2">
                    <div><label for="status" class="mb-2 block text-sm font-medium text-gray-700"> Status </label>
                        <select id="status" wire:model="status"
                                class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="open">Open</option>
                            <option value="in_progress">In behandeling</option>
                            <option value="closed">Gesloten</option>
                        </select> @error('status') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div><label for="priority" class="mb-2 block text-sm font-medium text-gray-700"> Prioriteit </label>
                        <select id="priority" wire:model="priority"
                                class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="low">Laag</option>
                            <option value="medium">Normaal</option>
                            <option value="high">Hoog</option>
                        </select> @error('priority') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="grid gap-6 md:grid-cols-2">
                    <div><label for="workflow_step" class="mb-2 block text-sm font-medium text-gray-700">
                            Workflow </label> <select id="workflow_step" wire:model="workflow_step"
                                                      class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="new">Nieuw</option>
                            <option value="triage">Triage</option>
                            <option value="investigating">Onderzoek</option>
                            <option value="waiting_customer">Wacht op klant</option>
                            <option value="resolved">Opgelost</option>
                        </select> @error('workflow_step') <p
                            class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror </div>
                    <div><label for="assigned_user_id" class="mb-2 block text-sm font-medium text-gray-700"> Toegewezen
                            aan </label> <select id="assigned_user_id" wire:model="assigned_user_id"
                                                 class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Niet toegewezen</option> @foreach ($this->assignees as $assignee)
                                <option value="{{ $assignee->id }}"> {{ $assignee->name }} </option>
                            @endforeach </select> @error('assigned_user_id') <p
                            class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror </div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div><span class="font-semibold">Huidige workflow:</span> {{ $ticket->workflowLabel() }} </div>
                        <div><span class="font-semibold">Huidige behandelaar:</span> {{ $ticket->assigneeName() }}
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <button type="submit" wire:loading.attr="disabled" wire:target="save"
                            class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                        Wijzigingen opslaan
                    </button>
                    <span wire:loading wire:target="save" class="text-sm text-gray-500"> Bezig met opslaan... </span>
                </div>
            </form>
        </div>
        <div class="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200"><h2
                class="mb-4 text-lg font-semibold text-gray-900"> Alleen lezen, metadata </h2>
            <div class="grid gap-4 md:grid-cols-2">
                <div><span class="block text-sm text-gray-500">Aangemaakt op</span> <span
                        class="text-sm font-medium text-gray-800"> {{ $ticket->created_at->format('d/m/Y H:i') }} </span>
                </div>
                <div><span class="block text-sm text-gray-500">Laatst bijgewerkt op</span> <span
                        class="text-sm font-medium text-gray-800"> {{ $ticket->updated_at->format('d/m/Y H:i') }} </span>
                </div>
            </div>
        </div>
        <livewire:ticket-overview-stats :ticket="$ticket" :key="'ticket-overview-stats-' . $ticket->id"/>
        <livewire:ticket-comments :ticket="$ticket" :key="'ticket-comments-' . $ticket->id"/>
        <livewire:ticket-attachments :ticket="$ticket" :key="'ticket-attachments-' . $ticket->id"/>
        <livewire:ticket-activity-log :ticket="$ticket" :key="'ticket-activity-log-' . $ticket->id"/>
    </div>
</div>
