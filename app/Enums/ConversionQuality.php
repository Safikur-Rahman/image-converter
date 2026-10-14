<?php

namespace App\Enums;

enum ConversionQuality: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
        };
    }

    public function blur(): float
    {
        // Gaussian blur used to reduce detail during tracing
        return match ($this) {
            self::Low => 0.6,
            self::Medium => 0.3,
            self::High => 0.0,
        };
    }

    public function colorCount(): int
    {
        return match ($this) {
            self::Low => 8,
            self::Medium => 24,
            self::High => 64,
        };
    }

    /**
     * Edge-smoothing passes applied before tracing. Fewer passes = sharper,
     * more truthful edges at the cost of more tiny noise contours, which the
     * engine's despeckle step then removes.
     */
    public function blurPasses(): int
    {
        return match ($this) {
            self::Low => 3,
            self::Medium => 2,
            self::High => 1,
        };
    }
}