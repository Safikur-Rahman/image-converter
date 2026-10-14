<?php

namespace Database\Factories;

use App\Enums\ConversionStatus;
use App\Enums\ConversionType;
use App\Models\Conversion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Conversion>
 */
class ConversionFactory extends Factory
{
    protected $model = Conversion::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'status' => ConversionStatus::Pending,
            'original_filename' => 'sample.png',
            'original_format' => 'png',
            'file_size' => 2048,
            'width' => 100,
            'height' => 100,
            'remove_background' => false,
            'conversion_type' => ConversionType::Color,
            'quality' => 'high',
            'path_optimization' => 'balanced',
            'disk' => 'local',
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => ConversionStatus::Completed,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);
    }
}