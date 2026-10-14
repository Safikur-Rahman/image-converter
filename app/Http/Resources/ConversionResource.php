<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'original_filename' => $this->original_filename,
            'original_format' => $this->original_format,
            'file_size' => $this->file_size,
            'width' => $this->width,
            'height' => $this->height,
            'remove_background' => (bool) $this->remove_background,
            'conversion_type' => $this->conversion_type,
            'quality' => $this->quality,
            'path_optimization' => $this->path_optimization,
            'error_message' => $this->error_message,
            'files' => ConversionFileResource::collection($this->whenLoaded('files')),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}