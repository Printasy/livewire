
<?php

namespace App;

enum TicketWorkflowStep: string
{
    case New = 'new';
    case Triage = 'triage';
    case Investigating = 'investigating';
    case WaitingCustomer = 'waiting_customer';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nieuw',
            self::Triage => 'Triage',
            self::Investigating => 'Onderzoek',
            self::WaitingCustomer => 'Wacht op klant',
            self::Resolved => 'Opgelost',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::New => 'bg-slate-100 text-slate-700',
            self::Triage => 'bg-indigo-100 text-indigo-700',
            self::Investigating => 'bg-purple-100 text-purple-700',
            self::WaitingCustomer => 'bg-amber-100 text-amber-700',
            self::Resolved => 'bg-emerald-100 text-emerald-700',
        };
    }

    public static function values(): array
    {
        return array_map(
            fn (self $case) => $case->value,
            self::cases()
        );
    }

    public static function options(): array
    {
        return array_map(
            fn (self $case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            self::cases()
        );
    }
}
