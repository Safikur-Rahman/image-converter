<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'price' => 0,
                'billing_interval' => 'monthly',
                'monthly_conversion_limit' => 5,
                'max_file_size_kb' => 5120,
                'max_dimensions' => 4000,
                'background_removal_enabled' => false,
                'api_enabled' => false,
                'is_active' => true,
                'features' => [
                    '5 conversions / month',
                    'PNG, JPG, JPEG, WEBP',
                    'Color and B&W SVG',
                    'Max 5 MB per file',
                ],
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price' => 12,
                'billing_interval' => 'monthly',
                'monthly_conversion_limit' => 200,
                'max_file_size_kb' => 15360,
                'max_dimensions' => 8000,
                'background_removal_enabled' => true,
                'api_enabled' => true,
                'is_active' => true,
                'features' => [
                    '200 conversions / month',
                    'AI background removal',
                    'API access',
                    'Max 15 MB per file',
                    'Priority processing',
                ],
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'price' => 39,
                'billing_interval' => 'monthly',
                'monthly_conversion_limit' => -1,
                'max_file_size_kb' => 51200,
                'max_dimensions' => 16000,
                'background_removal_enabled' => true,
                'api_enabled' => true,
                'is_active' => true,
                'features' => [
                    'Unlimited conversions',
                    'AI background removal',
                    'API access',
                    'Max 50 MB per file',
                    'Priority processing',
                    'Bulk conversion',
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}