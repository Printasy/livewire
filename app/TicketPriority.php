<?php

namespace App;

enum TicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Laag',
            self::Medium => 'Normaal',
            self::High => 'Hoog',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Low => 'bg-gray-100 text-gray-700',
            self::Medium => 'bg-orange-100 text-orange-700',
            self::High => 'bg-red-100 text-red-700',
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
