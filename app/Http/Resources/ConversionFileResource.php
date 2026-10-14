<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversionFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->type,
            'filename' => $this->filename,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'download_url' => $this->when($this->type === 'svg', fn () => route('api.conversions.download', ['conversion' => $this->conversion?->uuid, 'type' => 'svg'])),
        ];
    }
}