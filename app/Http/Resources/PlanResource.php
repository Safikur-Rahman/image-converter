<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => $this->price,
            'billing_interval' => $this->billing_interval,
            'monthly_conversion_limit' => $this->monthly_conversion_limit,
            'max_file_size_kb' => $this->max_file_size_kb,
            'max_dimensions' => $this->max_dimensions,
            'background_removal_enabled' => (bool) $this->background_removal_enabled,
            'api_enabled' => (bool) $this->api_enabled,
            'features' => $this->features,
        ];
    }
}