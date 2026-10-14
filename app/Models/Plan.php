<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'price',
        'billing_interval',
        'monthly_conversion_limit',
        'max_file_size_kb',
        'max_dimensions',
        'background_removal_enabled',
        'api_enabled',
        'features',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'monthly_conversion_limit' => 'integer',
        'max_file_size_kb' => 'integer',
        'max_dimensions' => 'integer',
        'background_removal_enabled' => 'boolean',
        'api_enabled' => 'boolean',
        'features' => 'json',
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}