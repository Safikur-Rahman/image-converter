<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'max_upload_size' => ['nullable', 'numeric', 'min:1', 'max:1048576'],
            'allowed_formats' => ['nullable', 'string'],
            'background_removal_available' => ['sometimes', 'boolean'],
            'default_svg_quality' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'storage_retention_days' => ['nullable', 'numeric', 'min:1', 'max:3650'],
            'maintenance_message' => ['nullable', 'string', 'max:500'],
        ];
    }
}