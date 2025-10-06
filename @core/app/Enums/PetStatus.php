<?php

namespace App\Enums;

enum PetStatus: string
{
    case AVAILABLE = 'available';
    case PENDING   = 'pending';
    case SOLD      = 'sold';

    /**
     * Return all statuses as an array of values.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(fn(self $status) => $status->value, self::cases());
    }

    /**
     * Get a human-readable label for the status.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Dostępny',
            self::PENDING   => 'Oczekujący',
            self::SOLD      => 'Sprzedany',
        };
    }

    /**
     * Return all statuses with their labels.
     *
     * @return array<string, string>  e.g. ['available' => 'Available', ...]
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }
}
