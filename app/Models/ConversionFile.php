<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversionFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversion_id',
        'type',
        'disk',
        'path',
        'filename',
        'mime_type',
        'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function conversion(): BelongsTo
    {
        return $this->belongsTo(Conversion::class);
    }
}