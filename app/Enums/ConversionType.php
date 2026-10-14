<?php

namespace App\Enums;

enum ConversionType: string
{
    case Color = 'color';
    case BlackWhite = 'b_w';

    public function label(): string
    {
        return match ($this) {
            self::Color => 'Color',
            self::BlackWhite => 'Black & White',
        };
    }
}