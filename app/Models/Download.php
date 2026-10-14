<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Download extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'conversion_id',
        'conversion_file_id',
        'type',
        'ip_hash',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversion(): BelongsTo
    {
        return $this->belongsTo(Conversion::class);
    }

    public function conversionFile(): BelongsTo
    {
        return $this->belongsTo(ConversionFile::class);
    }
}