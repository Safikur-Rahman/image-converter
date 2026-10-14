<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'name' => 'Free',
            'slug' => 'free',
            'price' => 0,
            'billing_interval' => 'monthly',
            'monthly_conversion_limit' => 5,
            'max_file_size_kb' => 10240,
            'max_dimensions' => 8000,
            'background_removal_enabled' => false,
            'api_enabled' => false,
            'features' => null,
            'is_active' => true,
        ];
    }
}