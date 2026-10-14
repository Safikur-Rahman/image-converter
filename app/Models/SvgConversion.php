<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SvgConversion extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'conversion_id',
        'tracing_engine',
        'status',
        'input_width',
        'input_height',
        'output_width',
        'output_height',
        'original_svg_size',
        'optimized_svg_size',
        'path_count',
    ];

    protected $casts = [
        'input_width' => 'integer',
        'input_height' => 'integer',
        'output_width' => 'integer',
        'output_height' => 'integer',
        'original_svg_size' => 'integer',
        'optimized_svg_size' => 'integer',
        'path_count' => 'integer',
    ];

    public function conversion(): BelongsTo
    {
        return $this->belongsTo(Conversion::class);
    }
}