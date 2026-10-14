<?php

namespace App\Enums;

enum ConversionStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case BackgroundRemoving = 'background_removing';
    case Vectorizing = 'vectorizing';
    case Optimizing = 'optimizing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::BackgroundRemoving => 'Removing background',
            self::Vectorizing => 'Creating vector',
            self::Optimizing => 'Optimizing SVG',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Failed, self::Cancelled], true);
    }
}