<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'action',
        'quantity',
        'metadata',
        'period',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'metadata' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}