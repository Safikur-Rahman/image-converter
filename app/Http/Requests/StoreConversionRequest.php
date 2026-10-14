<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConversionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $limits = app(\App\Services\Usage\UsageLimitService::class);
        $user = $this->user();

        return [
            'image' => [
                'required',
                'file',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:'.(int) ceil($limits->maxFileSizeBytes($user) / 1024),
                'dimensions:max_width='.$limits->maxDimensions($user).',max_height='.$limits->maxDimensions($user),
            ],
            'remove_background' => ['sometimes', 'boolean'],
            'conversion_type' => ['required', Rule::in(['color', 'b_w'])],
            'quality' => ['required', Rule::in(['low', 'medium', 'high'])],
            'path_optimization' => ['sometimes', Rule::in(['simple', 'balanced', 'aggressive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'image.image' => 'The file must be a valid image.',
            'image.mimes' => 'Only PNG, JPG, JPEG and WEBP images are supported.',
            'image.max' => 'The image is larger than the maximum allowed size for your plan.',
            'image.dimensions' => 'The image dimensions exceed the maximum allowed size.',
        ];
    }
}