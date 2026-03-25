<?php

namespace Lunar\Enums;

enum DevelopmentUpdateStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::InProgress => 'In progress',
            self::Completed => 'Completed',
        };
    }
}
