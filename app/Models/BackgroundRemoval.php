<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackgroundRemoval extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'conversion_id',
        'provider',
        'status',
        'input_path',
        'output_path',
    ];

    public function conversion(): BelongsTo
    {
        return $this->belongsTo(Conversion::class);
    }
}