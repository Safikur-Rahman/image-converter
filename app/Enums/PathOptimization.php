<?php

namespace App\Enums;

enum PathOptimization: string
{
    case Simple = 'simple';
    case Balanced = 'balanced';
    case Aggressive = 'aggressive';

    public function label(): string
    {
        return match ($this) {
            self::Simple => 'Simple',
            self::Balanced => 'Balanced',
            self::Aggressive => 'Aggressive',
        };
    }
}