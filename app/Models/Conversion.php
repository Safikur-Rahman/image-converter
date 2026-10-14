<?php

namespace App\Models;

use App\Enums\ConversionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversion extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'status',
        'original_filename',
        'original_format',
        'file_size',
        'width',
        'height',
        'remove_background',
        'conversion_type',
        'quality',
        'path_optimization',
        'disk',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'remove_background' => 'boolean',
        'status' => ConversionStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ConversionFile::class);
    }

    public function file(string $type): ?ConversionFile
    {
        return $this->files()->where('type', $type)->first();
    }

    public function backgroundRemoval(): HasOne
    {
        return $this->hasOne(BackgroundRemoval::class);
    }

    public function svgConversion(): HasOne
    {
        return $this->hasOne(SvgConversion::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public function markStarted(): void
    {
        $this->update([
            'status' => ConversionStatus::Processing,
            'started_at' => now(),
        ]);
    }

    public function markFailed(string $safeMessage): void
    {
        $this->update([
            'status' => ConversionStatus::Failed,
            'error_message' => $safeMessage,
            'completed_at' => now(),
        ]);
    }

    public function markCompleted(): void
    {
        $this->update([
            'status' => ConversionStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function svgFile(): ?ConversionFile
    {
        return $this->file('svg');
    }

    public function originalFile(): ?ConversionFile
    {
        return $this->file('original');
    }

    public function backgroundRemovedFile(): ?ConversionFile
    {
        return $this->file('background_removed');
    }
}